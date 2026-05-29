<?php

namespace App\Events;

use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public TicketMessage $message)
    {
        $this->message->loadMissing(['user', 'ticket']);
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('ticket.'.$this->message->ticket_id),
        ];

        foreach ($this->recipientUserIds() as $userId) {
            $channels[] = new PrivateChannel('user.'.$userId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'ticket.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'ticket_id' => $this->message->ticket_id,
            'user_id' => $this->message->user_id,
            'user_name' => $this->message->user->name,
            'user_role' => $this->message->user->role,
            'message' => $this->message->message,
            'image_url' => $this->message->image_path ? asset('storage/'.$this->message->image_path) : null,
            'ticket_subject' => $this->message->ticket->subject,
            'ticket_number' => $this->message->ticket->ticket_id,
            'created_at' => $this->message->created_at->format('d/m/Y H:i'),
        ];
    }

    private function recipientUserIds(): array
    {
        $ticket = $this->message->ticket;

        if ($this->message->user->isAgent()) {
            $ids = [$ticket->user_id];
        } elseif ($ticket->assigned_to) {
            $ids = [$ticket->assigned_to];
        } else {
            $ids = User::query()
                ->whereIn('role', ['admin', 'support'])
                ->where('is_active', true)
                ->get()
                ->filter(fn (User $user) => $user->canViewTicket($ticket))
                ->pluck('id')
                ->all();
        }

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $this->message->user_id)
            ->unique()
            ->values()
            ->all();
    }
}
