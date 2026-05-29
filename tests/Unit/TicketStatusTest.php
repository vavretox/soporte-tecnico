<?php

namespace Tests\Unit;

use App\Models\Ticket;
use PHPUnit\Framework\TestCase;

class TicketStatusTest extends TestCase
{
    public function test_status_label_uses_configured_translation(): void
    {
        $ticket = new Ticket(['status' => 'waiting_user']);

        $this->assertSame('Esperando usuario', $ticket->statusLabel());
    }

    public function test_send_note_status_label_is_available(): void
    {
        $ticket = new Ticket(['status' => 'send_note']);

        $this->assertSame('Enviar Nota', $ticket->statusLabel());
    }

    public function test_due_date_depends_on_priority(): void
    {
        $dueAt = Ticket::dueDateForPriority('high');

        $this->assertNotNull($dueAt);
        $this->assertEqualsWithDelta(24, now()->diffInHours($dueAt, false), 0.01);
    }
}
