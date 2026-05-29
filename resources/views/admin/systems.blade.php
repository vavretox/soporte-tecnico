@extends('layouts.app')

@section('title', 'Sistema')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Sistema</h1>
            <p class="text-sm text-gray-500 mt-1">Usuarios, cargo y usuario por cada sistema registrado como software en inventario.</p>
        </div>
        <button onclick="showCreateSystemModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-plus mr-2"></i>Nuevo Registro
        </button>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <input name="q" value="{{ request('q') }}" placeholder="Buscar usuario o sistema..." class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-3">
        <div class="flex gap-3">
            <button class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">Filtrar</button>
            <a href="{{ route('admin.systems') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sistemas y usuarios</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($records as $record)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="font-medium">{{ $record->responsible }}</div>
                                <div class="text-xs text-gray-500">{{ $record->position }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @forelse($record->systems as $system)
                                    <div class="mb-2">
                                        <span class="font-medium">{{ $system->name }}</span>
                                        @if($system->version)<span class="text-xs text-gray-500">v{{ $system->version }}</span>@endif
                                        <div class="text-xs text-gray-500">Usuario: {{ $system->pivot->username ?: 'Sin usuario registrado' }}</div>
                                    </div>
                                @empty
                                    Sin sistemas seleccionados
                                @endforelse
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    data-responsible="{{ e($record->responsible) }}"
                                    data-position="{{ e($record->position) }}"
                                    data-systems='@json($record->systems->map(fn ($system) => ['name' => $system->name, 'version' => $system->version, 'username' => $system->pivot->username])->values()->all())'
                                    data-notes="{{ e($record->notes) }}"
                                    onclick="viewSystem(this.dataset)"
                                    class="text-green-600 hover:text-green-800 mr-3"><i class="fas fa-eye"></i></button>
                                <button
                                    data-id="{{ $record->id }}"
                                    data-responsible="{{ e($record->responsible) }}"
                                    data-position="{{ e($record->position) }}"
                                    data-asset-ids='@json($record->systems->pluck('id')->all())'
                                    data-asset-users='@json($record->systems->mapWithKeys(fn ($system) => [$system->id => $system->pivot->username])->all())'
                                    data-notes="{{ e($record->notes) }}"
                                    onclick="editSystem(this.dataset)"
                                    class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="{{ route('admin.systems.delete', $record) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar este registro de sistema?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500">No hay registros de sistema.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $records->appends(request()->query())->links() }}</div>
    </div>
</div>

<div id="systemViewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Detalle de Sistema</h3>
            <button onclick="closeSystemViewModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div id="systemViewContent" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm"></div>
        <div class="flex justify-end mt-6">
            <button type="button" onclick="closeSystemViewModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cerrar</button>
        </div>
    </div>
</div>

<div id="systemModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-8 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="systemModalTitle" class="text-lg font-medium">Nuevo Registro de Sistema</h3>
            <button onclick="closeSystemModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="systemForm" method="POST">
            @csrf
            <input type="hidden" id="systemMethod" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input name="responsible" id="systemResponsible" required placeholder="Usuario *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="position" id="systemPosition" required placeholder="Cargo *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <div class="md:col-span-2 rounded-lg border border-gray-200 p-4">
                    <div class="mb-3 text-sm font-medium text-gray-700">Nombre del sistema</div>
                    @if($softwareAssets->isEmpty())
                        <div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">No hay activos de tipo Software activos en inventario.</div>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($softwareAssets as $asset)
                                <div class="rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="asset_ids[]" value="{{ $asset->id }}" class="system-asset-checkbox" onchange="syncSystemUserInputs()">
                                        <span>{{ $asset->name }}</span>
                                        @if($asset->version)<span class="text-xs text-gray-500">v{{ $asset->version }}</span>@endif
                                    </label>
                                    <input name="asset_users[{{ $asset->id }}]" data-asset-user="{{ $asset->id }}" placeholder="Usuario de {{ $asset->name }}" class="mt-2 hidden w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <textarea name="notes" id="systemNotes" rows="3" placeholder="Notas" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-2"></textarea>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeSystemModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showCreateSystemModal() {
    systemModalTitle.innerText = 'Nuevo Registro de Sistema';
    systemForm.action = "{{ route('admin.systems.store') }}";
    systemMethod.value = 'POST';
    ['systemResponsible','systemPosition','systemNotes'].forEach(id => document.getElementById(id).value = '');
    setCheckedValues('.system-asset-checkbox', []);
    setSystemAssetUsers({});
    systemModal.classList.remove('hidden');
}

function editSystem(record) {
    systemModalTitle.innerText = 'Editar Registro de Sistema';
    systemForm.action = `{{ url('/admin/systems') }}/${record.id}`;
    systemMethod.value = 'PUT';
    systemResponsible.value = record.responsible || '';
    systemPosition.value = record.position || '';
    systemNotes.value = record.notes || '';
    setCheckedValues('.system-asset-checkbox', JSON.parse(record.assetIds || '[]'));
    setSystemAssetUsers(JSON.parse(record.assetUsers || '{}'));
    systemModal.classList.remove('hidden');
}

function viewSystem(record) {
    const systems = JSON.parse(record.systems || '[]');
    const systemsHtml = systems.length
        ? systems.map(system => '<div class="rounded border border-gray-100 px-3 py-2"><span class="font-medium">' + escapeHtml(system.name || 'Sistema') + '</span>' + (system.version ? ' <span class="text-xs text-gray-500">v' + escapeHtml(system.version) + '</span>' : '') + '<br>Usuario: ' + escapeHtml(system.username || 'Sin usuario registrado') + '</div>').join('')
        : 'Sin sistemas seleccionados';

    systemViewContent.innerHTML = `
        ${systemDetailBlock('Usuario', record.responsible)}
        ${systemDetailBlock('Cargo', record.position)}
        <div class="md:col-span-2">${systemDetailBlock('Sistemas', systemsHtml)}</div>
        <div class="md:col-span-2">${systemDetailBlock('Notas', record.notes || 'Sin notas')}</div>
    `;
    systemViewModal.classList.remove('hidden');
}

function systemDetailBlock(label, value) {
    return '<div class="rounded-lg border border-gray-200 p-3"><div class="text-xs font-semibold uppercase text-gray-500">' + label + '</div><div class="mt-1 text-gray-900">' + value + '</div></div>';
}

function escapeHtml(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function closeSystemViewModal() { systemViewModal.classList.add('hidden'); }

function setCheckedValues(selector, values) {
    const normalized = values.map(String);
    document.querySelectorAll(selector).forEach(function(input) {
        input.checked = normalized.includes(input.value);
    });

    syncSystemUserInputs();
}

function setSystemAssetUsers(users) {
    document.querySelectorAll('[data-asset-user]').forEach(function(input) {
        input.value = users[input.dataset.assetUser] || '';
    });

    syncSystemUserInputs();
}

function syncSystemUserInputs() {
    document.querySelectorAll('.system-asset-checkbox').forEach(function(checkbox) {
        var input = document.querySelector('[data-asset-user="' + checkbox.value + '"]');

        if (!input) {
            return;
        }

        input.classList.toggle('hidden', !checkbox.checked);
        input.required = checkbox.checked;

        if (!checkbox.checked) {
            input.value = '';
        }
    });
}

function closeSystemModal() { systemModal.classList.add('hidden'); }
</script>
@endpush
@endsection
