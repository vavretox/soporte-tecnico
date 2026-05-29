<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    public const STATUSES = [
        'active' => 'Activo',
        'pending' => 'Pendiente',
        'expired' => 'Vencido',
        'cancelled' => 'Cancelado',
    ];

    protected $fillable = [
        'supplier_id',
        'name',
        'contract_number',
        'type',
        'status',
        'starts_at',
        'ends_at',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
