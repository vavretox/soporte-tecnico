<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open', 'assigned', 'in_progress', 'send_note', 'waiting_user', 'resolved', 'closed', 'reopened') NOT NULL DEFAULT 'open'");
    }

    public function down(): void
    {
        DB::table('tickets')->where('status', 'send_note')->update(['status' => 'in_progress']);
        DB::statement("ALTER TABLE tickets MODIFY status ENUM('open', 'assigned', 'in_progress', 'waiting_user', 'resolved', 'closed', 'reopened') NOT NULL DEFAULT 'open'");
    }
};
