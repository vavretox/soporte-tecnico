<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'support', 'secretary_dti', 'admin') NOT NULL DEFAULT 'user'");

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('created_by_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->enum('request_channel', ['digital', 'physical'])->default('digital')->after('ticket_id')->index();
            $table->string('internal_cite')->nullable()->after('request_channel')->unique();
            $table->string('reference')->nullable()->after('subject');
            $table->string('physical_pdf_path')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by_id');
            $table->dropColumn(['request_channel', 'internal_cite', 'reference', 'physical_pdf_path']);
        });

        DB::statement("ALTER TABLE users MODIFY role ENUM('user', 'support', 'admin') NOT NULL DEFAULT 'user'");
    }
};
