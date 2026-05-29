@extends('layouts.app')

@section('title', 'Nuevo Ticket')

@section('content')
<div class="max-w-3xl mx-auto px-4">
    <div class="bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-6">Crear nuevo Ticket</h1>

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" id="ticketCreateForm">
            @csrf
            <input type="hidden" name="request_token" value="{{ $requestToken }}">
            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Asunto *</label>
                <input type="text" name="subject" required value="{{ old('subject') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Tipo de soporte *</label>
                    <select name="department_id" required id="department" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        <option value="">Selecciona...</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div id="supportInfo" class="hidden rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900"></div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Prioridad *</label>
                <select name="priority" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="low">Baja</option>
                    <option value="medium">Media</option>
                    <option value="high">Alta</option>
                    <option value="urgent">Urgente</option>
                </select>
            </div>

            @if($assets->isNotEmpty() || auth()->user()->isAgent())
                <div class="grid grid-cols-1 {{ auth()->user()->isAgent() ? 'md:grid-cols-2' : '' }} gap-4 mb-4">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Activo relacionado</label>
                        <select name="asset_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">No aplica</option>
                            @foreach($assets as $asset)
                                <option value="{{ $asset->id }}" {{ old('asset_id') == $asset->id ? 'selected' : '' }}>
                                    {{ $asset->asset_tag }} - {{ $asset->name }} {{ $asset->office ? '('.$asset->office->name.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @if(auth()->user()->isAgent())
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Proveedor relacionado</label>
                            <select name="supplier_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="">No aplica</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }} {{ $supplier->rif ? '('.$supplier->rif.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            @endif

            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Mensaje *</label>
                <textarea name="message" required rows="6"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">{{ old('message') }}</textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Mientras mas detalles brindes, mejor soporte podremos darte. Incluye que estabas haciendo, desde cuando ocurre, mensajes de error y cualquier paso que ya intentaste.
                </p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-medium mb-2">Imagen del problema</label>
                <input type="file" name="image" accept="image/*"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                <p class="text-xs text-gray-500 mt-1">Puedes adjuntar una captura o foto del error. Maximo 4 MB.</p>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="{{ route('tickets.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" id="createTicketButton" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 disabled:cursor-not-allowed disabled:opacity-60">
                    <span class="submit-label">Crear Ticket</span>
                    <span class="submit-loading hidden">Creando...</span>
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $supportTypes = $departments->mapWithKeys(function ($dept) {
        return [
            $dept->id => [
                'name' => $dept->name,
                'description' => $dept->description,
            ],
        ];
    });
@endphp

@push('scripts')
<script>
    const supportTypes = @json($supportTypes);

    function appendTextLine(container, text, className) {
        const line = document.createElement('div');
        if (className) {
            line.className = className;
        }
        line.textContent = text;
        container.appendChild(line);
    }

    $('#department').change(function() {
        var deptId = $(this).val();
        var support = supportTypes[deptId];
        var supportInfo = document.getElementById('supportInfo');
        if (support) {
            supportInfo.classList.remove('hidden');
            supportInfo.textContent = '';
            appendTextLine(supportInfo, support.name || 'Tipo de soporte', 'font-semibold');
            appendTextLine(supportInfo, support.description || 'Solicitud dirigida a este tipo de soporte.');
            appendTextLine(supportInfo, 'El administrador o el personal de soporte asignara el tecnico responsable.', 'text-blue-700');
        } else {
            supportInfo.classList.add('hidden');
            supportInfo.textContent = '';
        }
    });

    $('#department').trigger('change');

    $('#ticketCreateForm').on('submit', function() {
        var button = $('#createTicketButton');
        if (button.prop('disabled')) {
            return false;
        }

        button.prop('disabled', true);
        button.find('.submit-label').addClass('hidden');
        button.find('.submit-loading').removeClass('hidden');
    });
</script>
@endpush
@endsection
