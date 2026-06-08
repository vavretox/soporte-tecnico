<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rustdesk_id', 80)->nullable()->after('telegram_chat_id')->index();
            $table->string('rustdesk_alias')->nullable()->after('rustdesk_id');
        });

        Schema::create('rustdesk_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('remote_id', 80);
            $table->enum('direction', ['user_to_support', 'support_to_user'])->default('user_to_support')->index();
            $table->enum('status', ['requested', 'accepted', 'started', 'completed', 'cancelled'])->default('requested')->index();
            $table->text('reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rustdesk_sessions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rustdesk_id', 'rustdesk_alias']);
        });
    }
};
