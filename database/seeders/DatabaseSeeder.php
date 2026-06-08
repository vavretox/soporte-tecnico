<?php

namespace Database\Seeders;

use App\Models\CannedResponse;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Department;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $secretaria = Office::updateOrCreate([
            'slug' => 'secretaria-general',
        ], [
            'name' => 'Secretaria General',
            'type' => 'secretaria',
            'description' => 'Oficina principal de coordinacion.',
            'location' => 'Edificio principal',
            'email' => 'secretaria@example.com',
            'is_active' => true,
        ]);

        $direccion = Office::updateOrCreate([
            'slug' => 'direccion-de-tecnologia',
        ], [
            'name' => 'Direccion de Tecnologia',
            'type' => 'direccion',
            'parent_id' => $secretaria->id,
            'description' => 'Direccion responsable de plataformas y soporte.',
            'location' => 'Edificio principal, area de tecnologia',
            'email' => 'tecnologia@example.com',
            'is_active' => true,
        ]);

        if (app()->environment(['local', 'testing'])) {
            User::updateOrCreate([
                'email' => 'admin@helpdesk.com',
            ], [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'office_id' => $secretaria->id,
                'is_active' => true,
            ]);
        } elseif (filled(env('INITIAL_ADMIN_EMAIL')) && filled(env('INITIAL_ADMIN_PASSWORD'))) {
            User::updateOrCreate([
                'email' => env('INITIAL_ADMIN_EMAIL'),
            ], [
                'name' => env('INITIAL_ADMIN_NAME', 'Administrador'),
                'password' => Hash::make(env('INITIAL_ADMIN_PASSWORD')),
                'must_change_password' => true,
                'role' => 'admin',
                'office_id' => $secretaria->id,
                'is_active' => true,
            ]);
        }

        Asset::updateOrCreate([
            'asset_tag' => 'HW-0001',
        ], [
            'name' => 'Equipo de soporte demo',
            'type' => 'hardware',
            'brand' => 'Generico',
            'model' => 'Desktop',
            'status' => 'active',
            'office_id' => $direccion->id,
        ]);

        $supplier = Supplier::updateOrCreate([
            'rif' => 'J-00000000-0',
        ], [
            'name' => 'Proveedor Demo',
            'contact_name' => 'Contacto Demo',
            'email' => 'proveedor@example.com',
            'phone' => '0000-0000000',
            'is_active' => true,
        ]);

        if (app()->environment(['local', 'testing'])) {
            User::updateOrCreate([
                'email' => 'soporte@helpdesk.com',
            ], [
                'name' => 'Soporte Demo',
                'password' => Hash::make('password'),
                'role' => 'support',
                'office_id' => $direccion->id,
                'is_active' => true,
            ]);

            User::updateOrCreate([
                'email' => 'user@demo.com',
            ], [
                'name' => 'Funcionario Demo',
                'password' => Hash::make('password'),
                'role' => 'user',
                'office_id' => $direccion->id,
                'is_active' => true,
            ]);
        }

        $infraestructura = Department::updateOrCreate([
            'slug' => 'infraestructura-y-telecomunicaciones',
        ], [
            'name' => 'Infraestructura y Telecomunicaciones',
            'description' => 'Gestion de infraestructura tecnologica, enlaces, comunicaciones, servidores, cableado estructurado y continuidad de servicios.',
            'is_active' => true,
        ]);

        $sistemas = Department::updateOrCreate([
            'slug' => 'sistemas-y-base-de-datos',
        ], [
            'name' => 'Sistemas y Base de Datos',
            'description' => 'Atencion de sistemas institucionales, usuarios, permisos, errores de aplicacion, consultas y administracion de bases de datos.',
            'is_active' => true,
        ]);

        $soporte = Department::updateOrCreate([
            'slug' => 'soporte-tecnico-y-redes',
        ], [
            'name' => 'Soporte Tecnico y Redes',
            'description' => 'Soporte a equipos, impresoras, perifericos, conectividad de usuario, WiFi, red local y atencion tecnica de primer nivel.',
            'is_active' => true,
        ]);

        $supportDemo = User::where('email', 'soporte@helpdesk.com')->first();
        $supportDemo?->update([
            'department_id' => $soporte->id,
        ]);
        $supportDemo?->supportDepartments()->sync([$soporte->id]);

        Category::updateOrCreate([
            'slug' => 'problemas-acceso',
        ], [
            'name' => 'Problemas de Acceso',
            'department_id' => $sistemas->id,
        ]);

        Category::updateOrCreate([
            'slug' => 'error-sistema',
        ], [
            'name' => 'Error en Sistema',
            'department_id' => $sistemas->id,
        ]);

        Category::updateOrCreate([
            'slug' => 'falla-conexion',
        ], [
            'name' => 'Falla de Conexion',
            'department_id' => $soporte->id,
        ]);

        Category::updateOrCreate([
            'slug' => 'infraestructura-tecnologica',
        ], [
            'name' => 'Infraestructura Tecnologica',
            'department_id' => $infraestructura->id,
        ]);

        CannedResponse::updateOrCreate([
            'shortcut' => 'gracias',
        ], [
            'title' => 'Gracias por contactarnos',
            'content' => "Gracias por contactar con nuestro equipo de soporte.\n\nHemos recibido tu ticket y nos pondremos en contacto contigo lo antes posible.\n\nNumero de ticket: #{ticket_id}",
        ]);

        CannedResponse::updateOrCreate([
            'shortcut' => 'resuelto',
        ], [
            'title' => 'Ticket Resuelto',
            'content' => "Hemos verificado que tu problema ha sido resuelto.\n\nProcederemos a cerrar este ticket. Si vuelves a tener el mismo problema, puedes reabrir el ticket respondiendo a este mensaje.\n\nGracias por tu paciencia.",
        ]);

        CannedResponse::updateOrCreate([
            'shortcut' => 'info',
        ], [
            'title' => 'Solicitar mas informacion',
            'content' => "Para poder ayudarte mejor, necesitamos mas informacion:\n\n1. En que sistema operativo ocurre el problema?\n2. Has probado algun paso para solucionarlo?\n3. Podrias adjuntar una captura de pantalla del error?\n\nEsperamos tu respuesta.",
        ]);
    }
}
