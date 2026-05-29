<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Soporte Tecnico')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root {
            --ui-ease: cubic-bezier(.2, .8, .2, 1);
            --surface: rgba(255, 255, 255, .94);
            --line: #e5e7eb;
            --ink-soft: #64748b;
            --brand: #16803a;
            --brand-strong: #11632e;
        }

        @keyframes pageIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform: translateY(12px) scale(.98);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateX(18px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .app-shell a,
        .app-shell button,
        .app-shell input,
        .app-shell select,
        .app-shell textarea {
            transition:
                background-color .18s var(--ui-ease),
                border-color .18s var(--ui-ease),
                color .18s var(--ui-ease),
                box-shadow .18s var(--ui-ease),
                transform .18s var(--ui-ease);
        }

        body.app-shell {
            background:
                linear-gradient(180deg, #f8fafc 0%, #eef7f1 46%, #f8fafc 100%);
            min-height: 100vh;
        }

        ::selection {
            background: #bbf7d0;
            color: #052e16;
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: #86efac #f1f5f9;
        }

        *::-webkit-scrollbar {
            height: 10px;
            width: 10px;
        }

        *::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        *::-webkit-scrollbar-thumb {
            background: #86efac;
            border: 2px solid #f1f5f9;
            border-radius: 999px;
        }

        .app-shell nav {
            position: sticky;
            top: 0;
            z-index: 40;
            background: var(--surface);
            backdrop-filter: blur(14px);
        }

        .app-shell nav.is-scrolled {
            box-shadow: 0 14px 30px rgba(15, 23, 42, .08);
        }

        .scroll-progress {
            position: fixed;
            left: 0;
            top: 0;
            z-index: 60;
            height: 3px;
            width: 0;
            background: linear-gradient(90deg, #22c55e, #14b8a6);
            box-shadow: 0 0 18px rgba(34, 197, 94, .35);
        }

        .app-shell button:hover,
        .app-shell a[class*="bg-"]:hover,
        .app-shell a[class*="border"]:hover {
            transform: translateY(-1px);
        }

        .app-shell button:active,
        .app-shell a:active {
            transform: translateY(0);
        }

        .app-sidebar a,
        .app-sidebar button {
            position: relative;
            min-height: 42px;
        }

        .app-sidebar a:hover,
        .app-sidebar button:hover {
            box-shadow: 0 8px 22px rgba(37, 99, 235, .08);
        }

        .app-sidebar a[class*="bg-blue-600"]::before,
        .app-sidebar button[class*="bg-blue-600"]::before {
            content: "";
            position: absolute;
            left: -9px;
            top: 10px;
            bottom: 10px;
            width: 3px;
            border-radius: 999px;
            background: #22c55e;
        }

        .app-page {
            animation: pageIn .26s var(--ui-ease) both;
        }

        .app-page h1 {
            position: relative;
            letter-spacing: 0;
        }

        .app-page h1::after {
            content: "";
            display: block;
            height: 3px;
            width: 48px;
            margin-top: .45rem;
            border-radius: 999px;
            background: linear-gradient(90deg, #22c55e, #14b8a6);
        }

        .app-page .bg-white.rounded-lg.shadow {
            transition: box-shadow .2s var(--ui-ease), transform .2s var(--ui-ease);
        }

        .app-page .bg-white.rounded-lg.shadow:hover {
            box-shadow: 0 14px 35px rgba(15, 23, 42, .08);
        }

        .app-page table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .app-page .overflow-x-auto {
            scrollbar-gutter: stable;
        }

        .app-page th {
            letter-spacing: .02em;
        }

        .app-page tbody tr:nth-child(even) {
            background: rgba(248, 250, 252, .62);
        }

        .app-page tbody tr {
            transition: background-color .16s var(--ui-ease);
        }

        .app-page tbody tr:hover {
            background: #f0fdf4;
        }

        .app-page td[colspan] {
            background:
                radial-gradient(circle at center top, rgba(34, 197, 94, .08), transparent 38%),
                #fff;
        }

        .app-page td form.inline,
        .app-page td button,
        .app-page td a[title] {
            vertical-align: middle;
        }

        .app-page td button:not([class*="px-"]),
        .app-page td a[title] {
            display: inline-flex;
            height: 2rem;
            width: 2rem;
            align-items: center;
            justify-content: center;
            border-radius: .5rem;
        }

        .app-page td button:not([class*="px-"]):hover,
        .app-page td a[title]:hover {
            background: rgba(15, 23, 42, .06);
        }

        .app-page input,
        .app-page select,
        .app-page textarea {
            background: rgba(255, 255, 255, .98);
            outline: none;
        }

        .app-page input:required,
        .app-page select:required,
        .app-page textarea:required {
            border-left-width: 3px;
            border-left-color: #bbf7d0;
        }

        .app-page input:invalid:not(:placeholder-shown),
        .app-page textarea:invalid:not(:placeholder-shown) {
            border-color: #fca5a5;
            box-shadow: 0 0 0 3px rgba(248, 113, 113, .12);
        }

        .app-page input:focus,
        .app-page select:focus,
        .app-page textarea:focus {
            box-shadow: 0 0 0 3px rgba(34, 163, 74, .16);
        }

        .fixed.inset-0:not(.hidden) {
            backdrop-filter: blur(6px);
        }

        .fixed.inset-0:not(.hidden) > .relative {
            animation: modalIn .22s var(--ui-ease) both;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;
        }

        .fixed.inset-0:not(.hidden) > .relative::-webkit-scrollbar {
            width: 8px;
        }

        #flashToast {
            animation: toastIn .24s var(--ui-ease) both;
        }

        #backToTop {
            opacity: 0;
            pointer-events: none;
            transform: translateY(10px);
        }

        #backToTop.is-visible {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .app-page .rounded-lg.border:not(table .rounded-lg) {
            border-color: var(--line);
        }

        .app-page label {
            color: #334155;
        }

        .app-page .shadow {
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        }

        @media (max-width: 768px) {
            .app-page {
                padding-top: 1rem;
            }

            .app-page table {
                min-width: 760px;
            }

            .app-shell nav .text-lg {
                font-size: 1rem;
            }
        }

        .bg-blue-50 { background-color: #f0fdf4 !important; }
        .bg-blue-100 { background-color: #dcfce7 !important; }
        .bg-blue-500 { background-color: #22a34a !important; }
        .bg-blue-600 { background-color: #16803a !important; }
        .bg-blue-700 { background-color: #11632e !important; }
        .hover\:bg-blue-50:hover { background-color: #f0fdf4 !important; }
        .hover\:bg-blue-100:hover { background-color: #dcfce7 !important; }
        .hover\:bg-blue-600:hover { background-color: #16803a !important; }
        .hover\:bg-blue-700:hover { background-color: #11632e !important; }
        .text-blue-500 { color: #22a34a !important; }
        .text-blue-600 { color: #16803a !important; }
        .text-blue-700 { color: #11632e !important; }
        .text-blue-800 { color: #0f4d27 !important; }
        .text-blue-900 { color: #0b3d20 !important; }
        .hover\:text-blue-700:hover { color: #11632e !important; }
        .hover\:text-blue-800:hover { color: #0f4d27 !important; }
        .border-blue-100 { border-color: #dcfce7 !important; }
        .border-blue-200 { border-color: #bbf7d0 !important; }
        .border-blue-600 { border-color: #16803a !important; }
        .hover\:border-blue-200:hover { border-color: #bbf7d0 !important; }
        .focus\:border-blue-500:focus,
        .focus-within\:border-blue-500:focus-within {
            border-color: #22a34a !important;
        }

        .avatar-picker[open] summary {
            border-color: #bbf7d0;
            background: #f0fdf4;
        }

        .avatar-menu {
            animation: modalIn .18s var(--ui-ease) both;
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                scroll-behavior: auto !important;
                transition-duration: .01ms !important;
            }
        }
    </style>
    @auth
        <script>
            window.helpdeskUser = {
                id: {{ auth()->id() }},
                isAgent: @json(auth()->user()->isAgent()),
            };
            window.helpdeskRoutes = {
                notifications: @json(url('/notifications/messages')),
                tickets: @json(url('/tickets')),
                adminTickets: @json(url('/admin/tickets')),
            };
        </script>
    @endauth
    @stack('styles')
    @vite(['resources/js/app.js'])
</head>
<body class="app-shell min-h-screen bg-gray-100 text-gray-900">
    <div id="scrollProgress" class="scroll-progress"></div>
    <nav class="bg-white border-b border-gray-200 shadow-sm">
        <div class="px-4 py-3">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <a href="{{ auth()->check() ? (auth()->user()->isAgent() ? route('admin.dashboard') : route('tickets.index')) : route('login') }}" class="inline-flex items-center gap-3 font-bold text-lg text-emerald-800 hover:text-green-700">
                    @if(file_exists(public_path('images/logo-gadt.png')))
                        <img src="{{ asset('images/logo-gadt.png') }}" alt="Gobierno Autonomo Departamental de Tarija" class="h-11 w-auto object-contain">
                    @endif
                    <span class="leading-tight">Sistema de Helpdesk Soporte Tecnico DTI GADT</span>
                </a>

                <div class="flex flex-wrap items-center gap-2 text-sm">
                    @auth
                        @php
                            $roleLabel = match (auth()->user()->role) {
                                'admin' => 'Administrador',
                                'support' => 'Soporte',
                                'secretary_dti' => 'Secretaria DTI',
                                default => 'Funcionario',
                            };
                        @endphp
                        @php($avatarOptions = [
                            ['icon' => 'user', 'color' => 'green', 'class' => 'fa-user bg-green-100 text-green-700'],
                            ['icon' => 'headset', 'color' => 'teal', 'class' => 'fa-headset bg-teal-100 text-teal-700'],
                            ['icon' => 'laptop', 'color' => 'amber', 'class' => 'fa-laptop-code bg-amber-100 text-amber-700'],
                            ['icon' => 'shield', 'color' => 'slate', 'class' => 'fa-shield-halved bg-slate-100 text-slate-700'],
                            ['icon' => 'briefcase', 'color' => 'orange', 'class' => 'fa-briefcase bg-orange-100 text-orange-700'],
                            ['icon' => 'seedling', 'color' => 'rose', 'class' => 'fa-seedling bg-rose-100 text-rose-700'],
                        ])
                        <details class="avatar-picker relative">
                            <summary class="list-none inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 hover:border-green-200 hover:bg-green-50">
                                @if(auth()->user()->avatarUrl())
                                    <img src="{{ auth()->user()->avatarUrl() }}" alt="Avatar de {{ auth()->user()->name }}" class="h-9 w-9 rounded-full object-cover">
                                @else
                                    <span class="h-9 w-9 rounded-full {{ auth()->user()->avatarColorClasses() }} flex items-center justify-center text-sm">
                                        <i class="fas {{ auth()->user()->avatarIconClass() }}"></i>
                                    </span>
                                @endif
                                <span class="leading-tight text-left">
                                    <span class="block font-semibold text-gray-900">{{ auth()->user()->name }}</span>
                                    <span class="block text-xs text-gray-500">{{ $roleLabel }}</span>
                                </span>
                                <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                            </summary>
                            <div class="avatar-menu absolute right-0 top-full z-50 mt-2 w-72 rounded-lg border border-gray-200 bg-white p-4 shadow-xl">
                                <p class="text-sm font-semibold text-gray-900">Elige tu avatar</p>
                                <p class="mt-1 text-xs text-gray-500">Se mostrara en tu sesion y en la barra superior.</p>
                                <form method="POST" action="{{ route('profile.avatar') }}" class="mt-3">
                                    @csrf
                                    <input type="hidden" name="avatar_icon" id="avatarIconInput" value="{{ auth()->user()->avatar_icon ?? 'user' }}">
                                    <input type="hidden" name="avatar_color" id="avatarColorInput" value="{{ auth()->user()->avatar_color ?? 'green' }}">
                                    <div class="grid grid-cols-3 gap-2">
                                        @foreach($avatarOptions as $option)
                                            <button type="submit"
                                                onclick="selectAvatar('{{ $option['icon'] }}', '{{ $option['color'] }}')"
                                                class="flex h-14 items-center justify-center rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50 {{ (auth()->user()->avatar_icon === $option['icon'] && auth()->user()->avatar_color === $option['color']) ? 'ring-2 ring-green-500' : '' }}"
                                                title="Usar este avatar">
                                                <span class="flex h-9 w-9 items-center justify-center rounded-full {{ $option['class'] }}">
                                                    <i class="fas {{ Str::before($option['class'], ' ') }}"></i>
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </form>
                            </div>
                        </details>
                        <form method="POST" action="{{ route('logout') }}" class="inline-flex">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-100 bg-red-50 px-3 py-2 font-medium text-red-700 hover:bg-red-100">
                                <i class="fas fa-right-from-bracket"></i>Salir
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 font-medium text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"><i class="fas fa-right-to-bracket"></i>Ingresar</a>
                        @if(config('auth.allow_registration'))
                            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 font-semibold text-white shadow-sm hover:bg-blue-700"><i class="fas fa-user-plus"></i>Crear cuenta</a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-[calc(100vh-73px)] lg:flex">
        @auth
            <aside class="app-sidebar bg-white border-b border-gray-200 lg:w-64 lg:border-b-0 lg:border-r">
                <div class="px-4 py-4 lg:sticky lg:top-0">
                    <div class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Menu</div>
                    <div class="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible lg:pb-0">
                        @if(auth()->user()->isAgent())
                            <a href="{{ route('admin.dashboard') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.dashboard') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-chart-line w-5"></i>Panel</a>
                        @endif
                        <a href="{{ route('tickets.index') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('tickets.index') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-ticket-alt w-5"></i>Mis tickets</a>
                        @if(auth()->user()->isAgent())
                            <a href="{{ route('admin.tickets') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.tickets', 'admin.ticket.show') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-inbox w-5"></i>Tickets</a>
                            <a href="{{ route('admin.bitacoras') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.bitacoras') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-clipboard-list w-5"></i>Bitacoras</a>
                            <a href="{{ route('admin.reports') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.reports') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-chart-pie w-5"></i>Reportes</a>
                            @if(auth()->user()->isManager())
                                <a href="{{ route('admin.assets') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.assets') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-boxes-stacked w-5"></i>Inventario</a>
                                <a href="{{ route('admin.suppliers') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.suppliers') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-truck-field w-5"></i>Proveedores</a>
                                <a href="{{ route('admin.changes') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.changes') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-code-branch w-5"></i>Cambios</a>
                                <a href="{{ route('admin.network') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.network') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-network-wired w-5"></i>Red</a>
                                <a href="{{ route('admin.systems') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.systems') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-desktop w-5"></i>Sistema</a>
                            @endif
                            @if(auth()->user()->isAdmin())
                                <a href="{{ route('admin.offices') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.offices') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-building w-5"></i>Oficinas</a>
                                <a href="{{ route('admin.departments') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.departments') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-layer-group w-5"></i>Tipos</a>
                                <a href="{{ route('admin.audit-logs') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.audit-logs') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-clock-rotate-left w-5"></i>Auditoria</a>
                                <a href="{{ route('admin.users') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.users') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-users-gear w-5"></i>Usuarios</a>
                            @endif
                        @endif
                        @unless(auth()->user()->isSecretaryDti())
                            <a href="{{ route('knowledge.index') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('knowledge.*') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-book-open w-5"></i>Conocimiento</a>
                        @endunless
                        <a href="{{ route('telegram.index') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('telegram.*') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fab fa-telegram-plane w-5"></i>Telegram</a>
                        @if(auth()->user()->isAgent())
                            <a href="{{ route('admin.knowledge') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg border px-3 py-2 font-medium {{ request()->routeIs('admin.knowledge') ? 'border-blue-600 bg-blue-600 text-white shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"><i class="fas fa-pen-to-square w-5"></i>Gestionar conocimiento</a>
                        @endif
                        <button type="button" id="enableNotifications" class="inline-flex min-w-0 items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-left font-medium text-gray-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 lg:w-full" title="Activar alertas de mensajes">
                            <i class="fas fa-bell w-5"></i>Alertas
                        </button>
                        @unless(auth()->user()->isAgent())
                            @if(auth()->user()->isSecretaryDti())
                                <a href="{{ route('tickets.physical.create') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 font-semibold text-white shadow-sm hover:bg-indigo-700">
                                    <i class="fas fa-file-circle-plus w-5"></i>Ticket fisico
                                </a>
                            @else
                            <a href="{{ route('tickets.create') }}" class="inline-flex min-w-max items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 font-semibold text-white shadow-sm hover:bg-blue-700">
                                <i class="fas fa-plus w-5"></i>Nuevo ticket
                            </a>
                            @endif
                        @endunless
                    </div>
                </div>
            </aside>
        @endauth

        <main class="app-page flex-1 py-8">
        @if(session('success') || session('error'))
            <div id="flashToast" class="fixed right-4 top-4 z-[9999] w-[calc(100%-2rem)] max-w-md rounded-lg border bg-white p-4 shadow-xl {{ session('success') ? 'border-green-200' : 'border-red-200' }}">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ session('success') ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
                        <i class="fas {{ session('success') ? 'fa-check' : 'fa-triangle-exclamation' }}"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="font-semibold text-gray-900">{{ session('success') ? 'Operacion completada' : 'Revisa la accion' }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ session('success') ?? session('error') }}</p>
                    </div>
                    <button type="button" onclick="closeFlashToast()" class="text-gray-400 hover:text-gray-600" title="Cerrar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="max-w-7xl mx-auto px-4 mb-4">
                <div class="bg-red-100 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @yield('content')
        </main>
    </div>

    <button type="button" id="backToTop" class="fixed bottom-5 right-5 z-50 inline-flex h-11 w-11 items-center justify-center rounded-full border border-green-200 bg-white text-green-700 shadow-lg hover:bg-green-50" title="Volver arriba">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    @stack('scripts')
    <script>
        function selectAvatar(icon, color) {
            document.getElementById('avatarIconInput').value = icon;
            document.getElementById('avatarColorInput').value = color;
        }

        function closeFlashToast() {
            document.getElementById('flashToast')?.remove();
        }

        if (document.getElementById('flashToast')) {
            setTimeout(closeFlashToast, 4500);
        }

        var appNav = document.querySelector('nav');
        var scrollProgress = document.getElementById('scrollProgress');
        var backToTop = document.getElementById('backToTop');

        function updateScrollUi() {
            var scrollTop = window.scrollY || document.documentElement.scrollTop;
            var height = document.documentElement.scrollHeight - window.innerHeight;
            var progress = height > 0 ? (scrollTop / height) * 100 : 0;

            scrollProgress.style.width = progress + '%';
            appNav?.classList.toggle('is-scrolled', scrollTop > 6);
            backToTop?.classList.toggle('is-visible', scrollTop > 420);
        }

        updateScrollUi();
        window.addEventListener('scroll', updateScrollUi, { passive: true });

        backToTop?.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        document.querySelector('.app-sidebar a[class*="bg-blue-600"]')?.scrollIntoView({
            block: 'nearest',
            inline: 'center',
        });

        function closeTopModal() {
            var openModals = Array.from(document.querySelectorAll('.fixed.inset-0:not(.hidden)'));
            var modal = openModals.pop();

            if (modal && modal.id !== 'flashToast') {
                modal.classList.add('hidden');
            }
        }

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeTopModal();
            }

            if (event.key === '/' && !event.ctrlKey && !event.metaKey && !event.altKey) {
                var active = document.activeElement;
                var isTyping = active instanceof HTMLElement && ['INPUT', 'TEXTAREA', 'SELECT'].includes(active.tagName);

                if (!isTyping) {
                    var searchInput = document.querySelector('.app-page input[name="q"], .app-page input[type="search"]');

                    if (searchInput instanceof HTMLInputElement) {
                        event.preventDefault();
                        searchInput.focus();
                        searchInput.select();
                    }
                }
            }
        });

        document.addEventListener('click', function(event) {
            var backdrop = event.target;

            if (backdrop instanceof HTMLElement && backdrop.matches('.fixed.inset-0')) {
                backdrop.classList.add('hidden');
            }
        });

        var modalObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                var target = mutation.target;

                if (!(target instanceof HTMLElement) || !target.matches('.fixed.inset-0') || target.classList.contains('hidden')) {
                    document.body.classList.toggle('overflow-hidden', Boolean(document.querySelector('.fixed.inset-0:not(.hidden)')));
                    return;
                }

                document.body.classList.add('overflow-hidden');
                window.setTimeout(function() {
                    var focusable = target.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])');
                    focusable?.focus({ preventScroll: true });
                }, 60);
            });
        });

        document.querySelectorAll('.fixed.inset-0').forEach(function(modal) {
            modalObserver.observe(modal, { attributes: true, attributeFilter: ['class'] });
        });

        document.addEventListener('submit', function(event) {
            if (event.defaultPrevented) {
                return;
            }

            var form = event.target;
            if (!(form instanceof HTMLFormElement) || form.dataset.noSubmitLock === 'true') {
                return;
            }

            var method = (form.getAttribute('method') || 'GET').toUpperCase();
            if (method === 'GET') {
                return;
            }

            if (form.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = 'true';

            var spoofedMethod = (form.querySelector('input[name="_method"]')?.value || method).toUpperCase();
            var loadingText = spoofedMethod === 'DELETE'
                ? 'Eliminando...'
                : (spoofedMethod === 'POST' ? 'Guardando...' : 'Actualizando...');

            form.querySelectorAll('button[type="submit"], button:not([type])').forEach(function(button) {
                button.dataset.originalHtml = button.innerHTML;
                if (!button.style.minWidth) {
                    button.style.minWidth = button.offsetWidth + 'px';
                }
                button.disabled = true;
                button.classList.add('opacity-60', 'cursor-not-allowed');

                if (!button.querySelector('i.fa-spinner')) {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>' + (button.dataset.loadingText || loadingText);
                }
            });
        }, false);
    </script>
</body>
</html>
