<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    use HasFactory;

    public const STATUSES = [
        'open' => 'Abierta',
        'in_progress' => 'En proceso',
        'resolved' => 'Resuelta',
        'closed' => 'Cerrada',
    ];

    protected $fillable = [
        'ticket_id',
        'user_id',
        'technician_id',
        'department_id',
        'office_id',
        'title',
        'equipment',
        'location',
        'description',
        'actions_taken',
        'result',
        'status',
        'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAgent()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }
}
