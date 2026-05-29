@extends('layouts.app')

@section('title', 'Ingresar')

@section('content')
<div class="max-w-md mx-auto px-4">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-2">Ingresar al soporte</h1>
        <p class="text-sm text-gray-600 mb-6">Accede para crear tickets, responder mensajes y revisar el estado de tus solicitudes.</p>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
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

        <div class="mt-6 text-sm text-gray-600">
            No tienes cuenta?
            <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-800">Registrate</a>
        </div>

        @env('local')
            <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-600">
                Demo: administrador admin@helpdesk.com / password, soporte soporte@helpdesk.com / password, funcionario user@demo.com / password
            </div>
        @endenv
    </div>
</div>
@endsection
