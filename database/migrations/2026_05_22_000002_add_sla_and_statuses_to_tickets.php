<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed', 'reopened') NOT NULL DEFAULT 'open'");

        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('first_response_at')->nullable()->after('resolved_at');
            $table->timestamp('due_at')->nullable()->after('first_response_at')->index();
            $table->timestamp('reopened_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['first_response_at', 'due_at', 'reopened_at']);
        });

        DB::table('tickets')->whereIn('status', ['assigned', 'waiting_user', 'reopened'])->update(['status' => 'in_progress']);
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
    }
};
