@extends('layouts.app')

@section('title', 'Nuevo Ticket Fisico')

@section('content')
<div class="max-w-4xl mx-auto px-4">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold">Crear Ticket Fisico</h1>
            <p class="mt-1 text-sm text-gray-600">Registro avanzado para solicitudes escritas que llegan a la oficina de Helpdesk.</p>
        </div>

        <form method="POST" action="{{ route('tickets.physical.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Funcionario remitente *</label>
                    <select name="user_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona...</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id') == $user->id)>
                                {{ $user->name }} - {{ $user->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de soporte *</label>
                    <select name="department_id" id="physicalDepartment" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona...</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>
                                {{ $department->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirigido a *</label>
                    <select name="assigned_to" id="physicalAssignee" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona soporte...</option>
                        @foreach($agents as $agent)
                            <option value="{{ $agent->id }}" @selected(old('assigned_to') == $agent->id)
                                data-role="{{ $agent->role }}"
                                data-departments='@json($agent->supportDepartmentIds())'>
                                {{ $agent->name }} ({{ $agent->role === 'admin' ? 'Administrador' : 'Soporte' }})
                            </option>
                        @endforeach
                    </select>
                    <p id="assigneeHelp" class="mt-1 text-xs text-gray-500">Primero selecciona el tipo de soporte para filtrar los destinatarios disponibles.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad *</label>
                    <select name="priority" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="low" @selected(old('priority') === 'low')>Baja</option>
                        <option value="medium" @selected(old('priority', 'medium') === 'medium')>Media</option>
                        <option value="high" @selected(old('priority') === 'high')>Alta</option>
                        <option value="urgent" @selected(old('priority') === 'urgent')>Urgente</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CITE de circular interna *</label>
                <input type="text" name="circular_cite" value="{{ old('circular_cite') }}" required
                    placeholder="Ejemplo: CITE INT. DTI Nro. 012/2026"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Referencia *</label>
                <input type="text" name="reference" value="{{ old('reference') }}" required
                    placeholder="Ejemplo: Solicitud de mantenimiento preventivo"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <p class="mt-1 text-xs text-gray-500">El sistema generara automaticamente el CITE interno al guardar.</p>
            </div>

            <div class="rounded-lg border border-gray-200 p-4">
                <label class="block text-sm font-medium text-gray-700 mb-3">Instrucciones para circular interna</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                    @foreach(\App\Models\Ticket::PHYSICAL_INSTRUCTIONS as $number => $label)
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="physical_instructions[]" value="{{ $number }}"
                                @checked(in_array((string) $number, old('physical_instructions', []), true))
                                class="rounded border-gray-300 text-blue-600">
                            <span class="w-5 text-right font-medium">{{ $number }}</span>
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripcion breve *</label>
                <textarea name="message" required rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                    placeholder="Resume el contenido de la solicitud fisica.">{{ old('message') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">PDF de solicitud fisica</label>
                <input type="file" name="physical_pdf" accept="application/pdf"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <p class="mt-1 text-xs text-gray-500">Adjunta el escaneo de la solicitud escrita en PDF. Maximo 10 MB.</p>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('tickets.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="bg-blue-600 px-5 py-2 rounded-lg font-semibold text-white hover:bg-blue-700">
                    <i class="fas fa-file-circle-plus mr-2"></i>Crear ticket fisico
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function filterPhysicalAssignees() {
        var departmentId = document.getElementById('physicalDepartment').value;
        var assignee = document.getElementById('physicalAssignee');
        var visibleCount = 0;

        assignee.querySelectorAll('option').forEach(function(option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            var isAdmin = option.dataset.role === 'admin';
            var departmentIds = JSON.parse(option.dataset.departments || '[]').map(String);
            var canHandle = departmentId && (isAdmin || departmentIds.includes(departmentId));
            option.hidden = !canHandle;

            if (!canHandle && option.selected) {
                option.selected = false;
            }

            if (canHandle) {
                visibleCount++;
            }
        });

        document.getElementById('assigneeHelp').textContent = departmentId
            ? (visibleCount > 0 ? 'Solo se muestran destinatarios que atienden este tipo de soporte.' : 'No hay soporte asignado para este tipo. Puedes seleccionar un administrador si aparece disponible.')
            : 'Primero selecciona el tipo de soporte para filtrar los destinatarios disponibles.';
    }

    document.getElementById('physicalDepartment').addEventListener('change', filterPhysicalAssignees);
    filterPhysicalAssignees();
</script>
@endpush
