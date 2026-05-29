<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ticket $ticket)
    {
        $this->ticket->loadMissing('user');
    }

    public function broadcastOn(): array
    {
        return User::query()
            ->whereIn('role', ['admin', 'support'])
            ->where('is_active', true)
            ->where('id', '!=', $this->ticket->user_id)
            ->get()
            ->filter(fn (User $user) => $user->canViewTicket($this->ticket))
            ->map(fn (User $user) => new PrivateChannel('user.'.$user->id))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'ticket.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->ticket->id,
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_id,
            'ticket_subject' => $this->ticket->subject,
            'message' => $this->ticket->message,
            'user_id' => $this->ticket->user_id,
            'user_name' => $this->ticket->user->name,
            'created_at' => $this->ticket->created_at->format('d/m/Y H:i'),
        ];
    }
}
