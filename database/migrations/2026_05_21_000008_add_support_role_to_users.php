<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'agent', 'support', 'admin') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'support')->update(['role' => 'agent']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'agent', 'admin') NOT NULL DEFAULT 'user'");
    }
};
