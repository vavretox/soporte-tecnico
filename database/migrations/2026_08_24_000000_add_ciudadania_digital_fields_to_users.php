<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ciudadania_digital_sub')->nullable()->unique()->after('email');
            $table->timestamp('ciudadania_digital_last_login_at')->nullable()->after('remember_token');
            $table->json('ciudadania_digital_claims')->nullable()->after('ciudadania_digital_last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['ciudadania_digital_sub']);
            $table->dropColumn([
                'ciudadania_digital_sub',
                'ciudadania_digital_last_login_at',
                'ciudadania_digital_claims',
            ]);
        });
    }
};
