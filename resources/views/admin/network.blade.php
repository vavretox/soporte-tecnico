@extends('layouts.app')

@section('title', 'Red')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Red</h1>
            <p class="text-sm text-gray-500 mt-1">Usuarios, IP, MAC, conexion, dispositivos, hostname, correo y carpetas compartidas.</p>
        </div>
        <button onclick="showCreateNetworkModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-plus mr-2"></i>Nuevo Registro
        </button>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <input name="q" value="{{ request('q') }}" placeholder="Buscar usuario, IP, MAC, hostname..." class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-2">
        <select name="connection_type" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="all">Todas las conexiones</option>
            @foreach($connectionTypes as $value => $label)
                <option value="{{ $value }}" {{ request('connection_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <div class="flex gap-3">
            <button class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">Filtrar</button>
            <a href="{{ route('admin.network') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP / MAC</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Conexion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dispositivos</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hostname</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Carpetas</th>
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
                                @forelse($record->interfaceRows() as $interface)
                                    <div>{{ $interface['ip_address'] ?: 'Sin IP' }}</div>
                                    <div class="mb-1 text-xs text-gray-500">{{ $interface['mac_address'] ?: 'Sin MAC' }}</div>
                                @empty
                                    Sin IP/MAC
                                @endforelse
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @foreach($record->selectedConnectionTypes() as $type)
                                    <span class="mb-1 inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs text-blue-800">{{ $connectionTypes[$type] ?? $type }}</span>
                                @endforeach
                            </td>
                            <td class="px-6 py-4 text-sm">
                                Total: {{ $record->connected_devices }}<br>
                                Celulares: {{ $record->mobile_devices }} / PC: {{ $record->pc_devices }}
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $record->hostname ?? 'Sin hostname' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $record->has_email ? ($record->email ?? 'Si') : 'No' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $record->has_shared_folders ? Str::limit($record->shared_folders, 60) : 'No' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    data-responsible="{{ e($record->responsible) }}"
                                    data-position="{{ e($record->position) }}"
                                    data-hostname="{{ e($record->hostname) }}"
                                    data-connection-types='@json($record->selectedConnectionTypes())'
                                    data-network-interfaces='@json($record->interfaceRows())'
                                    data-connected-devices="{{ $record->connected_devices }}"
                                    data-mobile-devices="{{ $record->mobile_devices }}"
                                    data-pc-devices="{{ $record->pc_devices }}"
                                    data-has-email="{{ $record->has_email ? '1' : '0' }}"
                                    data-email="{{ e($record->email) }}"
                                    data-has-shared-folders="{{ $record->has_shared_folders ? '1' : '0' }}"
                                    data-shared-folders="{{ e($record->shared_folders) }}"
                                    data-notes="{{ e($record->notes) }}"
                                    onclick="viewNetwork(this.dataset)"
                                    class="text-green-600 hover:text-green-800 mr-3"><i class="fas fa-eye"></i></button>
                                <button
                                    data-id="{{ $record->id }}"
                                    data-responsible="{{ e($record->responsible) }}"
                                    data-position="{{ e($record->position) }}"
                                    data-ip-address="{{ e($record->ip_address) }}"
                                    data-mac-address="{{ e($record->mac_address) }}"
                                    data-connection-types='@json($record->selectedConnectionTypes())'
                                    data-network-interfaces='@json($record->interfaceRows())'
                                    data-connected-devices="{{ $record->connected_devices }}"
                                    data-mobile-devices="{{ $record->mobile_devices }}"
                                    data-pc-devices="{{ $record->pc_devices }}"
                                    data-hostname="{{ e($record->hostname) }}"
                                    data-has-email="{{ $record->has_email ? '1' : '0' }}"
                                    data-email="{{ e($record->email) }}"
                                    data-has-shared-folders="{{ $record->has_shared_folders ? '1' : '0' }}"
                                    data-shared-folders="{{ e($record->shared_folders) }}"
                                    data-notes="{{ e($record->notes) }}"
                                    onclick="editNetwork(this.dataset)"
                                    class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></button>
                                <form method="POST" action="{{ route('admin.network.delete', $record) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar este registro de red?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No hay registros de red.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $records->appends(request()->query())->links() }}</div>
    </div>
</div>

<div id="networkViewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Detalle de Red</h3>
            <button onclick="closeNetworkViewModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <div id="networkViewContent" class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm"></div>
        <div class="flex justify-end mt-6">
            <button type="button" onclick="closeNetworkViewModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cerrar</button>
        </div>
    </div>
</div>

<div id="networkModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-8 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="networkModalTitle" class="text-lg font-medium">Nuevo Registro de Red</h3>
            <button onclick="closeNetworkModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="networkForm" method="POST">
            @csrf
            <input type="hidden" id="networkMethod" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input name="responsible" id="networkResponsible" required placeholder="Usuario *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="position" id="networkPosition" required placeholder="Cargo *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="hostname" id="networkHostname" placeholder="Hostname" class="px-3 py-2 border border-gray-300 rounded-lg">
                <div class="rounded-lg border border-gray-200 px-3 py-2">
                    <div class="mb-2 text-sm font-medium text-gray-700">Tipo de conexion *</div>
                    <div class="flex flex-wrap gap-3">
                        @foreach($connectionTypes as $value => $label)
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" name="connection_types[]" value="{{ $value }}" class="network-connection-checkbox">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>
                <input type="number" min="0" name="connected_devices" id="networkConnectedDevices" placeholder="Dispositivos conectados" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input type="number" min="0" name="mobile_devices" id="networkMobileDevices" placeholder="Celulares" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input type="number" min="0" name="pc_devices" id="networkPcDevices" placeholder="PC" class="px-3 py-2 border border-gray-300 rounded-lg">
                <div class="md:col-span-3 rounded-lg border border-gray-200 p-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div>
                            <div class="text-sm font-medium text-gray-700">IP y MAC por dispositivo</div>
                            <div class="text-xs text-gray-500">Agrega una fila por cada equipo o conexion registrada.</div>
                        </div>
                        <button type="button" onclick="addNetworkInterfaceRow()" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-700 hover:bg-blue-100">
                            <i class="fas fa-plus mr-1"></i>Agregar
                        </button>
                    </div>
                    <div id="networkInterfaces" class="space-y-2"></div>
                </div>
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2">
                    <input type="checkbox" name="has_email" id="networkHasEmail" value="1" onchange="syncNetworkConditionalFields()"> Correo electronico
                </label>
                <input name="email" id="networkEmail" type="email" placeholder="Correo electronico" class="hidden px-3 py-2 border border-gray-300 rounded-lg md:col-span-2">
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2">
                    <input type="checkbox" name="has_shared_folders" id="networkHasSharedFolders" value="1" onchange="syncNetworkConditionalFields()"> Carpetas compartidas
                </label>
                <textarea name="shared_folders" id="networkSharedFolders" rows="3" placeholder="Detalle de carpetas compartidas" class="hidden px-3 py-2 border border-gray-300 rounded-lg md:col-span-2"></textarea>
                <textarea name="notes" id="networkNotes" rows="3" placeholder="Notas" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-3"></textarea>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeNetworkModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showCreateNetworkModal() {
    networkModalTitle.innerText = 'Nuevo Registro de Red';
    networkForm.action = "{{ route('admin.network.store') }}";
    networkMethod.value = 'POST';
    ['networkResponsible','networkPosition','networkHostname','networkConnectedDevices','networkMobileDevices','networkPcDevices','networkEmail','networkSharedFolders','networkNotes'].forEach(id => document.getElementById(id).value = '');
    setNetworkConnections(['cable']);
    setNetworkInterfaces([{}]);
    networkHasEmail.checked = false;
    networkHasSharedFolders.checked = false;
    syncNetworkConditionalFields();
    networkModal.classList.remove('hidden');
}

function editNetwork(record) {
    networkModalTitle.innerText = 'Editar Registro de Red';
    networkForm.action = `{{ url('/admin/network') }}/${record.id}`;
    networkMethod.value = 'PUT';
    networkResponsible.value = record.responsible || '';
    networkPosition.value = record.position || '';
    networkHostname.value = record.hostname || '';
    setNetworkConnections(JSON.parse(record.connectionTypes || '["cable"]'));
    setNetworkInterfaces(JSON.parse(record.networkInterfaces || '[]'));
    networkConnectedDevices.value = record.connectedDevices || 0;
    networkMobileDevices.value = record.mobileDevices || 0;
    networkPcDevices.value = record.pcDevices || 0;
    networkHasEmail.checked = record.hasEmail === '1';
    networkEmail.value = record.email || '';
    networkHasSharedFolders.checked = record.hasSharedFolders === '1';
    networkSharedFolders.value = record.sharedFolders || '';
    networkNotes.value = record.notes || '';
    syncNetworkConditionalFields();
    networkModal.classList.remove('hidden');
}

function syncNetworkConditionalFields() {
    networkEmail.classList.toggle('hidden', !networkHasEmail.checked);
    networkSharedFolders.classList.toggle('hidden', !networkHasSharedFolders.checked);
}

function viewNetwork(record) {
    const connectionLabels = { cable: 'Cable', wifi: 'WiFi' };
    const connections = JSON.parse(record.connectionTypes || '[]').map(type => connectionLabels[type] || type).join(', ') || 'Sin registrar';
    const interfaces = JSON.parse(record.networkInterfaces || '[]');
    const interfaceHtml = interfaces.length
        ? interfaces.map(row => '<div class="rounded border border-gray-100 px-3 py-2">IP: ' + escapeHtml(row.ip_address || 'Sin IP') + '<br>MAC: ' + escapeHtml(row.mac_address || 'Sin MAC') + '</div>').join('')
        : 'Sin IP/MAC';

    networkViewContent.innerHTML = `
        ${detailBlock('Usuario', record.responsible)}
        ${detailBlock('Cargo', record.position)}
        ${detailBlock('Hostname', record.hostname || 'Sin hostname')}
        ${detailBlock('Conexion', connections)}
        ${detailBlock('Dispositivos', 'Total: ' + (record.connectedDevices || 0) + '<br>Celulares: ' + (record.mobileDevices || 0) + '<br>PC: ' + (record.pcDevices || 0))}
        ${detailBlock('Correo electronico', record.hasEmail === '1' ? (record.email || 'Si') : 'No')}
        <div class="md:col-span-2">${detailBlock('IP y MAC', interfaceHtml)}</div>
        <div class="md:col-span-2">${detailBlock('Carpetas compartidas', record.hasSharedFolders === '1' ? (record.sharedFolders || 'Si') : 'No')}</div>
        <div class="md:col-span-2">${detailBlock('Notas', record.notes || 'Sin notas')}</div>
    `;
    networkViewModal.classList.remove('hidden');
}

function detailBlock(label, value) {
    return '<div class="rounded-lg border border-gray-200 p-3"><div class="text-xs font-semibold uppercase text-gray-500">' + label + '</div><div class="mt-1 text-gray-900">' + value + '</div></div>';
}

function escapeHtml(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function closeNetworkViewModal() { networkViewModal.classList.add('hidden'); }

function setNetworkConnections(values) {
    const normalized = values.map(String);
    document.querySelectorAll('.network-connection-checkbox').forEach(function(input) {
        input.checked = normalized.includes(input.value);
    });
}

function setNetworkInterfaces(rows) {
    networkInterfaces.innerHTML = '';
    const normalizedRows = rows.length ? rows : [{}];
    normalizedRows.forEach(function(row) {
        addNetworkInterfaceRow(row.ip_address || '', row.mac_address || '');
    });
}

function addNetworkInterfaceRow(ipAddress = '', macAddress = '') {
    const index = networkInterfaces.children.length;
    const row = document.createElement('div');
    row.className = 'grid grid-cols-1 gap-2 md:grid-cols-[1fr_1fr_auto]';
    row.innerHTML = `
        <input name="network_interfaces[${index}][ip_address]" value="${escapeAttribute(ipAddress)}" placeholder="IP" class="px-3 py-2 border border-gray-300 rounded-lg">
        <input name="network_interfaces[${index}][mac_address]" value="${escapeAttribute(macAddress)}" placeholder="MAC" class="px-3 py-2 border border-gray-300 rounded-lg">
        <button type="button" onclick="removeNetworkInterfaceRow(this)" class="rounded-lg border border-gray-300 px-3 py-2 text-sm hover:bg-gray-50">Quitar</button>
    `;
    networkInterfaces.appendChild(row);
}

function removeNetworkInterfaceRow(button) {
    button.closest('div').remove();

    if (!networkInterfaces.children.length) {
        addNetworkInterfaceRow();
    }

    reindexNetworkInterfaceRows();
}

function reindexNetworkInterfaceRows() {
    Array.from(networkInterfaces.children).forEach(function(row, index) {
        row.querySelectorAll('input')[0].name = `network_interfaces[${index}][ip_address]`;
        row.querySelectorAll('input')[1].name = `network_interfaces[${index}][mac_address]`;
    });
}

function escapeAttribute(value) {
    return String(value || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function closeNetworkModal() { networkModal.classList.add('hidden'); }
</script>
@endpush
@endsection
