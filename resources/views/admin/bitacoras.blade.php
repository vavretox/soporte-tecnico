@extends('layouts.app')

@section('title', 'Bitacoras')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Bitacoras</h1>
            <p class="text-sm text-gray-500 mt-1">Registro operativo de atenciones, visitas, equipos y resultados.</p>
        </div>
        <button onclick="showCreateBitacoraModal()" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
            <i class="fas fa-clipboard-list mr-2"></i>Nueva Bitacora
        </button>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="all">Todos</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de soporte</label>
            <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="all">Todos</option>
                @foreach($departments as $department)
                    <option value="{{ $department->id }}" {{ request('department_id') == $department->id ? 'selected' : '' }}>
                        {{ $department->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2 flex items-end gap-3">
            <button type="submit" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900">Filtrar</button>
            <a href="{{ route('admin.bitacoras') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asunto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oficina</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Soporte</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($bitacoras as $bitacora)
                    <tr>
                        <td class="px-6 py-4 text-sm whitespace-nowrap">
                            {{ $bitacora->reported_at?->format('d/m/Y H:i') ?? $bitacora->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium">{{ $bitacora->title }}</div>
                            <div class="text-xs text-gray-500 mt-1">
                                @if($bitacora->ticket)
                                    Ticket {{ $bitacora->ticket->ticket_id }} -
                                @endif
                                {{ $bitacora->user?->name ?? 'Sin funcionario' }}
                                @if($bitacora->equipment)
                                    - {{ $bitacora->equipment }}
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $bitacora->department->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ $bitacora->office?->name ?? 'Sin oficina' }}</td>
                        <td class="px-6 py-4 text-sm">{{ $bitacora->technician?->name ?? 'Sin asignar' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full
                                @if($bitacora->status === 'open') bg-yellow-100 text-yellow-800
                                @elseif($bitacora->status === 'in_progress') bg-blue-100 text-blue-800
                                @elseif($bitacora->status === 'resolved') bg-green-100 text-green-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ $bitacora->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button
                                data-id="{{ $bitacora->id }}"
                                data-ticket-id="{{ $bitacora->ticket_id }}"
                                data-ticket-code="{{ $bitacora->ticket?->ticket_id }}"
                                data-title="{{ e($bitacora->title) }}"
                                data-department-name="{{ e($bitacora->department?->name ?? 'Sin tipo') }}"
                                data-department-id="{{ $bitacora->department_id }}"
                                data-office-name="{{ e($bitacora->office?->name ?? 'Sin oficina') }}"
                                data-office-id="{{ $bitacora->office_id }}"
                                data-user-name="{{ e($bitacora->user?->name ?? 'Sin funcionario') }}"
                                data-user-id="{{ $bitacora->user_id }}"
                                data-technician-name="{{ e($bitacora->technician?->name ?? 'Sin asignar') }}"
                                data-technician-id="{{ $bitacora->technician_id }}"
                                data-equipment="{{ e($bitacora->equipment) }}"
                                data-location="{{ e($bitacora->location) }}"
                                data-description="{{ e($bitacora->description) }}"
                                data-actions-taken="{{ e($bitacora->actions_taken) }}"
                                data-result="{{ e($bitacora->result) }}"
                                data-status="{{ $bitacora->status }}"
                                data-status-label="{{ $bitacora->statusLabel() }}"
                                data-reported-at="{{ $bitacora->reported_at?->format('Y-m-d\TH:i') }}"
                                data-reported-at-label="{{ $bitacora->reported_at?->format('d/m/Y H:i') ?? $bitacora->created_at->format('d/m/Y H:i') }}"
                                onclick="showBitacoraDetails(this.dataset)"
                                class="text-gray-600 hover:text-gray-900 mr-3" title="Ver informacion">
                                <i class="fas fa-search"></i>
                            </button>
                            <button
                                data-id="{{ $bitacora->id }}"
                                data-ticket-id="{{ $bitacora->ticket_id }}"
                                data-title="{{ e($bitacora->title) }}"
                                data-department-id="{{ $bitacora->department_id }}"
                                data-office-id="{{ $bitacora->office_id }}"
                                data-user-id="{{ $bitacora->user_id }}"
                                data-technician-id="{{ $bitacora->technician_id }}"
                                data-equipment="{{ e($bitacora->equipment) }}"
                                data-location="{{ e($bitacora->location) }}"
                                data-description="{{ e($bitacora->description) }}"
                                data-actions-taken="{{ e($bitacora->actions_taken) }}"
                                data-result="{{ e($bitacora->result) }}"
                                data-status="{{ $bitacora->status }}"
                                data-reported-at="{{ $bitacora->reported_at?->format('Y-m-d\TH:i') }}"
                                onclick="editBitacora(this.dataset)"
                                class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.bitacoras.delete', $bitacora) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Eliminar esta bitacora?')" title="Eliminar">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay bitacoras registradas.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4">
            {{ $bitacoras->links() }}
        </div>
    </div>
</div>

<div id="bitacoraDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-start gap-4 mb-4">
            <div>
                <h3 class="text-lg font-semibold">Informacion de Bitacora</h3>
                <p id="detailTicket" class="mt-1 text-sm text-gray-500"></p>
            </div>
            <button onclick="closeBitacoraDetails()" class="text-gray-400 hover:text-gray-600" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Asunto</div>
                <div id="detailTitle" class="mt-1 text-sm font-medium text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Fecha y hora</div>
                <div id="detailReportedAt" class="mt-1 text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Tipo de soporte</div>
                <div id="detailDepartment" class="mt-1 text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Oficina</div>
                <div id="detailOffice" class="mt-1 text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Estado</div>
                <div id="detailStatus" class="mt-1 text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Funcionario</div>
                <div id="detailUser" class="mt-1 text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Soporte</div>
                <div id="detailTechnician" class="mt-1 text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Equipo / activo</div>
                <div id="detailEquipment" class="mt-1 text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Ubicacion</div>
                <div id="detailLocation" class="mt-1 text-sm text-gray-900"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Descripcion</div>
                <div id="detailDescription" class="mt-2 whitespace-pre-wrap text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Acciones realizadas</div>
                <div id="detailActionsTaken" class="mt-2 whitespace-pre-wrap text-sm text-gray-900"></div>
            </div>
            <div class="rounded-lg border border-gray-200 p-3">
                <div class="text-xs font-semibold uppercase text-gray-500">Resultado</div>
                <div id="detailResult" class="mt-2 whitespace-pre-wrap text-sm text-gray-900"></div>
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <button type="button" onclick="closeBitacoraDetails()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cerrar</button>
        </div>
    </div>
</div>

<div id="bitacoraModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-6 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="bitacoraModalTitle" class="text-lg font-medium">Nueva Bitacora</h3>
            <button onclick="closeBitacoraModal()" class="text-gray-400 hover:text-gray-600" title="Cerrar">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form id="bitacoraForm" method="POST">
            @csrf
            <input type="hidden" id="bitacoraMethod" name="_method" value="POST">
            <input type="hidden" name="ticket_id" id="bitacoraTicketId">
            <input type="hidden" name="department_id" id="lockedBitacoraDepartment" disabled>
            <input type="hidden" name="office_id" id="lockedBitacoraOffice" disabled>
            <input type="hidden" name="user_id" id="lockedBitacoraUser" disabled>
            <input type="hidden" name="technician_id" id="lockedBitacoraTechnician" disabled>
            <input type="hidden" name="location" id="lockedBitacoraLocation" disabled>
            <input type="hidden" name="return_ticket_id" id="bitacoraReturnTicketId">
            <div id="sourceTicketNotice" class="hidden mb-4 rounded-lg border border-green-100 bg-green-50 px-4 py-3 text-sm text-green-900"></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Asunto *</label>
                    <input type="text" name="title" id="bitacoraTitle" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha y hora *</label>
                    <input type="datetime-local" name="reported_at" id="bitacoraReportedAt" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de soporte *</label>
                    <select name="department_id" id="bitacoraDepartment" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona...</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Oficina *</label>
                    <select name="office_id" id="bitacoraOffice" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona...</option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}">{{ $office->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                    <select name="status" id="bitacoraStatus" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Funcionario *</label>
                    <select name="user_id" id="bitacoraUser" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Soporte *</label>
                    <select name="technician_id" id="bitacoraTechnician" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona...</option>
                        @foreach($technicians as $technician)
                            <option value="{{ $technician->id }}">{{ $technician->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Equipo / activo</label>
                    <input type="text" name="equipment" id="bitacoraEquipment" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ubicacion *</label>
                    <input type="text" name="location" id="bitacoraLocation" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion *</label>
                    <textarea name="description" id="bitacoraDescription" required rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Acciones realizadas *</label>
                    <textarea name="actions_taken" id="bitacoraActionsTaken" required rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Resultado</label>
                    <textarea name="result" id="bitacoraResult" rows="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                </div>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="closeBitacoraModal()" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Guardar</button>
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
        'department_id' => $sourceTicket->department_id,
        'user_id' => $sourceTicket->user_id,
        'office_id' => $sourceTicket->user?->office_id,
        'technician_id' => $sourceTicket->assigned_to ?: auth()->id(),
        'status' => $sourceTicket->status,
        'department_name' => $sourceTicket->department?->name,
        'office_name' => $sourceTicket->user?->office?->name,
        'office_location' => $sourceTicket->user?->office?->location ?: $sourceTicket->user?->office?->name,
        'user_name' => $sourceTicket->user?->name,
        'technicianName' => $sourceTicket->assignee?->name ?? auth()->user()?->name,
        'asset_name' => $sourceTicket->asset ? trim(($sourceTicket->asset->asset_tag ? $sourceTicket->asset->asset_tag.' - ' : '').$sourceTicket->asset->name) : null,
        'return_ticket_id' => $sourceTicket->id,
    ] : null;
@endphp

@push('scripts')
<script>
    const sourceTicket = @json($sourceTicketPayload);

    function currentDateTimeLocal() {
        var date = new Date();
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 16);
    }

    function setDetailText(id, value) {
        document.getElementById(id).textContent = value && value.trim() ? value : 'Sin registrar';
    }

    function showBitacoraDetails(bitacora) {
        setDetailText('detailTicket', bitacora.ticketCode ? 'Ticket ' + bitacora.ticketCode : 'Bitacora sin ticket asociado');
        setDetailText('detailTitle', bitacora.title);
        setDetailText('detailReportedAt', bitacora.reportedAtLabel);
        setDetailText('detailDepartment', bitacora.departmentName);
        setDetailText('detailOffice', bitacora.officeName);
        setDetailText('detailStatus', bitacora.statusLabel);
        setDetailText('detailUser', bitacora.userName);
        setDetailText('detailTechnician', bitacora.technicianName);
        setDetailText('detailEquipment', bitacora.equipment);
        setDetailText('detailLocation', bitacora.location);
        setDetailText('detailDescription', bitacora.description);
        setDetailText('detailActionsTaken', bitacora.actionsTaken);
        setDetailText('detailResult', bitacora.result);
        document.getElementById('bitacoraDetailsModal').classList.remove('hidden');
    }

    function closeBitacoraDetails() {
        document.getElementById('bitacoraDetailsModal').classList.add('hidden');
    }

    function setBitacoraLockedFields(locked, values = {}) {
        [
            ['bitacoraDepartment', 'lockedBitacoraDepartment', values.department_id],
            ['bitacoraOffice', 'lockedBitacoraOffice', values.office_id],
            ['bitacoraUser', 'lockedBitacoraUser', values.user_id],
            ['bitacoraTechnician', 'lockedBitacoraTechnician', values.technician_id],
            ['bitacoraLocation', 'lockedBitacoraLocation', values.office_location],
        ].forEach(function(field) {
            var control = document.getElementById(field[0]);
            var hidden = document.getElementById(field[1]);
            control.disabled = locked;
            hidden.disabled = !locked;
            hidden.value = locked ? (field[2] || '') : '';
            control.classList.toggle('bg-gray-100', locked);
            control.classList.toggle('cursor-not-allowed', locked);
        });
    }

    function showCreateBitacoraModal() {
        document.getElementById('bitacoraModalTitle').innerText = 'Nueva Bitacora';
        document.getElementById('bitacoraForm').action = "{{ route('admin.bitacoras.store') }}";
        document.getElementById('bitacoraMethod').value = 'POST';
        document.getElementById('bitacoraTicketId').value = '';
        document.getElementById('bitacoraReturnTicketId').value = '';
        document.getElementById('sourceTicketNotice').classList.add('hidden');
        document.getElementById('sourceTicketNotice').innerHTML = '';
        setBitacoraLockedFields(false);
        document.getElementById('bitacoraTitle').value = '';
        document.getElementById('bitacoraDepartment').value = '';
        document.getElementById('bitacoraOffice').value = '';
        document.getElementById('bitacoraUser').value = '';
        document.getElementById('bitacoraTechnician').value = "{{ auth()->id() }}";
        document.getElementById('bitacoraEquipment').value = '';
        document.getElementById('bitacoraLocation').value = '';
        document.getElementById('bitacoraDescription').value = '';
        document.getElementById('bitacoraActionsTaken').value = '';
        document.getElementById('bitacoraResult').value = '';
        document.getElementById('bitacoraStatus').value = 'open';
        document.getElementById('bitacoraReportedAt').value = currentDateTimeLocal();
        document.getElementById('bitacoraModal').classList.remove('hidden');
    }

    function showCreateBitacoraFromTicket(ticket) {
        showCreateBitacoraModal();
        document.getElementById('bitacoraTicketId').value = ticket.id || '';
        document.getElementById('bitacoraReturnTicketId').value = ticket.return_ticket_id || ticket.id || '';
        document.getElementById('bitacoraTitle').value = 'Atencion de ticket ' + ticket.ticket_id + ': ' + ticket.subject;
        document.getElementById('bitacoraDepartment').value = ticket.department_id || '';
        document.getElementById('bitacoraOffice').value = ticket.office_id || '';
        document.getElementById('bitacoraUser').value = ticket.user_id || '';
        document.getElementById('bitacoraTechnician').value = ticket.technician_id || "{{ auth()->id() }}";
        setBitacoraLockedFields(true, ticket);
        document.getElementById('bitacoraEquipment').value = ticket.asset_name || '';
        document.getElementById('bitacoraLocation').value = ticket.office_location || '';
        document.getElementById('bitacoraDescription').value = ticket.message || '';
        document.getElementById('bitacoraActionsTaken').value = '';
        document.getElementById('bitacoraResult').value = ticket.status === 'closed' ? 'Ticket cerrado.' : 'Ticket resuelto.';
        document.getElementById('bitacoraStatus').value = ticket.status === 'closed' ? 'closed' : 'resolved';
        document.getElementById('sourceTicketNotice').classList.remove('hidden');
        document.getElementById('sourceTicketNotice').innerHTML = '<strong>Bitacora sugerida desde ticket ' + escapeHtml(ticket.ticket_id) + '</strong><br>Tipo de soporte, oficina, funcionario y soporte asignado quedan bloqueados desde el ticket.';
    }

    function editBitacora(bitacora) {
        document.getElementById('bitacoraModalTitle').innerText = 'Editar Bitacora';
        document.getElementById('bitacoraForm').action = `{{ url('/admin/bitacoras') }}/${bitacora.id}`;
        document.getElementById('bitacoraMethod').value = 'PUT';
        document.getElementById('bitacoraTicketId').value = bitacora.ticketId || '';
        document.getElementById('bitacoraReturnTicketId').value = '';
        document.getElementById('sourceTicketNotice').classList.add('hidden');
        setBitacoraLockedFields(false);
        document.getElementById('bitacoraTitle').value = bitacora.title || '';
        document.getElementById('bitacoraDepartment').value = bitacora.departmentId || '';
        document.getElementById('bitacoraOffice').value = bitacora.officeId || '';
        document.getElementById('bitacoraUser').value = bitacora.userId || '';
        document.getElementById('bitacoraTechnician').value = bitacora.technicianId || '';
        document.getElementById('bitacoraEquipment').value = bitacora.equipment || '';
        document.getElementById('bitacoraLocation').value = bitacora.location || '';
        document.getElementById('bitacoraDescription').value = bitacora.description || '';
        document.getElementById('bitacoraActionsTaken').value = bitacora.actionsTaken || '';
        document.getElementById('bitacoraResult').value = bitacora.result || '';
        document.getElementById('bitacoraStatus').value = bitacora.status || 'open';
        document.getElementById('bitacoraReportedAt').value = bitacora.reportedAt || currentDateTimeLocal();
        document.getElementById('bitacoraModal').classList.remove('hidden');
    }

    function closeBitacoraModal() {
        document.getElementById('bitacoraModal').classList.add('hidden');
    }

    function escapeHtml(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    if (sourceTicket) {
        document.addEventListener('DOMContentLoaded', function() {
            showCreateBitacoraFromTicket(sourceTicket);
        });
    }
</script>
@endpush
@endsection
