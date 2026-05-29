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
        $secretaria = Office::create([
            'name' => 'Secretaria General',
            'slug' => 'secretaria-general',
            'type' => 'secretaria',
            'description' => 'Oficina principal de coordinacion.',
            'location' => 'Edificio principal',
            'email' => 'secretaria@example.com',
            'is_active' => true,
        ]);

        $direccion = Office::create([
            'name' => 'Direccion de Tecnologia',
            'slug' => 'direccion-de-tecnologia',
            'type' => 'direccion',
            'parent_id' => $secretaria->id,
            'description' => 'Direccion responsable de plataformas y soporte.',
            'location' => 'Edificio principal, area de tecnologia',
            'email' => 'tecnologia@example.com',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Administrador',
            'email' => 'admin@helpdesk.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'office_id' => $secretaria->id,
            'is_active' => true,
        ]);

        Asset::create([
            'asset_tag' => 'HW-0001',
            'name' => 'Equipo de soporte demo',
            'type' => 'hardware',
            'brand' => 'Generico',
            'model' => 'Desktop',
            'status' => 'active',
            'office_id' => $direccion->id,
        ]);

        $supplier = Supplier::create([
            'name' => 'Proveedor Demo',
            'rif' => 'J-00000000-0',
            'contact_name' => 'Contacto Demo',
            'email' => 'proveedor@example.com',
            'phone' => '0000-0000000',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Soporte Demo',
            'email' => 'soporte@helpdesk.com',
            'password' => Hash::make('password'),
            'role' => 'support',
            'office_id' => $direccion->id,
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Funcionario Demo',
            'email' => 'user@demo.com',
            'password' => Hash::make('password'),
            'role' => 'user',
            'office_id' => $direccion->id,
            'is_active' => true,
        ]);

        $infraestructura = Department::create([
            'name' => 'Infraestructura y Telecomunicaciones',
            'slug' => 'infraestructura-y-telecomunicaciones',
            'description' => 'Gestion de infraestructura tecnologica, enlaces, comunicaciones, servidores, cableado estructurado y continuidad de servicios.',
            'is_active' => true,
        ]);

        $sistemas = Department::create([
            'name' => 'Sistemas y Base de Datos',
            'slug' => 'sistemas-y-base-de-datos',
            'description' => 'Atencion de sistemas institucionales, usuarios, permisos, errores de aplicacion, consultas y administracion de bases de datos.',
            'is_active' => true,
        ]);

        $soporte = Department::create([
            'name' => 'Soporte Tecnico y Redes',
            'slug' => 'soporte-tecnico-y-redes',
            'description' => 'Soporte a equipos, impresoras, perifericos, conectividad de usuario, WiFi, red local y atencion tecnica de primer nivel.',
            'is_active' => true,
        ]);

        $supportDemo = User::where('email', 'soporte@helpdesk.com')->first();
        $supportDemo?->update([
            'department_id' => $soporte->id,
        ]);
        $supportDemo?->supportDepartments()->sync([$soporte->id]);

        Category::create([
            'name' => 'Problemas de Acceso',
            'slug' => 'problemas-acceso',
            'department_id' => $sistemas->id,
        ]);

        Category::create([
            'name' => 'Error en Sistema',
            'slug' => 'error-sistema',
            'department_id' => $sistemas->id,
        ]);

        Category::create([
            'name' => 'Falla de Conexion',
            'slug' => 'falla-conexion',
            'department_id' => $soporte->id,
        ]);

        Category::create([
            'name' => 'Infraestructura Tecnologica',
            'slug' => 'infraestructura-tecnologica',
            'department_id' => $infraestructura->id,
        ]);

        CannedResponse::create([
            'title' => 'Gracias por contactarnos',
            'shortcut' => 'gracias',
            'content' => "Gracias por contactar con nuestro equipo de soporte.\n\nHemos recibido tu ticket y nos pondremos en contacto contigo lo antes posible.\n\nNumero de ticket: #{ticket_id}",
        ]);

        CannedResponse::create([
            'title' => 'Ticket Resuelto',
            'shortcut' => 'resuelto',
            'content' => "Hemos verificado que tu problema ha sido resuelto.\n\nProcederemos a cerrar este ticket. Si vuelves a tener el mismo problema, puedes reabrir el ticket respondiendo a este mensaje.\n\nGracias por tu paciencia.",
        ]);

        CannedResponse::create([
            'title' => 'Solicitar mas informacion',
            'shortcut' => 'info',
            'content' => "Para poder ayudarte mejor, necesitamos mas informacion:\n\n1. En que sistema operativo ocurre el problema?\n2. Has probado algun paso para solucionarlo?\n3. Podrias adjuntar una captura de pantalla del error?\n\nEsperamos tu respuesta.",
        ]);
    }
}
