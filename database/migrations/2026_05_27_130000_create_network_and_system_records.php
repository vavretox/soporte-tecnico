<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_records', function (Blueprint $table) {
            $table->id();
            $table->string('responsible');
            $table->string('position');
            $table->string('ip_address')->nullable()->index();
            $table->string('mac_address')->nullable()->index();
            $table->enum('connection_type', ['cable', 'wifi'])->default('cable')->index();
            $table->unsignedInteger('connected_devices')->default(0);
            $table->unsignedInteger('mobile_devices')->default(0);
            $table->unsignedInteger('pc_devices')->default(0);
            $table->string('hostname')->nullable()->index();
            $table->boolean('has_email')->default(false);
            $table->string('email')->nullable();
            $table->boolean('has_shared_folders')->default(false);
            $table->text('shared_folders')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('system_records', function (Blueprint $table) {
            $table->id();
            $table->string('responsible');
            $table->string('position');
            $table->string('system_type')->nullable();
            $table->string('username')->nullable();
            $table->json('checklist')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('asset_system_record', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['system_record_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_system_record');
        Schema::dropIfExists('system_records');
        Schema::dropIfExists('network_records');
    }
};
