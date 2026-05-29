# Sistema de Soporte Técnico

Aplicación Helpdesk en Laravel 11 con PHP, MySQL, Blade, Tailwind CDN y autenticación propia.

## Funcionalidad

- Registro e inicio de sesión.
- Usuarios crean tickets por departamento, categoría, prioridad y mensaje.
- Conversación tipo chat entre usuario y soporte.
- Panel administrativo para soporte/agentes.
- Asignación de tickets a agentes.
- Cambio de estados: abierto, en progreso, resuelto y cerrado.
- Gestión de departamentos, categorías, agentes y respuestas predefinidas.

## Instalación local en Laragon

1. Crear la base de datos MySQL:

```sql
CREATE DATABASE soporte_tecnico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Revisar `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=soporte_tecnico
DB_USERNAME=root
DB_PASSWORD=
```

3. Ejecutar migraciones y datos iniciales:

```bash
C:\laragon\bin\php\php-8.5.1-nts-Win32-vs17-x64\php.exe artisan migrate:fresh --seed
```

4. Levantar servidor:

```bash
C:\laragon\bin\php\php-8.5.1-nts-Win32-vs17-x64\php.exe artisan serve
```

## Credenciales Demo

- Admin: `admin@helpdesk.com` / `password`
- Agente: `agent@helpdesk.com` / `password`
- Usuario: `user@demo.com` / `password`

## Notas De Diseño

El flujo está inspirado en patrones comunes de plataformas helpdesk libres como osTicket y Zammad: ticket como hilo de conversación, asignación a agente, estados claros, respuestas rápidas y organización por departamentos/categorías.
