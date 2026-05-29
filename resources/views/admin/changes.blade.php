@extends('layouts.app')

@section('title', 'Control de Cambios')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold">Control de Cambios</h1>
            <p class="text-sm text-gray-500 mt-1">Planificacion, riesgo, reversa y resultado de cambios de infraestructura.</p>
        </div>
        <div class="flex flex-wrap justify-end gap-3">
            <button onclick="showCreateChangeModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                <i class="fas fa-plus mr-2"></i>Nuevo cambio
            </button>
            @if($sourceTicket)
                <button onclick="showCreateChangeFromTicket(sourceTicket)" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                    <i class="fas fa-code-branch mr-2"></i>Registrar cambio del ticket
                </button>
            @endif
        </div>
    </div>

    @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            <div class="font-semibold">No se pudo guardar el cambio.</div>
            <ul class="mt-2 list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="all">Todos los estados</option>
            @foreach($statuses as $value => $label)<option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
        </select>
        <select name="priority" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="all">Todas las prioridades</option>
            @foreach($priorities as $value => $label)<option value="{{ $value }}" {{ request('priority') === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
        </select>
        <div class="md:col-span-2 flex gap-3">
            <button class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">Filtrar</button>
            <a href="{{ route('admin.changes') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cambio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Programado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Soporte</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($changes as $change)
                    <tr>
                        <td class="px-6 py-4"><div class="font-medium">{{ $change->title }}</div><div class="text-xs text-gray-500">{{ $change->type ?? 'Cambio general' }}</div></td>
                        <td class="px-6 py-4 text-sm">
                            @if($change->ticket)
                                <a href="{{ route('admin.ticket.show', $change->ticket) }}" class="text-blue-600 hover:text-blue-800">{{ $change->ticket->ticket_id }}</a>
                            @else
                                Sin ticket
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $change->asset?->name ?? 'Sin activo' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $change->scheduled_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $change->assignee?->name ?? 'Sin asignar' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $priorities[$change->priority] ?? $change->priority }}</td>
                        <td class="px-6 py-4 text-sm">{{ $statuses[$change->status] ?? $change->status }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                data-id="{{ $change->id }}"
                                data-ticket-id="{{ $change->ticket_id }}"
                                data-title="{{ e($change->title) }}"
                                data-type="{{ e($change->type) }}"
                                data-status="{{ $change->status }}"
                                data-priority="{{ $change->priority }}"
                                data-requested-by="{{ $change->requested_by }}"
                                data-assigned-to="{{ $change->assigned_to }}"
                                data-department-id="{{ $change->department_id }}"
                                data-asset-id="{{ $change->asset_id }}"
                                data-scheduled-at="{{ $change->scheduled_at?->format('Y-m-d\TH:i') }}"
                                data-completed-at="{{ $change->completed_at?->format('Y-m-d\TH:i') }}"
                                data-description="{{ e($change->description) }}"
                                data-risk="{{ e($change->risk) }}"
                                data-rollback-plan="{{ e($change->rollback_plan) }}"
                                data-result="{{ e($change->result) }}"
                                onclick="editChange(this.dataset)"
                                class="text-blue-600 hover:text-blue-800 mr-3"><i class="fas fa-edit"></i></button>
                            <form method="POST" action="{{ route('admin.changes.delete', $change) }}" class="inline">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar este cambio?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No hay cambios registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">{{ $changes->links() }}</div>
    </div>
</div>

<div id="changeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-6 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="changeModalTitle" class="text-lg font-medium">Nuevo Cambio</h3>
            <button onclick="closeChangeModal()" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
        </div>
        <form id="changeForm" method="POST">
            @csrf
            <input type="hidden" id="changeMethod" name="_method" value="POST">
            <input type="hidden" id="changeTicketId" name="ticket_id">
            <div id="sourceTicketNotice" class="hidden mb-4 rounded-lg border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-900"></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input name="title" id="changeTitle" required placeholder="Titulo *" class="px-3 py-2 border border-gray-300 rounded-lg md:col-span-2">
                <input name="type" id="changeType" required placeholder="Tipo de cambio *" class="px-3 py-2 border border-gray-300 rounded-lg">
                <select name="status" id="changeStatus" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    @foreach($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                <select name="priority" id="changePriority" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    @foreach($priorities as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                </select>
                <select name="asset_id" id="changeAsset" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Sin activo</option>
                    @foreach($assets as $asset)<option value="{{ $asset->id }}">{{ $asset->name }}</option>@endforeach
                </select>
                <select name="requested_by" id="changeRequestedBy" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Solicitante *</option>
                    @foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach
                </select>
                <select name="assigned_to" id="changeAssignedTo" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Soporte *</option>
                    @foreach($technicians as $technician)<option value="{{ $technician->id }}">{{ $technician->name }}</option>@endforeach
                </select>
                <select name="department_id" id="changeDepartment" required class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Tipo de soporte *</option>
                    @foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach
                </select>
                <input type="datetime-local" name="scheduled_at" id="changeScheduledAt" required class="px-3 py-2 border border-gray-300 rounded-lg">
                <input type="datetime-local" name="completed_at" id="changeCompletedAt" class="px-3 py-2 border border-gray-300 rounded-lg">
                <div class="md:col-span-3">
                    <label for="changeDescription" class="mb-1 block text-sm font-medium text-gray-700">Descripcion *</label>
                    <textarea name="description" id="changeDescription" required rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="changeRisk" class="mb-1 block text-sm font-medium text-gray-700">Riesgo *</label>
                    <textarea name="risk" id="changeRisk" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="changeRollbackPlan" class="mb-1 block text-sm font-medium text-gray-700">Plan de reversa *</label>
                    <textarea name="rollback_plan" id="changeRollbackPlan" required rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label for="changeResult" class="mb-1 block text-sm font-medium text-gray-700">Resultado</label>
                    <textarea name="result" id="changeResult" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeChangeModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
            </div>
        </form>
    </div>
</div>

@php
    $sourceTicketPayload = $sourceTicket ? [
        'id' => $sourceTicket->id,
        'ticket_id' => $sourceTicket->ticket_id,
        'subject' => $sourceTicket->subject,
        'message' => $sourceTicket->message,
        'priority' => $sourceTicket->priority === 'urgent' ? 'critical' : $sourceTicket->priority,
        'requested_by' => $sourceTicket->user_id,
        'assigned_to' => $sourceTicket->assigned_to ?: auth()->id(),
        'department_id' => $sourceTicket->department_id,
        'asset_id' => $sourceTicket->asset_id,
        'department_name' => $sourceTicket->department?->name,
        'requester_name' => $sourceTicket->user?->name,
        'assignee_name' => $sourceTicket->assignee?->name,
        'asset_name' => $sourceTicket->asset?->name,
    ] : null;
@endphp

@push('scripts')
<script>
const sourceTicket = @json($sourceTicketPayload);

function localDateTimeValue(date) {
    const offsetDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000);
    return offsetDate.toISOString().slice(0, 16);
}

function showCreateChangeModal() {
    changeModalTitle.innerText = 'Nuevo Cambio';
    changeForm.action = "{{ route('admin.changes.store') }}";
    changeMethod.value = 'POST';
    changeTicketId.value = '';
    sourceTicketNotice.classList.add('hidden');
    sourceTicketNotice.innerHTML = '';
    ['changeTitle','changeType','changeScheduledAt','changeCompletedAt','changeDescription','changeRisk','changeRollbackPlan','changeResult'].forEach(id => document.getElementById(id).value = '');
    changeStatus.value = 'planned'; changePriority.value = 'medium'; changeAsset.value = ''; changeRequestedBy.value = ''; changeAssignedTo.value = "{{ auth()->id() }}"; changeDepartment.value = '';
    changeScheduledAt.value = localDateTimeValue(new Date());
    changeModal.classList.remove('hidden');
}

function showCreateChangeFromTicket(ticket) {
    showCreateChangeModal();
    changeTicketId.value = ticket.id || '';
    changeTitle.value = 'Cambio solicitado desde ticket ' + ticket.ticket_id + ': ' + ticket.subject;
    changeType.value = ticket.department_name || 'Cambio tecnico';
    changePriority.value = ticket.priority || 'medium';
    changeRequestedBy.value = ticket.requested_by || '';
    changeAssignedTo.value = ticket.assigned_to || "{{ auth()->id() }}";
    changeDepartment.value = ticket.department_id || '';
    changeAsset.value = ticket.asset_id || '';
    changeDescription.value = ticket.message || '';
    changeRisk.value = 'Pendiente de evaluacion tecnica.';
    changeRollbackPlan.value = 'Pendiente de definir plan de reversa antes de ejecutar el cambio.';
    sourceTicketNotice.classList.remove('hidden');
    sourceTicketNotice.innerHTML = '<strong>Cambio vinculado al ticket ' + ticket.ticket_id + '</strong><br>Solicitante, tipo de soporte, prioridad, responsable y descripcion fueron sugeridos desde el ticket.';
}

function editChange(change) {
    changeModalTitle.innerText = 'Editar Cambio';
    changeForm.action = `{{ url('/admin/changes') }}/${change.id}`;
    changeMethod.value = 'PUT';
    changeTicketId.value = change.ticketId || '';
    sourceTicketNotice.classList.toggle('hidden', !change.ticketId);
    sourceTicketNotice.innerHTML = change.ticketId ? '<strong>Cambio vinculado a un ticket.</strong><br>El cambio mantiene el respaldo del ticket de origen.' : '';
    changeTitle.value = change.title || ''; changeType.value = change.type || ''; changeStatus.value = change.status || 'planned'; changePriority.value = change.priority || 'medium';
    changeRequestedBy.value = change.requestedBy || ''; changeAssignedTo.value = change.assignedTo || ''; changeDepartment.value = change.departmentId || ''; changeAsset.value = change.assetId || '';
    changeScheduledAt.value = change.scheduledAt || ''; changeCompletedAt.value = change.completedAt || ''; changeDescription.value = change.description || '';
    changeRisk.value = change.risk || ''; changeRollbackPlan.value = change.rollbackPlan || ''; changeResult.value = change.result || '';
    changeModal.classList.remove('hidden');
}
function closeChangeModal() { changeModal.classList.add('hidden'); }

if (sourceTicket) {
    document.addEventListener('DOMContentLoaded', function() {
        showCreateChangeFromTicket(sourceTicket);
    });
}
</script>
@endpush
@endsection
