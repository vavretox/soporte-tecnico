<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkRecord extends Model
{
    use HasFactory;

    public const CONNECTION_TYPES = [
        'cable' => 'Cable',
        'wifi' => 'WiFi',
    ];

    protected $fillable = [
        'responsible',
        'position',
        'ip_address',
        'mac_address',
        'connection_type',
        'connection_types',
        'network_interfaces',
        'connected_devices',
        'mobile_devices',
        'pc_devices',
        'hostname',
        'has_email',
        'email',
        'has_shared_folders',
        'shared_folders',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'has_email' => 'boolean',
            'has_shared_folders' => 'boolean',
            'connection_types' => 'array',
            'network_interfaces' => 'array',
        ];
    }

    public function selectedConnectionTypes(): array
    {
        return $this->connection_types ?: [$this->connection_type ?: 'cable'];
    }

    public function interfaceRows(): array
    {
        $rows = $this->network_interfaces ?: [];

        if (! $rows && ($this->ip_address || $this->mac_address)) {
            $rows[] = [
                'ip_address' => $this->ip_address,
                'mac_address' => $this->mac_address,
            ];
        }

        return $rows;
    }
}
