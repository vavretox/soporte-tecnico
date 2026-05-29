@extends('layouts.app')

@section('title', 'Telegram')

@section('content')
<div class="max-w-3xl mx-auto px-4">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Notificaciones por Telegram</h1>
        <p class="text-sm text-gray-500 mt-1">Vincula tu cuenta para recibir avisos de tickets y mensajes.</p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 space-y-5">
        @if(! $telegramEnabled)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                Falta configurar el token del bot en el archivo .env.
            </div>
        @endif

        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 p-4">
            <div>
                <p class="font-semibold text-gray-900">Estado</p>
                <p class="text-sm text-gray-500">
                    @if($user->telegram_chat_id)
                        Vinculado al chat {{ $user->telegram_chat_id }}
                    @else
                        No vinculado
                    @endif
                </p>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $user->telegram_chat_id ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                {{ $user->telegram_chat_id ? 'Activo' : 'Pendiente' }}
            </span>
        </div>

        <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
            <p class="text-sm font-semibold text-blue-900">Codigo de vinculacion</p>
            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                <code class="inline-flex rounded-lg bg-white px-4 py-3 text-lg font-bold tracking-wide text-blue-700 shadow-sm">{{ $user->telegram_link_code }}</code>
                <form method="POST" action="{{ route('telegram.regenerate') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-white px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                        <i class="fas fa-rotate"></i>Nuevo codigo
                    </button>
                </form>
            </div>
            <p class="mt-3 text-sm text-blue-900">
                Envia este codigo al bot de Telegram. Luego pulsa Verificar codigo.
            </p>
            @if($botUsername)
                <a href="https://t.me/{{ ltrim($botUsername, '@') }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-sky-600 px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                    <i class="fab fa-telegram-plane"></i>Abrir bot
                </a>
            @endif
        </div>

        <div class="flex flex-wrap gap-3">
            <form method="POST" action="{{ route('telegram.sync') }}">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                    <i class="fas fa-check"></i>Verificar codigo
                </button>
            </form>

            @if($user->telegram_chat_id)
                <form method="POST" action="{{ route('telegram.disconnect') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 font-semibold text-red-700 hover:bg-red-100">
                        <i class="fas fa-unlink"></i>Desvincular
                    </button>
                </form>
            @endif
        </div>

        <div class="rounded-lg border border-gray-200 p-4 text-sm text-gray-600">
            Por seguridad, la vinculacion solo se completa cuando el bot recibe tu codigo vigente.
        </div>
    </div>
</div>
@endsection
