<?php

namespace App\Http\Controllers;

use App\Events\TicketMessageSent;
use App\Events\TicketCreated;
use App\Models\ActionLog;
use App\Models\Asset;
use App\Models\Department;
use App\Models\Supplier;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TelegramNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        $tickets = Ticket::query()
            ->with(['department', 'category', 'assignee', 'asset', 'supplier', 'creator'])
            ->visibleTo(Auth::user())
            ->latest()
            ->paginate(10);

        return view('tickets.index', compact('tickets'));
    }

    public function create(): View|RedirectResponse
    {
        if (Auth::user()->isSecretaryDti()) {
            return redirect()->route('tickets.physical.create');
        }

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $assets = Asset::with('office')->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $requestToken = (string) Str::uuid();
        session(['ticket_create_token' => $requestToken]);

        return view('tickets.create', compact('departments', 'assets', 'suppliers', 'requestToken'));
    }

    public function createPhysical(): View
    {
        abort_unless(Auth::user()->isSecretaryDti(), 403);

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $users = User::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $agents = User::query()
            ->with('supportDepartments')
            ->whereIn('role', ['admin', 'support'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('tickets.create-physical', compact('departments', 'users', 'agents'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_if(Auth::user()->isSecretaryDti(), 403);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'message' => ['required', 'string', 'max:10000'],
            'request_token' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if (! hash_equals((string) session('ticket_create_token'), (string) $data['request_token'])) {
            $existingTicketId = session('last_created_ticket_id');

            if ($existingTicketId && $existingTicket = Ticket::whereKey($existingTicketId)->where('user_id', Auth::id())->first()) {
                return redirect()
                    ->route('tickets.show', $existingTicket)
                    ->with('success', "Ticket {$existingTicket->ticket_id} creado correctamente.");
            }

            return redirect()->route('tickets.create')->with('error', 'La solicitud ya fue procesada. Revisa tus tickets antes de intentarlo otra vez.');
        }

        session()->forget('ticket_create_token');

        $imagePath = $request->file('image')?->store('ticket-images', 'public');
        unset($data['image'], $data['request_token']);

        $ticket = Ticket::create([
            ...$data,
            'image_path' => $imagePath,
            'user_id' => Auth::id(),
            'status' => 'open',
            'due_at' => Ticket::dueDateForPriority($data['priority']),
        ]);

        $this->broadcastSafely(new TicketCreated($ticket));
        app(TelegramNotifier::class)->notifyTicketCreated($ticket);
        ActionLog::record('ticket.creado', $ticket, 'Ticket creado por funcionario.', ['ticket_id' => $ticket->ticket_id]);
        session(['last_created_ticket_id' => $ticket->id]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->ticket_id} creado correctamente.");
    }

    public function storePhysical(Request $request): RedirectResponse
    {
        abort_unless(Auth::user()->isSecretaryDti(), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'assigned_to' => ['required', 'exists:users,id'],
            'priority' => ['required', Rule::in(Ticket::PRIORITIES)],
            'circular_cite' => ['required', 'string', 'max:255'],
            'physical_instructions' => ['nullable', 'array'],
            'physical_instructions.*' => ['integer', Rule::in(array_keys(Ticket::PHYSICAL_INSTRUCTIONS))],
            'reference' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:10000'],
            'physical_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'user_id.required' => 'Selecciona el funcionario remitente.',
            'department_id.required' => 'Selecciona el tipo de soporte.',
            'assigned_to.required' => 'Selecciona a quien va dirigido el ticket.',
            'circular_cite.required' => 'Ingresa el CITE de circular interna.',
            'reference.required' => 'Ingresa la referencia de la solicitud fisica.',
            'message.required' => 'Ingresa una descripcion breve de la solicitud.',
            'physical_pdf.mimes' => 'El archivo de respaldo debe ser un PDF.',
        ]);

        $assignee = User::findOrFail($data['assigned_to']);
        if (! $assignee->isAgent()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'assigned_to' => 'El destinatario debe ser administrador o soporte.',
            ]);
        }

        if ($assignee->isSupport() && ! $assignee->handlesDepartment($data['department_id'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'assigned_to' => 'El soporte seleccionado no atiende el tipo de soporte elegido.',
            ]);
        }

        $pdfPath = $request->file('physical_pdf')?->store('ticket-physical-requests', 'public');

        $ticket = Ticket::create([
            'request_channel' => 'physical',
            'internal_cite' => $this->nextInternalCite(),
            'circular_cite' => $data['circular_cite'],
            'physical_instructions' => array_map('intval', $data['physical_instructions'] ?? []),
            'user_id' => $data['user_id'],
            'created_by_id' => Auth::id(),
            'department_id' => $data['department_id'],
            'assigned_to' => $data['assigned_to'],
            'subject' => $data['reference'],
            'reference' => $data['reference'],
            'message' => $data['message'],
            'physical_pdf_path' => $pdfPath,
            'status' => 'assigned',
            'priority' => $data['priority'],
            'due_at' => Ticket::dueDateForPriority($data['priority']),
        ]);

        $this->broadcastSafely(new TicketCreated($ticket));
        app(TelegramNotifier::class)->notifyTicketCreated($ticket);
        ActionLog::record('ticket.fisico_creado', $ticket, 'Ticket fisico creado por Secretaria DTI.', [
            'ticket_id' => $ticket->ticket_id,
            'internal_cite' => $ticket->internal_cite,
            'assigned_to' => $ticket->assigned_to,
        ]);

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', "Ticket fisico {$ticket->ticket_id} creado con CITE {$ticket->internal_cite}.");
    }

    public function printPhysical(Ticket $ticket): View
    {
        abort_unless($ticket->request_channel === 'physical', 404);
        abort_unless(Auth::user()->canViewTicket($ticket), 403);

        $ticket->load(['user.office', 'department', 'assignee', 'creator']);

        return view('tickets.print-physical', compact('ticket'));
    }

    public function show(Ticket $ticket): View
    {
        abort_unless(Auth::user()->canViewTicket($ticket), 403);

        $ticket->load(['user', 'department', 'category', 'assignee', 'asset', 'supplier', 'creator']);
        $messages = $ticket->messages()->with('user')->oldest()->get();

        return view('tickets.show', compact('ticket', 'messages'));
    }

    public function messages(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless(Auth::user()->canViewTicket($ticket), 403);

        $afterId = (int) $request->query('after_id', 0);

        $messages = $ticket->messages()
            ->with('user')
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->oldest()
            ->get()
            ->map(fn (TicketMessage $message) => [
                'id' => $message->id,
                'ticket_id' => $message->ticket_id,
                'user_id' => $message->user_id,
                'user_name' => $message->user->name,
                'user_role' => $message->user->role,
                'message' => $message->message,
                'image_url' => $message->image_path ? asset('storage/'.$message->image_path) : null,
                'created_at' => $message->created_at->format('d/m/Y H:i'),
            ]);

        $ticket->messages()
            ->where('user_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function globalMessages(Request $request): JsonResponse
    {
        $afterId = (int) $request->query('after_id', 0);
        $afterTicketId = (int) $request->query('after_ticket_id', 0);
        $baseQuery = $this->visibleIncomingMessagesQuery();
        $latestMessageId = (clone $baseQuery)->max('ticket_messages.id') ?? 0;
        $ticketQuery = $this->visibleIncomingTicketsQuery();
        $latestTicketId = $ticketQuery ? ((clone $ticketQuery)->max('tickets.id') ?? 0) : 0;

        if ($afterId <= 0 && $afterTicketId <= 0) {
            return response()->json([
                'messages' => [],
                'latest_message_id' => $latestMessageId,
                'tickets' => [],
                'latest_ticket_id' => $latestTicketId,
            ]);
        }

        $messages = (clone $baseQuery)
            ->where('ticket_messages.id', '>', $afterId)
            ->oldest('ticket_messages.id')
            ->limit(20)
            ->get()
            ->map(fn (TicketMessage $message) => [
                'id' => $message->id,
                'ticket_id' => $message->ticket_id,
                'user_id' => $message->user_id,
                'user_name' => $message->user->name,
                'user_role' => $message->user->role,
                'message' => $message->message,
                'image_url' => $message->image_path ? asset('storage/'.$message->image_path) : null,
                'ticket_subject' => $message->ticket->subject,
                'ticket_number' => $message->ticket->ticket_id,
                'created_at' => $message->created_at->format('d/m/Y H:i'),
            ]);

        $tickets = $ticketQuery
            ? (clone $ticketQuery)
                ->where('tickets.id', '>', $afterTicketId)
                ->with('creator')
                ->oldest('tickets.id')
                ->limit(20)
                ->get()
                ->map(fn (Ticket $ticket) => [
                    'id' => $ticket->id,
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_id,
                    'ticket_subject' => $ticket->subject,
                    'message' => $ticket->message,
                    'user_id' => $ticket->user_id,
                    'user_name' => $ticket->request_channel === 'physical'
                        ? ($ticket->creator?->name ?? 'Secretaria DTI')
                        : $ticket->user->name,
                    'created_at' => $ticket->created_at->format('d/m/Y H:i'),
                ])
            : collect();

        return response()->json([
            'messages' => $messages,
            'latest_message_id' => max($latestMessageId, $messages->max('id') ?? 0),
            'tickets' => $tickets,
            'latest_ticket_id' => max($latestTicketId, $tickets->max('id') ?? 0),
        ]);
    }

    public function addMessage(Request $request, Ticket $ticket): RedirectResponse|JsonResponse
    {
        abort_unless(Auth::user()->canViewTicket($ticket), 403);
        abort_if($ticket->status === 'closed', 422, 'No se puede responder un ticket cerrado.');

        $data = $request->validate([
            'message' => ['required_without:image', 'nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $imagePath = $request->file('image')?->store('ticket-images', 'public');
        unset($data['image']);

        $message = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'message' => $data['message'] ?? '',
            'image_path' => $imagePath,
        ])->load('user');

        if (Auth::user()->isAgent() && in_array($ticket->status, ['open', 'assigned', 'reopened'], true)) {
            $ticket->update([
                'status' => 'in_progress',
                'assigned_to' => $ticket->assigned_to ?: Auth::id(),
                'first_response_at' => $ticket->first_response_at ?: now(),
            ]);
            ActionLog::record('ticket.atendido', $ticket, 'Ticket tomado al responder.', ['assigned_to' => $ticket->assigned_to]);
        } elseif (Auth::user()->isAgent() && ! $ticket->first_response_at) {
            $ticket->update(['first_response_at' => now()]);
        }

        $this->broadcastSafely(new TicketMessageSent($message));

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => [
                    'id' => $message->id,
                    'ticket_id' => $message->ticket_id,
                    'user_id' => $message->user_id,
                    'user_name' => $message->user->name,
                    'user_role' => $message->user->role,
                    'message' => $message->message,
                    'image_url' => $message->image_path ? asset('storage/'.$message->image_path) : null,
                    'created_at' => $message->created_at->format('d/m/Y H:i'),
                ],
            ]);
        }

        return back()->with('success', 'Mensaje enviado correctamente.');
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        abort_unless(Auth::user()->canViewTicket($ticket), 403);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
        ActionLog::record('ticket.cerrado', $ticket, 'Ticket cerrado.');

        return redirect()->route('tickets.index')->with('success', 'Ticket cerrado correctamente.');
    }

    private function visibleIncomingMessagesQuery()
    {
        $user = Auth::user();

        return TicketMessage::query()
            ->with(['user', 'ticket'])
            ->where('user_id', '!=', $user->id)
            ->whereHas('ticket', function ($query) use ($user) {
                $query->visibleTo($user);
            });
    }

    private function broadcastSafely(object $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable $exception) {
            Log::warning('No se pudo enviar evento en tiempo real.', [
                'event' => $event::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function visibleIncomingTicketsQuery()
    {
        $user = Auth::user();

        if (! $user->isAgent()) {
            return null;
        }

        return Ticket::query()
            ->with('user')
            ->visibleTo($user)
            ->where('user_id', '!=', $user->id);
    }

    private function nextInternalCite(): string
    {
        $year = now()->format('Y');
        $prefix = "CITE-DTI-{$year}-";
        $last = Ticket::query()
            ->where('internal_cite', 'like', $prefix.'%')
            ->orderByDesc('internal_cite')
            ->value('internal_cite');
        $number = $last ? ((int) substr($last, -5)) + 1 : 1;

        return $prefix.str_pad((string) $number, 5, '0', STR_PAD_LEFT);
    }
}
