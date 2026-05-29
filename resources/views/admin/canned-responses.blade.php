@extends('layouts.app')

@section('title', 'Respuestas Predefinidas')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Respuestas Predefinidas</h1>
        <button onclick="showCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-plus mr-2"></i>Nueva Respuesta
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Título</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Atajo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contenido</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo de soporte</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($responses as $response)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $response->title }}</td>
                        <td class="px-6 py-4"><code class="bg-gray-100 px-2 py-1 rounded">/{{ $response->shortcut }}</code></td>
                        <td class="px-6 py-4 text-sm">{{ Str::limit($response->content, 60) }}</td>
                        <td class="px-6 py-4 text-sm">{{ $response->department->name ?? 'Global' }}</td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('admin.canned.delete', $response) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('¿Eliminar esta respuesta?')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="respModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Nueva Respuesta Predefinida</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.canned.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Título *</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Atajo *</label>
                <input type="text" name="shortcut" required placeholder="ej: gracias, saludo, etc" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Contenido *</label>
                <textarea name="content" required rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de soporte (opcional)</label>
                <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Global (todos los tipos)</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showCreateModal() {
        document.getElementById('respModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('respModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
