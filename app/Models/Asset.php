<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asset extends Model
{
    use HasFactory;

    public const TYPES = [
        'hardware' => 'Hardware',
        'software' => 'Software',
    ];

    public const STATUSES = [
        'active' => 'Activo',
        'maintenance' => 'En mantenimiento',
        'retired' => 'Retirado',
        'lost' => 'Extraviado',
    ];

    protected $fillable = [
        'asset_tag',
        'name',
        'type',
        'brand',
        'model',
        'serial_number',
        'version',
        'status',
        'office_id',
        'assigned_to',
        'purchase_date',
        'warranty_until',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_until' => 'date',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ChangeRecord::class);
    }
}
