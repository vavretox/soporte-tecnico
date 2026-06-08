<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'must_change_password',
        'password_changed_at',
        'role',
        'avatar_icon',
        'avatar_color',
        'avatar_path',
        'office_id',
        'department_id',
        'telegram_chat_id',
        'rustdesk_id',
        'rustdesk_alias',
        'telegram_link_code',
        'telegram_linked_at',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'is_active' => 'boolean',
            'telegram_linked_at' => 'datetime',
        ];
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function supportDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function supportDepartments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'support_department_user')->withTimestamps();
    }

    public function supportDepartmentIds(): array
    {
        $ids = [];

        if ($this->relationLoaded('supportDepartments')) {
            $ids = $this->supportDepartments->pluck('id')->all();
        } elseif ($this->exists) {
            $ids = $this->supportDepartments()->pluck('departments.id')->all();
        }

        if ($this->department_id) {
            $ids[] = (int) $this->department_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    public function handlesDepartment(int|string|null $departmentId): bool
    {
        if (! $departmentId) {
            return false;
        }

        return in_array((int) $departmentId, $this->supportDepartmentIds(), true);
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function rustDeskSessionsRequested(): HasMany
    {
        return $this->hasMany(RustDeskSession::class, 'requester_id');
    }

    public function rustDeskSessionsAsTarget(): HasMany
    {
        return $this->hasMany(RustDeskSession::class, 'target_user_id');
    }

    public function rustDeskSessionsAsTechnician(): HasMany
    {
        return $this->hasMany(RustDeskSession::class, 'technician_id');
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class);
    }

    public function assignedBitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'technician_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'assigned_to');
    }

    public function avatarIconClass(): string
    {
        return match ($this->avatar_icon) {
            'headset' => 'fa-headset',
            'laptop' => 'fa-laptop-code',
            'shield' => 'fa-shield-halved',
            'briefcase' => 'fa-briefcase',
            'seedling' => 'fa-seedling',
            default => 'fa-user',
        };
    }

    public function avatarColorClasses(): string
    {
        return match ($this->avatar_color) {
            'amber' => 'bg-amber-100 text-amber-700',
            'orange' => 'bg-orange-100 text-orange-700',
            'teal' => 'bg-teal-100 text-teal-700',
            'slate' => 'bg-slate-100 text-slate-700',
            'rose' => 'bg-rose-100 text-rose-700',
            default => 'bg-green-100 text-green-700',
        };
    }

    public function avatarUrl(): ?string
    {
        return $this->avatar_path ? asset('storage/'.$this->avatar_path) : null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSupport(): bool
    {
        return $this->role === 'support';
    }

    public function isSecretaryDti(): bool
    {
        return $this->role === 'secretary_dti';
    }

    public function isManager(): bool
    {
        return $this->isAgent();
    }

    public function isAgent(): bool
    {
        return in_array($this->role, ['admin', 'support'], true);
    }

    public function canViewTicket(Ticket $ticket): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isSupport()) {
            return (int) $ticket->assigned_to === (int) $this->id
                || $this->handlesDepartment($ticket->department_id);
        }

        if ($ticket->request_channel === 'physical') {
            return (int) $ticket->created_by_id === (int) $this->id;
        }

        return (int) $ticket->user_id === (int) $this->id
            || (int) $ticket->created_by_id === (int) $this->id;
    }

    public function canReportTicket(Ticket $ticket): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isSupport()) {
            return (int) $ticket->assigned_to === (int) $this->id;
        }

        if ($ticket->request_channel === 'physical') {
            return (int) $ticket->created_by_id === (int) $this->id;
        }

        return (int) $ticket->user_id === (int) $this->id
            || (int) $ticket->created_by_id === (int) $this->id;
    }

    public function canViewBitacora(Bitacora $bitacora): bool
    {
        if ($this->isAgent()) {
            return true;
        }

        return (int) $bitacora->user_id === (int) $this->id;
    }
}
