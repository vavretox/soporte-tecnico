<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class SystemRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'responsible',
        'position',
        'system_type',
        'username',
        'checklist',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
        ];
    }

    public function systems(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_system_record', 'system_record_id', 'asset_id')
            ->withPivot('username')
            ->withTimestamps();
    }
}
