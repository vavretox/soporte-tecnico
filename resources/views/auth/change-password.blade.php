@extends('layouts.app')

@section('title', 'Cambiar contrasena')

@section('content')
<div class="max-w-md mx-auto px-4">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Cambia tu contrasena</h1>
            <p class="text-sm text-gray-600 mt-1">Por seguridad debes crear una contrasena propia antes de continuar.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-700 font-medium mb-2">Contrasena actual</label>
                <input type="password" name="current_password" required autofocus
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Nueva contrasena</label>
                <input type="password" name="password" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Confirma nueva contrasena</label>
                <input type="password" name="password_confirmation" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold hover:bg-blue-700">
                Guardar y continuar
            </button>
        </form>
    </div>
</div>
@endsection
