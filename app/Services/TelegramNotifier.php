<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramNotifier
{
    public function notifyTicketCreated(Ticket $ticket): void
    {
        if (! $this->enabled()) {
            return;
        }

        $ticket->loadMissing(['user', 'department', 'category']);

        $text = implode("\n", array_filter([
            'Nuevo ticket de soporte',
            "Ticket: {$ticket->ticket_id}",
            "Funcionario: {$ticket->user?->name}",
            "Tipo: {$ticket->department?->name}",
            "Prioridad: ".ucfirst($ticket->priority),
            "Asunto: {$ticket->subject}",
            route('admin.ticket.show', $ticket),
        ]));

        $this->sendToUsers($this->ticketRecipients($ticket), $text);
    }

    public function syncPendingLinks(): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        $linked = 0;

        foreach ($this->updates() as $update) {
            $message = $update['message'] ?? null;
            $chat = $message['chat'] ?? null;
            $text = trim((string) ($message['text'] ?? ''));

            if (! $chat || ($chat['type'] ?? '') !== 'private' || $text === '') {
                continue;
            }

            $code = $this->extractLinkCode($text);

            if (! $code) {
                continue;
            }

            $user = User::where('telegram_link_code', $code)->first();

            if (! $user) {
                continue;
            }

            $this->linkUser($user, (string) $chat['id']);
            $linked++;
        }

        return $linked;
    }

    private function enabled(): bool
    {
        return (bool) config('services.telegram.enabled') && filled(config('services.telegram.bot_token'));
    }

    private function ticketRecipients(Ticket $ticket): EloquentCollection
    {
        return User::query()
            ->where('is_active', true)
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->whereIn('role', ['admin', 'support'])
            ->get()
            ->filter(function (User $user) use ($ticket) {
                return $user->canViewTicket($ticket);
            })
            ->values();
    }

    private function sendToUsers($users, string $text): void
    {
        $users->unique('telegram_chat_id')->each(fn (User $user) => $this->send($user->telegram_chat_id, $text));
    }

    private function linkUser(User $user, string $chatId): void
    {
        $user->update([
            'telegram_chat_id' => $chatId,
            'telegram_linked_at' => now(),
            'telegram_link_code' => null,
        ]);

        $this->send($chatId, 'Tu cuenta quedo vinculada al Sistema de Helpdesk Soporte Tecnico DTI GADT.');
    }

    private function extractLinkCode(string $text): ?string
    {
        if (preg_match('/HD-[A-Z0-9]{8}/i', $text, $matches)) {
            return strtoupper($matches[0]);
        }

        return null;
    }

    private function updates(): array
    {
        try {
            $response = Http::timeout(8)->get($this->endpoint('getUpdates'), [
                'allowed_updates' => json_encode(['message']),
            ]);

            if ($response->failed()) {
                Log::warning('No se pudieron consultar actualizaciones Telegram.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return $response->json('result') ?? [];
        } catch (\Throwable $exception) {
            Log::warning('Error consultando actualizaciones Telegram.', [
                'message' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function send(string $chatId, string $text): void
    {
        try {
            $response = Http::timeout(8)->asForm()->post($this->endpoint('sendMessage'), [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                Log::warning('No se pudo enviar notificacion Telegram.', [
                    'chat_id' => $chatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Error enviando notificacion Telegram.', [
                'chat_id' => $chatId,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function endpoint(string $method): string
    {
        return 'https://api.telegram.org/bot'.config('services.telegram.bot_token').'/'.$method;
    }
}
