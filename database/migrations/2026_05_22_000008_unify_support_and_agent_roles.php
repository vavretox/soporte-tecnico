<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'agent')->update(['role' => 'support']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'support', 'admin') NOT NULL DEFAULT 'user'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'support', 'agent', 'admin') NOT NULL DEFAULT 'user'");
    }
};
