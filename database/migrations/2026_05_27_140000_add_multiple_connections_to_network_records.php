<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('network_records', function (Blueprint $table) {
            $table->json('connection_types')->nullable()->after('connection_type');
            $table->json('network_interfaces')->nullable()->after('mac_address');
        });

        DB::table('network_records')
            ->whereNotNull('connection_type')
            ->orderBy('id')
            ->chunkById(100, function ($records) {
                foreach ($records as $record) {
                    DB::table('network_records')
                        ->where('id', $record->id)
                        ->update([
                            'connection_types' => json_encode([$record->connection_type]),
                            'network_interfaces' => json_encode([[
                                'ip_address' => $record->ip_address,
                                'mac_address' => $record->mac_address,
                            ]]),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('network_records', function (Blueprint $table) {
            $table->dropColumn(['connection_types', 'network_interfaces']);
        });
    }
};
