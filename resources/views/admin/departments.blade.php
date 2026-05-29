@extends('layouts.app')

@section('title', 'Tipos de Soporte')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Tipos de Soporte</h1>
        <button onclick="showCreateModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-plus mr-2"></i>Nuevo Tipo
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripcion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tickets</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bitacoras</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($departments as $dept)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $dept->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ Str::limit($dept->description, 50) }}</td>
                        <td class="px-6 py-4 text-sm">{{ $dept->email ?? 'No aplica' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $dept->tickets_count }}</td>
                        <td class="px-6 py-4 text-sm">{{ $dept->bitacoras_count }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full {{ $dept->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $dept->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <button
                                data-id="{{ $dept->id }}"
                                data-name="{{ e($dept->name) }}"
                                data-description="{{ e($dept->description) }}"
                                data-email="{{ e($dept->email) }}"
                                data-active="{{ $dept->is_active ? '1' : '0' }}"
                                onclick="editDepartment(this.dataset)"
                                class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.departments.delete', $dept) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar este tipo de soporte?')" title="Eliminar">
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

<div id="deptModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-medium">Nuevo Tipo de Soporte</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="deptForm" method="POST">
            @csrf
            <input type="hidden" id="method" name="_method" value="POST">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                <input type="text" name="name" id="deptName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion *</label>
                <textarea name="description" id="deptDesc" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo</label>
                <input type="email" name="email" id="deptCorreo" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div class="mb-4">
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" id="deptActive" value="1" class="mr-2">
                    <span class="text-sm text-gray-700">Activo</span>
                </label>
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
        document.getElementById('modalTitle').innerText = 'Nuevo Tipo de Soporte';
        document.getElementById('deptForm').action = "{{ route('admin.departments.store') }}";
        document.getElementById('method').value = 'POST';
        document.getElementById('deptName').value = '';
        document.getElementById('deptDesc').value = '';
        document.getElementById('deptCorreo').value = '';
        document.getElementById('deptActive').checked = true;
        document.getElementById('deptModal').classList.remove('hidden');
    }

    function editDepartment(department) {
        document.getElementById('modalTitle').innerText = 'Editar Tipo de Soporte';
        document.getElementById('deptForm').action = `{{ url('/admin/departments') }}/${department.id}`;
        document.getElementById('method').value = 'PUT';
        document.getElementById('deptName').value = department.name || '';
        document.getElementById('deptDesc').value = department.description || '';
        document.getElementById('deptCorreo').value = department.email || '';
        document.getElementById('deptActive').checked = department.active === '1';
        document.getElementById('deptModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('deptModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
