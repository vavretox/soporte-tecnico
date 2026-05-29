@extends('layouts.app')

@section('title', 'Gestionar Oficinas')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Gestionar Oficinas</h1>
            <p class="text-sm text-gray-500 mt-1">Estructura jerarquica: Secretarias, Direcciones, Unidades descentralizadas y Otros.</p>
        </div>
        <button onclick="showCreateOfficeModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-plus mr-2"></i>Nueva Oficina
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oficina</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Depende de</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicacion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuarios</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($officeTree as $node)
                        @php($office = $node['office'])
                        <tr>
                            <td class="px-6 py-4 font-medium">
                                <div style="padding-left: {{ $node['depth'] * 24 }}px">
                                    @if($node['depth'] > 0)
                                        <i class="fas fa-turn-up fa-rotate-90 text-gray-300 mr-2"></i>
                                    @endif
                                    {{ $office->name }}
                                    @if($office->description)
                                        <div class="text-xs text-gray-500 font-normal mt-1">{{ Str::limit($office->description, 70) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $office->typeLabel() }}</td>
                            <td class="px-6 py-4 text-sm">{{ $office->parent?->name ?? 'Nivel principal' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $office->location ?? 'Sin ubicacion' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $office->email ?? 'No aplica' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $office->users_count }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full {{ $office->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $office->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    data-id="{{ $office->id }}"
                                    data-name="{{ e($office->name) }}"
                                    data-type="{{ $office->type }}"
                                    data-parent-id="{{ $office->parent_id }}"
                                    data-description="{{ e($office->description) }}"
                                    data-location="{{ e($office->location) }}"
                                    data-email="{{ e($office->email) }}"
                                    data-active="{{ $office->is_active ? '1' : '0' }}"
                                    onclick="editOffice(this.dataset)"
                                    class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.offices.delete', $office) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar esta oficina?')" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay oficinas registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="officeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="officeModalTitle" class="text-lg font-medium">Nueva Oficina</h3>
            <button onclick="closeOfficeModal()" class="text-gray-400 hover:text-gray-600" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="officeForm" method="POST">
            @csrf
            <input type="hidden" id="officeMethod" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input type="text" name="name" id="officeName" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo *</label>
                    <select name="type" id="officeType" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @foreach($types as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Depende de</label>
                    <select name="parent_id" id="officeParent" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Nivel principal</option>
                        @foreach($officeOptions as $option)
                            <option value="{{ $option->id }}">{{ $option->name }} - {{ $option->typeLabel() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo *</label>
                    <input type="email" name="email" id="officeCorreo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ubicacion *</label>
                    <input type="text" name="location" id="officeLocation" required class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Ej: Edificio principal, piso 2, ala norte">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion *</label>
                <textarea name="description" id="officeDescription" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
            </div>
            <label class="flex items-center mt-4">
                <input type="checkbox" name="is_active" id="officeActive" value="1" class="mr-2">
                <span class="text-sm text-gray-700">Activa</span>
            </label>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeOfficeModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function showCreateOfficeModal() {
        document.getElementById('officeModalTitle').innerText = 'Nueva Oficina';
        document.getElementById('officeForm').action = "{{ route('admin.offices.store') }}";
        document.getElementById('officeMethod').value = 'POST';
        document.getElementById('officeName').value = '';
        document.getElementById('officeType').value = 'secretaria';
        document.getElementById('officeParent').value = '';
        document.getElementById('officeDescription').value = '';
        document.getElementById('officeLocation').value = '';
        document.getElementById('officeCorreo').value = '';
        document.getElementById('officeActive').checked = true;
        setParentOptions();
        document.getElementById('officeModal').classList.remove('hidden');
    }

    function editOffice(office) {
        document.getElementById('officeModalTitle').innerText = 'Editar Oficina';
        document.getElementById('officeForm').action = `{{ url('/admin/offices') }}/${office.id}`;
        document.getElementById('officeMethod').value = 'PUT';
        document.getElementById('officeName').value = office.name || '';
        document.getElementById('officeType').value = office.type || 'otro';
        document.getElementById('officeParent').value = office.parentId || '';
        document.getElementById('officeDescription').value = office.description || '';
        document.getElementById('officeLocation').value = office.location || '';
        document.getElementById('officeCorreo').value = office.email || '';
        document.getElementById('officeActive').checked = office.active === '1';
        setParentOptions(office.id);
        document.getElementById('officeModal').classList.remove('hidden');
    }

    function setParentOptions(currentId = null) {
        Array.from(document.getElementById('officeParent').options).forEach((option) => {
            option.disabled = currentId && option.value === String(currentId);
        });
    }

    function closeOfficeModal() {
        document.getElementById('officeModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
