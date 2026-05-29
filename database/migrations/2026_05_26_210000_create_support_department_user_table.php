<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'department_id']);
        });

        DB::table('users')
            ->where('role', 'support')
            ->whereNotNull('department_id')
            ->orderBy('id')
            ->select(['id', 'department_id'])
            ->chunk(100, function ($users) {
                foreach ($users as $user) {
                    DB::table('support_department_user')->updateOrInsert(
                        [
                            'user_id' => $user->id,
                            'department_id' => $user->department_id,
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_department_user');
    }
};
