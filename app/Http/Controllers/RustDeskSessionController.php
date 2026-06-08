<?php

namespace App\Http\Controllers;

use App\Events\TicketMessageSent;
use App\Models\ActionLog;
use App\Models\RustDeskSession;
use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RustDeskSessionController extends Controller
{
    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless(Auth::user()->canViewTicket($ticket), 403);
        abort_if($ticket->status === 'closed', 422, 'No se puede solicitar asistencia remota en un ticket cerrado.');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = Auth::user();
        $direction = $user->isAgent() ? 'support_to_user' : 'user_to_support';
        $targetUser = $ticket->user;

        if ($direction === 'user_to_support') {
            abort_unless((int) $ticket->user_id === (int) $user->id, 403);
            $targetUser = $user;
        }

        if (! $targetUser?->rustdesk_id) {
            return back()->with('error', 'El funcionario no tiene ID de RustDesk registrado.');
        }

        $existing = $ticket->rustDeskSessions()
            ->whereIn('status', ['requested', 'accepted', 'started'])
            ->latest()
            ->first();

        if ($existing) {
            return back()->with('error', 'Ya existe una solicitud remota abierta para este ticket.');
        }

        $session = RustDeskSession::create([
            'ticket_id' => $ticket->id,
            'requester_id' => $user->id,
            'target_user_id' => $targetUser->id,
            'technician_id' => $user->isAgent() ? $user->id : $ticket->assigned_to,
            'remote_id' => $targetUser->rustdesk_id,
            'direction' => $direction,
            'status' => 'requested',
            'reason' => $data['reason'] ?? null,
        ]);

        $this->addSystemMessage($ticket, $this->requestMessage($session));
        ActionLog::record('rustdesk.solicitado', $session, 'Solicitud remota RustDesk creada.', [
            'ticket_id' => $ticket->ticket_id,
            'remote_id' => $session->remote_id,
            'direction' => $session->direction,
        ]);

        return back()->with('success', 'Solicitud remota RustDesk registrada.');
    }

    public function accept(Ticket $ticket, RustDeskSession $rustdeskSession): RedirectResponse
    {
        $this->authorizeSession($ticket, $rustdeskSession);
        abort_unless($this->canAccept($rustdeskSession), 403);
        abort_unless($rustdeskSession->status === 'requested', 422);

        $rustdeskSession->update([
            'status' => 'accepted',
            'technician_id' => Auth::user()->isAgent() ? Auth::id() : $rustdeskSession->technician_id,
            'accepted_at' => now(),
        ]);

        $this->addSystemMessage($ticket, 'La solicitud remota RustDesk fue aceptada.');
        ActionLog::record('rustdesk.aceptado', $rustdeskSession, 'Solicitud remota RustDesk aceptada.');

        return back()->with('success', 'Solicitud RustDesk aceptada.');
    }

    public function start(Ticket $ticket, RustDeskSession $rustdeskSession): RedirectResponse
    {
        $this->authorizeSession($ticket, $rustdeskSession);
        abort_unless(Auth::user()->isAgent(), 403);
        abort_unless(in_array($rustdeskSession->status, ['requested', 'accepted'], true), 422);
        abort_if($rustdeskSession->direction === 'support_to_user' && $rustdeskSession->status !== 'accepted', 403);

        $rustdeskSession->update([
            'status' => 'started',
            'technician_id' => Auth::id(),
            'started_at' => now(),
        ]);

        if (in_array($ticket->status, ['open', 'assigned', 'reopened'], true)) {
            $ticket->update([
                'status' => 'in_progress',
                'assigned_to' => $ticket->assigned_to ?: Auth::id(),
                'first_response_at' => $ticket->first_response_at ?: now(),
            ]);
        }

        $this->addSystemMessage($ticket, "Soporte inicio una sesion remota RustDesk hacia el ID {$rustdeskSession->remote_id}.");
        ActionLog::record('rustdesk.iniciado', $rustdeskSession, 'Sesion remota RustDesk iniciada.', [
            'remote_id' => $rustdeskSession->remote_id,
        ]);

        return redirect()->away($this->openUrl($rustdeskSession->remote_id));
    }

    public function complete(Ticket $ticket, RustDeskSession $rustdeskSession): RedirectResponse
    {
        $this->authorizeSession($ticket, $rustdeskSession);
        abort_unless(Auth::user()->isAgent(), 403);
        abort_unless(in_array($rustdeskSession->status, ['accepted', 'started'], true), 422);

        $rustdeskSession->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->addSystemMessage($ticket, 'La sesion remota RustDesk fue marcada como completada.');
        ActionLog::record('rustdesk.completado', $rustdeskSession, 'Sesion remota RustDesk completada.');

        return back()->with('success', 'Sesion RustDesk completada.');
    }

    public function cancel(Ticket $ticket, RustDeskSession $rustdeskSession): RedirectResponse
    {
        $this->authorizeSession($ticket, $rustdeskSession);
        abort_unless($this->canCancel($rustdeskSession), 403);
        abort_unless($rustdeskSession->isOpen(), 422);

        $rustdeskSession->update(['status' => 'cancelled']);

        $this->addSystemMessage($ticket, 'La solicitud remota RustDesk fue cancelada.');
        ActionLog::record('rustdesk.cancelado', $rustdeskSession, 'Solicitud remota RustDesk cancelada.');

        return back()->with('success', 'Solicitud RustDesk cancelada.');
    }

    private function authorizeSession(Ticket $ticket, RustDeskSession $session): void
    {
        abort_unless((int) $session->ticket_id === (int) $ticket->id, 404);
        abort_unless(Auth::user()->canViewTicket($ticket), 403);
    }

    private function canAccept(RustDeskSession $session): bool
    {
        $user = Auth::user();

        if ($session->direction === 'user_to_support') {
            return $user->isAgent() && $user->canViewTicket($session->ticket);
        }

        return (int) $session->target_user_id === (int) $user->id;
    }

    private function canCancel(RustDeskSession $session): bool
    {
        $user = Auth::user();

        return $user->isAgent()
            || (int) $session->requester_id === (int) $user->id
            || (int) $session->target_user_id === (int) $user->id;
    }

    private function requestMessage(RustDeskSession $session): string
    {
        $prefix = $session->direction === 'user_to_support'
            ? 'El funcionario solicito asistencia remota por RustDesk.'
            : 'Soporte solicito permiso para iniciar asistencia remota por RustDesk.';

        return trim($prefix.($session->reason ? "\nMotivo: {$session->reason}" : ''));
    }

    private function addSystemMessage(Ticket $ticket, string $text): void
    {
        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $text,
        ])->load('user');

        try {
            broadcast(new TicketMessageSent($message));
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar mensaje RustDesk en tiempo real.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function openUrl(string $remoteId): string
    {
        $template = config('rustdesk.open_url_template') ?: 'rustdesk://connect/{id}';

        return str_replace('{id}', rawurlencode($remoteId), $template);
    }
}
