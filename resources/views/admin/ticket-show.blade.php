@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->ticket_id)

@section('content')
<div class="max-w-5xl mx-auto px-4">
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b px-6 py-4">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-xl font-bold">{{ $ticket->subject }}</h1>
                    <div class="text-sm text-gray-500 mt-1">
                        Ticket #{{ $ticket->ticket_id }} - {{ $ticket->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
                <div class="text-right">
                    <span class="inline-block px-3 py-1 text-sm rounded-full
                        @if($ticket->status === 'open') bg-yellow-100 text-yellow-800
                        @elseif($ticket->status === 'assigned') bg-indigo-100 text-indigo-800
                        @elseif($ticket->status === 'in_progress') bg-blue-100 text-blue-800
                        @elseif($ticket->status === 'send_note') bg-cyan-100 text-cyan-800
                        @elseif($ticket->status === 'waiting_user') bg-purple-100 text-purple-800
                        @elseif($ticket->status === 'resolved') bg-green-100 text-green-800
                        @elseif($ticket->status === 'reopened') bg-orange-100 text-orange-800
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ $ticket->statusLabel() }}
                    </span>
                </div>
            </div>
        </div>

        <div class="p-6">
            @include('tickets.partials.rustdesk-panel')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6 pb-6 border-b">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cambiar Estado</label>
                    <form method="POST" action="{{ route('admin.ticket.status', $ticket) }}">
                        @csrf
                        <div class="flex space-x-2">
                            <select name="status" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                @foreach(\App\Models\Ticket::STATUS_LABELS as $value => $label)
                                    <option value="{{ $value }}" {{ $ticket->status == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="bg-blue-500 text-white px-3 py-2 rounded-lg hover:bg-blue-600" title="Guardar estado">
                                <i class="fas fa-save"></i>
                            </button>
                        </div>
                    </form>
                </div>

                @if(auth()->user()->isAdmin())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Asignar a</label>
                        <form method="POST" action="{{ route('admin.ticket.assign', $ticket) }}">
                            @csrf
                            <div class="flex space-x-2">
                                <select name="assigned_to" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg">
                                    <option value="">Sin asignar</option>
                                    @foreach($agents as $agent)
                                        <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>
                                            {{ $agent->name }} ({{ $agent->role === 'admin' ? 'Administrador' : 'Soporte' }})
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="bg-green-500 text-white px-3 py-2 rounded-lg hover:bg-green-600" title="Asignar ticket">
                                    <i class="fas fa-user-check"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                @else
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Asignacion</label>
                        <div class="text-sm text-gray-700 mb-2">
                            {{ $ticket->assignee ? 'Asignado a '.$ticket->assignee->name : 'Sin asignar' }}
                        </div>
                        @if(! $ticket->assigned_to && ! in_array($ticket->status, ['resolved', 'closed'], true))
                            <form method="POST" action="{{ route('admin.ticket.assign-self', $ticket) }}">
                                @csrf
                                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                                    <i class="fas fa-user-check mr-2"></i>Asignarme
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                <div class="text-sm">
                    <p><strong>Prioridad:</strong>
                        <span class="px-2 py-1 text-xs rounded-full
                            @if($ticket->priority === 'urgent') bg-red-100 text-red-800
                            @elseif($ticket->priority === 'high') bg-orange-100 text-orange-800
                            @elseif($ticket->priority === 'medium') bg-yellow-100 text-yellow-800
                            @else bg-green-100 text-green-800 @endif">
                            {{ $ticket->priorityLabel() }}
                        </span>
                    </p>
                    <p><strong>Tipo de soporte:</strong> {{ $ticket->department->name }}</p>
                    <p><strong>Categoria:</strong> {{ $ticket->category->name ?? 'No aplica' }}</p>
                    <p><strong>Activo:</strong> {{ $ticket->asset ? $ticket->asset->asset_tag.' - '.$ticket->asset->name : 'No aplica' }}</p>
                    <p><strong>Proveedor:</strong> {{ $ticket->supplier?->name ?? 'No aplica' }}</p>
                    <p><strong>Vence SLA:</strong> {{ $ticket->due_at?->format('d/m/Y H:i') ?? 'Sin SLA' }}</p>
                    @if($ticket->request_channel === 'physical')
                        <p><strong>CITE interno:</strong> {{ $ticket->internal_cite ?? 'Sin CITE' }}</p>
                        <p><strong>CITE circular:</strong> {{ $ticket->circular_cite ?? 'Sin registrar' }}</p>
                        <p><strong>Remitente:</strong> {{ $ticket->user?->name ?? 'Sin remitente' }}</p>
                        <p><strong>Registrado por:</strong> {{ $ticket->creator?->name ?? 'Secretaria DTI' }}</p>
                    @endif
                    @if($ticket->isOverdue())
                        <p class="mt-1 text-red-600 font-semibold"><i class="fas fa-triangle-exclamation mr-1"></i>SLA vencido</p>
                    @endif
                    @if(auth()->user()->isManager())
                        <a href="{{ route('admin.changes', ['ticket_id' => $ticket->id]) }}" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            <i class="fas fa-code-branch"></i>Generar cambio
                        </a>
                    @endif
                </div>
            </div>

            <div class="mb-6 pb-6 border-b">
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-500"></i>
                    </div>
                    <div class="flex-1">
                        <div>
                            <span class="font-semibold">{{ $ticket->request_channel === 'physical' ? ($ticket->creator?->name ?? 'Secretaria DTI') : $ticket->user->name }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="mt-2 text-gray-700">
                            {!! nl2br(e($ticket->message)) !!}
                        </div>
                        @if($ticket->request_channel === 'physical')
                            <div class="mt-3 rounded-lg border border-indigo-100 bg-indigo-50 p-3 text-sm text-indigo-900">
                                <div><strong>CITE interno:</strong> {{ $ticket->internal_cite ?? 'Sin CITE' }}</div>
                                <div><strong>CITE circular interna:</strong> {{ $ticket->circular_cite ?? 'Sin registrar' }}</div>
                                <div><strong>Referencia:</strong> {{ $ticket->reference ?? $ticket->subject }}</div>
                                <div><strong>Remitente:</strong> {{ $ticket->user?->name ?? 'Sin remitente' }}</div>
                                <div><strong>Registrado por:</strong> {{ $ticket->creator?->name ?? 'Secretaria DTI' }}</div>
                                @if($ticket->physical_pdf_path)
                                    <a href="{{ route('tickets.physical.pdf', $ticket) }}" target="_blank" class="mt-2 inline-flex items-center gap-2 text-indigo-700 hover:text-indigo-900">
                                        <i class="fas fa-file-pdf"></i>Ver solicitud fisica en PDF
                                    </a>
                                @endif
                                <a href="{{ route('tickets.physical.print', $ticket) }}" target="_blank" class="mt-2 ml-3 inline-flex items-center gap-2 text-indigo-700 hover:text-indigo-900">
                                    <i class="fas fa-print"></i>Imprimir formato
                                </a>
                            </div>
                        @endif
                        @if($ticket->image_path)
                            <div class="mt-3">
                                <a href="{{ route('tickets.image', $ticket) }}" target="_blank" class="inline-block">
                                    <img src="{{ route('tickets.image', $ticket) }}" alt="Imagen adjunta del ticket"
                                        class="max-h-64 rounded-lg border border-gray-200 shadow-sm">
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="space-y-4 mb-6 max-h-96 overflow-y-auto" id="messages-container">
                @foreach($messages as $message)
                    <div class="flex items-start space-x-3 {{ $message->user_id === auth()->id() ? 'flex-row-reverse space-x-reverse' : '' }}" data-message-id="{{ $message->id }}">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas {{ $message->user_id === auth()->id() ? 'fa-user' : ($message->user->isAgent() ? 'fa-headset' : 'fa-user') }} text-gray-500 text-sm"></i>
                        </div>
                        <div class="flex-1 max-w-[75%]">
                            <div class="{{ $message->user_id === auth()->id() ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800' }} rounded-lg p-3">
                                <div class="text-sm">{!! nl2br(e($message->message)) !!}</div>
                                @if($message->image_path)
                                    <a href="{{ route('tickets.messages.image', [$ticket, $message]) }}" target="_blank" class="block mt-3">
                                        <img src="{{ route('tickets.messages.image', [$ticket, $message]) }}" alt="Imagen adjunta"
                                            class="max-h-56 rounded-lg border border-white/30 shadow-sm">
                                    </a>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $message->user->name }} - {{ $message->created_at->format('d/m/Y H:i') }}
                                @if($message->is_read && $message->user_id !== auth()->id())
                                    <i class="fas fa-check-double text-green-500 ml-1" title="Leido"></i>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($cannedResponses->count() > 0)
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-2">Respuestas Rapidas</label>
                <div class="flex flex-wrap gap-2">
                    @foreach($cannedResponses as $response)
                        <button type="button" data-content="{{ e($response->content) }}" onclick="insertCannedResponse(this.dataset.content)"
                            class="text-xs bg-white border border-gray-300 px-3 py-1 rounded-full hover:bg-blue-50 hover:border-blue-300">
                            {{ $response->title }}
                        </button>
                    @endforeach
                </div>
            </div>
            @endif

            @if($ticket->status !== 'closed')
                <div class="border-t pt-6">
                    <h3 class="font-medium mb-3">Responder</h3>
                    <form method="POST" action="{{ route('tickets.message', $ticket) }}" id="messageForm" enctype="multipart/form-data">
                        @csrf
                        <textarea name="message" id="message" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Escribe tu respuesta aqui..."></textarea>
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adjuntar imagen</label>
                            <input type="file" name="image" id="image" accept="image/*"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Opcional. Puedes enviar solo texto, solo imagen o ambos.</p>
                        </div>
                        <div class="flex justify-end mt-3">
                            <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                                <i class="fas fa-paper-plane mr-2"></i>Enviar Respuesta
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="text-center">
        <a href="{{ route('admin.tickets') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i>Volver a la lista
        </a>
    </div>
</div>

@push('scripts')
<script>
    function insertCannedResponse(content) {
        var textarea = document.getElementById('message');
        textarea.value = content;
        textarea.focus();
    }

    document.addEventListener('DOMContentLoaded', function() {
        window.setupTicketRealtime({
            ticketId: {{ $ticket->id }},
            currentUserId: {{ auth()->id() }},
            messagesContainerId: 'messages-container',
            formId: 'messageForm',
            textareaId: 'message'
        });
    });
</script>
@endpush
@endsection
