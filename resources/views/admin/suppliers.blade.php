@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Proveedores</h1>
        <div class="flex flex-wrap justify-end gap-2">
            <a href="{{ route('admin.suppliers.template') }}" class="border border-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50">
                <i class="fas fa-file-csv mr-2"></i>Plantilla
            </a>
            <a href="{{ route('admin.suppliers.export') }}" class="border border-green-200 bg-green-50 text-green-700 px-4 py-2 rounded-lg hover:bg-green-100">
                <i class="fas fa-download mr-2"></i>Exportar
            </a>
            <button onclick="showImportSupplierModal()" class="border border-blue-200 bg-blue-50 text-blue-700 px-4 py-2 rounded-lg hover:bg-blue-100">
                <i class="fas fa-upload mr-2"></i>Importar
            </button>
            <button onclick="showCreateSupplierModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                <i class="fas fa-plus mr-2"></i>Nuevo Proveedor
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Proveedor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contacto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telefono</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($suppliers as $supplier)
                <tr>
                    <td class="px-6 py-4">
                        <div class="font-medium">{{ $supplier->name }}</div>
                        <div class="text-xs text-gray-500">{{ $supplier->rif ?? 'Sin RIF' }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm">{{ $supplier->contact_name ?? 'No aplica' }}<br>{{ $supplier->email ?? '' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $supplier->phone ?? 'No aplica' }}</td>
                    <td class="px-6 py-4 text-sm">{{ $supplier->is_active ? 'Activo' : 'Inactivo' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <button
                            data-id="{{ $supplier->id }}"
                            data-name="{{ e($supplier->name) }}"
                            data-rif="{{ e($supplier->rif) }}"
                            data-contact-name="{{ e($supplier->contact_name) }}"
                            data-email="{{ e($supplier->email) }}"
                            data-phone="{{ e($supplier->phone) }}"
                            data-address="{{ e($supplier->address) }}"
                            data-notes="{{ e($supplier->notes) }}"
                            data-active="{{ $supplier->is_active ? '1' : '0' }}"
                            onclick="editSupplier(this.dataset)"
                            class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></button>
                        <form method="POST" action="{{ route('admin.suppliers.delete', $supplier) }}" class="inline">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar este proveedor?')"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No hay proveedores registrados.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4">{{ $suppliers->links() }}</div>
    </div>
</div>

<div id="supplierImportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Importar Proveedores</h3>
            <button onclick="closeImportSupplierModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <p class="text-sm text-gray-600 mb-4">Usa la plantilla CSV. Se actualiza por RIF.</p>
        <form method="POST" action="{{ route('admin.suppliers.import') }}" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".csv,.txt" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeImportSupplierModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Importar</button>
            </div>
        </form>
    </div>
</div>

<div id="supplierModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="supplierModalTitle" class="text-lg font-medium">Nuevo Proveedor</h3>
            <button onclick="closeSupplierModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="supplierForm" method="POST">
            @csrf
            <input type="hidden" id="supplierMethod" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input name="name" id="supplierName" required placeholder="Nombre *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="rif" id="supplierRif" required placeholder="RIF *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="contact_name" id="supplierContactName" required placeholder="Contacto *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input type="email" name="email" id="supplierCorreo" required placeholder="Correo *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <input name="phone" id="supplierPhone" required placeholder="Telefono *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <label class="flex items-center px-3 py-2"><input type="checkbox" name="is_active" id="supplierActive" value="1" class="mr-2">Activo</label>
                <textarea name="address" id="supplierAddress" required rows="2" placeholder="Direccion *" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-2"></textarea>
                <textarea name="notes" id="supplierNotes" rows="3" placeholder="Notas" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-2"></textarea>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeSupplierModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function showCreateSupplierModal() {
    supplierModalTitle.innerText = 'Nuevo Proveedor';
    supplierForm.action = "{{ route('admin.suppliers.store') }}";
    supplierMethod.value = 'POST';
    ['supplierName','supplierRif','supplierContactName','supplierCorreo','supplierPhone','supplierAddress','supplierNotes'].forEach(id => document.getElementById(id).value = '');
    supplierActive.checked = true;
    supplierModal.classList.remove('hidden');
}
function editSupplier(supplier) {
    supplierModalTitle.innerText = 'Editar Proveedor';
    supplierForm.action = `{{ url('/admin/suppliers') }}/${supplier.id}`;
    supplierMethod.value = 'PUT';
    supplierName.value = supplier.name || ''; supplierRif.value = supplier.rif || ''; supplierContactName.value = supplier.contact_name || '';
    supplierCorreo.value = supplier.email || ''; supplierPhone.value = supplier.phone || ''; supplierAddress.value = supplier.address || '';
    supplierNotes.value = supplier.notes || ''; supplierActive.checked = supplier.active === '1';
    supplierModal.classList.remove('hidden');
}
function closeSupplierModal() { supplierModal.classList.add('hidden'); }
function showImportSupplierModal() { supplierImportModal.classList.remove('hidden'); }
function closeImportSupplierModal() { supplierImportModal.classList.add('hidden'); }
</script>
@endpush
@endsection
