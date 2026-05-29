<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacoras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('equipment')->nullable();
            $table->string('location')->nullable();
            $table->longText('description');
            $table->longText('actions_taken')->nullable();
            $table->longText('result')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open')->index();
            $table->timestamp('reported_at')->nullable()->index();
            $table->timestamps();
        });

        $departments = [
            'Infraestructura y Telecomunicaciones' => 'Gestion de infraestructura tecnologica, enlaces, comunicaciones, servidores, cableado estructurado y continuidad de servicios.',
            'Sistemas y Base de Datos' => 'Atencion de sistemas institucionales, usuarios, permisos, errores de aplicacion, consultas y administracion de bases de datos.',
            'Soporte Tecnico y Redes' => 'Soporte a equipos, impresoras, perifericos, conectividad de usuario, WiFi, red local y atencion tecnica de primer nivel.',
        ];

        foreach ($departments as $name => $description) {
            $slug = Str::slug($name);
            DB::table('departments')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacoras');
    }
};
