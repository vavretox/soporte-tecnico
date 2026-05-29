@extends('layouts.app')

@section('title', 'Auditoria')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Auditoria del sistema</h1>
            <p class="text-sm text-gray-500 mt-1">Historial de acciones importantes realizadas por los usuarios.</p>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <select name="action" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">Todas las acciones</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
            @endforeach
        </select>
        <div class="md:col-span-2 flex gap-3">
            <button class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">Filtrar</button>
            <a href="{{ route('admin.audit-logs') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Accion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Objeto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm">{{ $log->user?->name ?? 'Sistema' }}</td>
                            <td class="px-6 py-4 text-sm font-medium">{{ $log->action }}</td>
                            <td class="px-6 py-4 text-sm">{{ $log->description ?? 'Sin detalle' }}</td>
                            <td class="px-6 py-4 text-xs text-gray-500">
                                {{ class_basename($log->subject_type) ?: 'No aplica' }} {{ $log->subject_id ? '#'.$log->subject_id : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay acciones registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $logs->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection
