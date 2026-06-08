@extends('layouts.app')

@section('title', 'Ingresar')

@section('content')
<div class="max-w-md mx-auto px-4">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-2">INGRESAR AL SISTEMA</h1>
        <p class="text-sm text-gray-600 mb-6">Accede para crear tickets, responder mensajes y revisar el estado de tus solicitudes.</p>

        <form method="POST" action="{{ secure_url('/login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-700 font-medium mb-2">Correo</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Contrasena</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <label class="flex items-center text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1" class="mr-2">
                Mantener sesion iniciada
            </label>
            <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                Ingresar
            </button>
        </form>

        @if(config('auth.allow_registration'))
            <div class="mt-6 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                <p class="font-semibold">Eres funcionario y necesitas acceso?</p>
                <p class="mt-1 text-blue-800">Puedes crear tu cuenta desde el registro para ingresar solicitudes de soporte.</p>
                <a href="{{ route('register') }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-700">
                    <i class="fas fa-user-plus"></i>
                    Registrarme como funcionario
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
