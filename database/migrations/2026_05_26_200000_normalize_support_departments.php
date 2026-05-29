<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departments = [
            [
                'name' => 'Infraestructura y Telecomunicaciones',
                'slug' => 'infraestructura-y-telecomunicaciones',
                'legacy_slugs' => ['redes'],
                'description' => 'Gestion de infraestructura tecnologica, enlaces, comunicaciones, servidores, cableado estructurado y continuidad de servicios.',
            ],
            [
                'name' => 'Sistemas y Base de Datos',
                'slug' => 'sistemas-y-base-de-datos',
                'legacy_slugs' => ['sistemas'],
                'description' => 'Atencion de sistemas institucionales, usuarios, permisos, errores de aplicacion, consultas y administracion de bases de datos.',
            ],
            [
                'name' => 'Soporte Tecnico y Redes',
                'slug' => 'soporte-tecnico-y-redes',
                'legacy_slugs' => ['soporte-tecnico'],
                'description' => 'Soporte a equipos, impresoras, perifericos, conectividad de usuario, WiFi, red local y atencion tecnica de primer nivel.',
            ],
        ];

        $activeDepartmentIds = [];

        foreach ($departments as $department) {
            $row = DB::table('departments')->where('slug', $department['slug'])->first();

            if (! $row) {
                $row = DB::table('departments')
                    ->whereIn('slug', $department['legacy_slugs'])
                    ->orderBy('id')
                    ->first();
            }

            $payload = [
                'name' => $department['name'],
                'slug' => $department['slug'],
                'description' => $department['description'],
                'is_active' => true,
                'updated_at' => now(),
            ];

            if ($row) {
                DB::table('departments')->where('id', $row->id)->update($payload);
                $activeDepartmentIds[$department['slug']] = $row->id;
                continue;
            }

            $activeDepartmentIds[$department['slug']] = DB::table('departments')->insertGetId([
                ...$payload,
                'created_at' => now(),
            ]);
        }

        DB::table('departments')
            ->whereNotIn('id', array_values($activeDepartmentIds))
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        $this->syncCategory('problemas-acceso', 'Problemas de Acceso', $activeDepartmentIds['sistemas-y-base-de-datos']);
        $this->syncCategory('error-sistema', 'Error en Sistema', $activeDepartmentIds['sistemas-y-base-de-datos']);
        $this->syncCategory('falla-conexion', 'Falla de Conexion', $activeDepartmentIds['soporte-tecnico-y-redes']);
        $this->syncCategory('infraestructura-tecnologica', 'Infraestructura Tecnologica', $activeDepartmentIds['infraestructura-y-telecomunicaciones']);
    }

    public function down(): void
    {
        // No se revierte para no alterar tickets, bitacoras ni asignaciones historicas.
    }

    private function syncCategory(string $slug, string $name, int $departmentId): void
    {
        DB::table('categories')->updateOrInsert(
            ['slug' => $slug, 'department_id' => $departmentId],
            [
                'name' => $name,
                'slug' => $slug,
                'department_id' => $departmentId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
};
