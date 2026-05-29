@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Inventario y Activos</h1>
            <p class="text-sm text-gray-500 mt-1">Hardware, software, ubicacion, responsable, garantia y estado.</p>
        </div>
        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('admin.assets.template') }}" class="border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50">
                <i class="fas fa-file-csv mr-2"></i>Plantilla
            </a>
            <a href="{{ route('admin.assets.export', request()->query()) }}" class="border border-green-200 bg-green-50 text-green-700 px-4 py-2 rounded-lg hover:bg-green-100">
                <i class="fas fa-download mr-2"></i>Exportar
            </a>
            <button onclick="showImportAssetModal()" class="border border-blue-200 bg-blue-50 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100">
                <i class="fas fa-upload mr-2"></i>Importar
            </button>
            <button onclick="showCreateAssetModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                <i class="fas fa-plus mr-2"></i>Nuevo Activo
            </button>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
            <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="all">Todos los tipos</option>
                @foreach($types as $value => $label)
                    <option value="{{ $value }}" {{ request('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="all">Todos los estados</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Oficina</label>
            <select name="office_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="all">Todas las oficinas</option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}" {{ (string) request('office_id') === (string) $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Responsable</label>
            <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="all">Todos los responsables</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (string) request('assigned_to') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-3">
            <button class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">Filtrar</button>
            <a href="{{ route('admin.assets') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Codigo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicacion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsable</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($assets as $asset)
                    <tr>
                        <td class="px-6 py-4 text-sm font-mono">{{ $asset->asset_tag }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium">{{ $asset->name }}</div>
                            <div class="text-xs text-gray-500">{{ trim(($asset->brand ?? '').' '.($asset->model ?? '')) ?: 'Sin marca/modelo' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $types[$asset->type] ?? $asset->type }}</td>
                        <td class="px-6 py-4 text-sm">{{ $asset->office?->name ?? 'Sin oficina' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $asset->assignee?->name ?? 'Sin asignar' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $statuses[$asset->status] ?? $asset->status }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                data-id="{{ $asset->id }}"
                                data-asset-tag="{{ e($asset->asset_tag) }}"
                                data-name="{{ e($asset->name) }}"
                                data-type="{{ $asset->type }}"
                                data-brand="{{ e($asset->brand) }}"
                                data-model="{{ e($asset->model) }}"
                                data-serial_number="{{ e($asset->serial_number) }}"
                                data-version="{{ e($asset->version) }}"
                                data-status="{{ $asset->status }}"
                                data-office-id="{{ $asset->office_id }}"
                                data-assigned-to="{{ $asset->assigned_to }}"
                                data-purchase-date="{{ $asset->purchase_date?->format('Y-m-d') }}"
                                data-warranty-until="{{ $asset->warranty_until?->format('Y-m-d') }}"
                                data-notes="{{ e($asset->notes) }}"
                                onclick="editAsset(this.dataset)"
                                class="text-blue-600 hover:text-blue-800 mr-3" title="Editar"><i class="fas fa-edit"></i></button>
                            <form method="POST" action="{{ route('admin.assets.delete', $asset) }}" class="inline">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar este activo?')" title="Eliminar"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay activos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $assets->links() }}</div>
    </div>
</div>

<div id="assetImportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Importar Inventario</h3>
            <button onclick="closeImportAssetModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-600 mb-4">Usa la plantilla CSV. Se actualiza por codigo de activo (<strong>asset_tag</strong>).</p>
        <form method="POST" action="{{ route('admin.assets.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".csv,.txt" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeImportAssetModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Importar</button>
            </div>
        </form>
    </div>
</div>

<div id="assetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-8 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="assetModalTitle" class="text-lg font-medium">Nuevo Activo</h3>
            <button onclick="closeAssetModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="assetForm" method="POST">
            @csrf
            <input type="hidden" id="assetMethod" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input name="asset_tag" id="assetTag" required placeholder="Codigo *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="name" id="assetName" required placeholder="Nombre del activo *" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-2">
                <select name="type" id="assetType" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    @foreach($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                <input name="brand" id="assetBrand" required placeholder="Marca *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="model" id="assetModel" required placeholder="Modelo *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="serial_number" id="assetSerialNumber" required placeholder="Serial / licencia *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="version" id="assetVersion" placeholder="Version de software" class="px-3 py-2 border border-gray-300 rounded-lg">
                <select name="status" id="assetStatus" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    @foreach($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                <select name="office_id" id="assetOffice" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Oficina *</option>
                    @foreach($offices as $office)<option value="{{ $office->id }}">{{ $office->name }}</option>@endforeach
                </select>
                <select name="assigned_to" id="assetAssignedTo" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Responsable *</option>
                    @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                </select>
                <input type="date" name="purchase_date" id="assetPurchaseDate" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input type="date" name="warranty_until" id="assetWarrantyUntil" class="px-3 py-2 border border-gray-300 rounded-lg">
                <textarea name="notes" id="assetNotes" rows="3" placeholder="Notas" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-3"></textarea>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeAssetModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showCreateAssetModal() {
    assetModalTitle.innerText = 'Nuevo Activo';
    assetForm.action = "{{ route('admin.assets.store') }}";
    assetMethod.value = 'POST';
    ['assetTag','assetName','assetBrand','assetModel','assetSerialNumber','assetVersion','assetPurchaseDate','assetWarrantyUntil','assetNotes'].forEach(id => document.getElementById(id).value = '');
    assetType.value = 'hardware'; assetStatus.value = 'active'; assetOffice.value = ''; assetAssignedTo.value = '';
    assetModal.classList.remove('hidden');
}
function editAsset(asset) {
    assetModalTitle.innerText = 'Editar Activo';
    assetForm.action = `{{ url('/admin/assets') }}/${asset.id}`;
    assetMethod.value = 'PUT';
    assetTag.value = asset.assetTag || ''; assetName.value = asset.name || ''; assetType.value = asset.type || 'hardware';
    assetBrand.value = asset.brand || ''; assetModel.value = asset.model || ''; assetSerialNumber.value = asset.serialNumber || '';
    assetVersion.value = asset.version || ''; assetStatus.value = asset.status || 'active'; assetOffice.value = asset.officeId || '';
    assetAssignedTo.value = asset.assignedTo || ''; assetPurchaseDate.value = asset.purchaseDate || ''; assetWarrantyUntil.value = asset.warrantyUntil || '';
    assetNotes.value = asset.notes || ''; assetModal.classList.remove('hidden');
}
function closeAssetModal() { assetModal.classList.add('hidden'); }
function showImportAssetModal() { assetImportModal.classList.remove('hidden'); }
function closeImportAssetModal() { assetImportModal.classList.add('hidden'); }
</script>
@endpush
@endsection
