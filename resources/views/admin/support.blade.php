@extends('layouts.app')

@section('title', 'Gestionar Soporte')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <h1 class="text-2xl font-bold mb-6">Gestionar Personal de Soporte</h1>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow">
            <div class="border-b px-6 py-4">
                <h2 class="text-lg font-semibold">Soporte actual</h2>
            </div>
            <div class="p-6">
                @if($supportStaff->count() > 0)
                    <div class="space-y-3">
                        @foreach($supportStaff as $supportUser)
                            <div class="flex justify-between items-center p-3 border rounded-lg">
                                <div>
                                    <div class="font-medium">{{ $supportUser->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $supportUser->email }}</div>
                                    <div class="text-xs">
                                        <span class="px-2 py-0.5 rounded-full {{ $supportUser->role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $supportUser->role === 'admin' ? 'Administrador' : 'Soporte' }}
                                        </span>
                                    </div>
                                    <div class="mt-2 text-xs text-gray-500">
                                        @if($supportUser->role === 'admin')
                                            Atiende todos los tipos de soporte.
                                        @elseif($supportUser->supportDepartments->isNotEmpty())
                                            {{ $supportUser->supportDepartments->pluck('name')->join(', ') }}
                                        @else
                                            Sin especialidades asignadas.
                                        @endif
                                    </div>
                                </div>
                                @if($supportUser->role !== 'admin')
                                    <form method="POST" action="{{ route('admin.support.demote', $supportUser) }}">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm" onclick="return confirm('Degradar este usuario de soporte?')">
                                            <i class="fas fa-user-minus mr-1"></i>Degradar
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center">No hay personal de soporte registrado</p>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow">
            <div class="border-b px-6 py-4">
                <h2 class="text-lg font-semibold">Funcionarios para Promover</h2>
            </div>
            <div class="p-6">
                @if($users->count() > 0)
                    <div class="space-y-3">
                        @foreach($users as $user)
                            <div class="flex flex-col gap-3 p-3 border rounded-lg sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-medium">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    <div class="text-xs text-gray-400">Tickets: {{ $user->tickets->count() }}</div>
                                </div>
                                <form method="POST" action="{{ route('admin.support.promote', $user) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    @csrf
                                    <select name="department_ids[]" required multiple class="rounded-lg border border-gray-300 px-3 py-1 text-sm min-h-24">
                                        @foreach($departments as $department)
                                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded-lg text-sm hover:bg-blue-600">
                                        <i class="fas fa-user-plus mr-1"></i>Promover
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center">No hay funcionarios disponibles</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
