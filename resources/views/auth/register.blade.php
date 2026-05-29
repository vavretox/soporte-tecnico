@extends('layouts.app')

@section('title', 'Crear Cuenta')

@section('content')
<div class="max-w-lg mx-auto px-4">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-2">Crear cuenta</h1>
        <p class="text-sm text-gray-600 mb-6">Registrate para levantar tickets y conversar con soporte. Todos los campos son obligatorios.</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-gray-700 font-medium mb-2">Nombre completo *</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                    placeholder="Ejemplo: Juan Perez"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Correo institucional *</label>
                <div class="flex rounded-lg border border-gray-300 bg-white focus-within:border-blue-500">
                    <input type="text" name="email_prefix" value="{{ old('email_prefix') }}" required
                        pattern="[A-Za-z]+\.[A-Za-z]+"
                        inputmode="email"
                        autocomplete="username"
                        placeholder="juan.perez"
                        class="min-w-0 flex-1 rounded-l-lg px-3 py-2 focus:outline-none">
                    <span class="inline-flex items-center rounded-r-lg border-l border-gray-300 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-700">@tarija.gob.bo</span>
                </div>
                <p class="mt-2 text-xs text-gray-500">
                    Usa tu primer nombre, un punto y tu primer apellido. Ejemplo: juan.perez@tarija.gob.bo.
                </p>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Oficina *</label>
                <select name="office_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                    <option value="">Selecciona tu oficina</option>
                    @foreach($offices as $office)
                        <option value="{{ $office->id }}" @selected(old('office_id') == $office->id)>{{ $office->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Contrasena *</label>
                <input type="password" name="password" required minlength="8"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Confirmar contrasena *</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                Crear cuenta
            </button>
        </form>

        <div class="mt-6 text-sm text-gray-600">
            Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800">Ingresar</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelector('input[name="email_prefix"]')?.addEventListener('input', function () {
        this.value = this.value.toLowerCase().replace(/\s+/g, '');
    });
</script>
@endpush
