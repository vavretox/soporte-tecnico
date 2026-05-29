<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChangeRecord extends Model
{
    use HasFactory;

    public const STATUSES = [
        'planned' => 'Planificado',
        'approved' => 'Aprobado',
        'in_progress' => 'En proceso',
        'completed' => 'Completado',
        'cancelled' => 'Cancelado',
    ];

    public const PRIORITIES = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'critical' => 'Critica',
    ];

    protected $fillable = [
        'ticket_id',
        'title',
        'type',
        'status',
        'priority',
        'requested_by',
        'assigned_to',
        'department_id',
        'asset_id',
        'scheduled_at',
        'completed_at',
        'description',
        'risk',
        'rollback_plan',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
