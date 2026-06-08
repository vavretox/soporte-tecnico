<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RustDeskSession extends Model
{
    use HasFactory;

    protected $table = 'rustdesk_sessions';

    public const STATUSES = ['requested', 'accepted', 'started', 'completed', 'cancelled'];

    public const STATUS_LABELS = [
        'requested' => 'Solicitada',
        'accepted' => 'Aceptada',
        'started' => 'En curso',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ];

    protected $fillable = [
        'ticket_id',
        'requester_id',
        'target_user_id',
        'technician_id',
        'remote_id',
        'direction',
        'status',
        'reason',
        'accepted_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['requested', 'accepted', 'started'], true);
    }
}
