<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    public const STATUSES = ['open', 'assigned', 'in_progress', 'send_note', 'waiting_user', 'resolved', 'closed', 'reopened'];
    public const STATUS_LABELS = [
        'open' => 'Abierto',
        'assigned' => 'Asignado',
        'in_progress' => 'En atencion',
        'send_note' => 'Enviar Nota',
        'waiting_user' => 'Esperando usuario',
        'resolved' => 'Resuelto',
        'closed' => 'Cerrado',
        'reopened' => 'Reabierto',
    ];
    public const PRIORITIES = ['low', 'medium', 'high', 'urgent'];
    public const PRIORITY_LABELS = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
        'urgent' => 'Urgente',
    ];
    public const SLA_HOURS = [
        'low' => 72,
        'medium' => 48,
        'high' => 24,
        'urgent' => 8,
    ];
    public const PHYSICAL_INSTRUCTIONS = [
        1 => 'Para su conocimiento',
        2 => 'Para su consideracion',
        3 => 'Para su seguimiento',
        4 => 'Para su cumplimiento',
        5 => 'Tomar accion',
        6 => 'Proseguir tramite',
        7 => 'Analizar y Opinar',
        8 => 'Preparar Respuesta',
        9 => 'Remitir antecedentes',
        10 => 'Preparar viaje',
        11 => 'Informar',
        12 => 'Visitarme',
        13 => 'Aprobado',
        14 => 'Archivar',
    ];

    protected $fillable = [
        'ticket_id',
        'request_channel',
        'internal_cite',
        'circular_cite',
        'physical_instructions',
        'user_id',
        'created_by_id',
        'department_id',
        'category_id',
        'asset_id',
        'supplier_id',
        'assigned_to',
        'subject',
        'reference',
        'message',
        'image_path',
        'physical_pdf_path',
        'status',
        'priority',
        'closed_at',
        'resolved_at',
        'first_response_at',
        'due_at',
        'reopened_at',
    ];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
            'resolved_at' => 'datetime',
            'first_response_at' => 'datetime',
            'due_at' => 'datetime',
            'reopened_at' => 'datetime',
            'physical_instructions' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (! $ticket->ticket_id) {
                $ticket->ticket_id = 'TK-'.now()->format('Ymd').'-'.str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class);
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ChangeRecord::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isSupport()) {
            $departmentIds = $user->supportDepartmentIds();

            return $query->where(function (Builder $scope) use ($user, $departmentIds) {
                $scope->where('assigned_to', $user->id);

                if ($departmentIds) {
                    $scope->orWhereIn('department_id', $departmentIds);
                }
            });
        }

        return $query->where(function (Builder $scope) use ($user) {
            $scope->where(function (Builder $digital) use ($user) {
                $digital->where('request_channel', 'digital')
                    ->where('user_id', $user->id);
            })->orWhere(function (Builder $physical) use ($user) {
                $physical->where('request_channel', 'physical')
                    ->where('created_by_id', $user->id);
            });
        });
    }

    public function scopeReportVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($user->isSupport()) {
            return $query->where('assigned_to', $user->id);
        }

        return $query->where(function (Builder $scope) use ($user) {
            $scope->where(function (Builder $digital) use ($user) {
                $digital->where('request_channel', 'digital')
                    ->where('user_id', $user->id);
            })->orWhere(function (Builder $physical) use ($user) {
                $physical->where('request_channel', 'physical')
                    ->where('created_by_id', $user->id);
            });
        });
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function priorityLabel(): string
    {
        return self::PRIORITY_LABELS[$this->priority] ?? ucfirst(str_replace('_', ' ', $this->priority));
    }

    public static function dueDateForPriority(string $priority)
    {
        return now()->addHours(self::SLA_HOURS[$priority] ?? self::SLA_HOURS['medium']);
    }

    public function isOverdue(): bool
    {
        return $this->due_at && ! in_array($this->status, ['resolved', 'closed'], true) && $this->due_at->isPast();
    }
}
