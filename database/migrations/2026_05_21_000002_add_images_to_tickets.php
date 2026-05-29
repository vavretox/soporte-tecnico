<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('message');
        });

        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_messages', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
