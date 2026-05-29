@extends('layouts.app')

@section('title', 'Ticket #' . $ticket->ticket_id)

@section('content')
<div class="max-w-4xl mx-auto px-4">
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
                    @if($ticket->status !== 'closed')
                        <form method="POST" action="{{ route('tickets.close', $ticket) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="text-sm text-red-600 hover:text-red-800"
                                onclick="return confirm('Cerrar este ticket?')">
                                <i class="fas fa-times-circle mr-1"></i>Cerrar Ticket
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6">
            <div class="mb-6 pb-6 border-b">
                <div class="flex items-start space-x-3">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-gray-500"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-semibold">{{ $ticket->request_channel === 'physical' ? ($ticket->creator?->name ?? 'Secretaria DTI') : $ticket->user->name }}</span>
                                <span class="text-xs text-gray-500 ml-2">{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                            </div>
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
                                    <a href="{{ asset('storage/'.$ticket->physical_pdf_path) }}" target="_blank" class="mt-2 inline-flex items-center gap-2 text-indigo-700 hover:text-indigo-900">
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
                                <a href="{{ asset('storage/'.$ticket->image_path) }}" target="_blank" class="inline-block">
                                    <img src="{{ asset('storage/'.$ticket->image_path) }}" alt="Imagen adjunta del ticket"
                                        class="max-h-64 rounded-lg border border-gray-200 shadow-sm">
                                </a>
                            </div>
                        @endif
                        <div class="mt-2 text-xs text-gray-500">
                            <span class="inline-block px-2 py-0.5 rounded-full bg-gray-100">
                                <i class="fas fa-tag mr-1"></i>{{ $ticket->department->name }}
                            </span>
                            <span class="inline-block px-2 py-0.5 rounded-full bg-gray-100 ml-2">
                                <i class="fas fa-flag mr-1"></i>{{ $ticket->priorityLabel() }}
                            </span>
                        </div>
                        <div class="mt-2 text-xs text-gray-500">
                            <span class="inline-block px-2 py-0.5 rounded-full bg-gray-100">
                                <i class="fas fa-box mr-1"></i>{{ $ticket->asset ? $ticket->asset->asset_tag.' - '.$ticket->asset->name : 'Sin activo' }}
                            </span>
                            <span class="inline-block px-2 py-0.5 rounded-full bg-gray-100 ml-2">
                                <i class="fas fa-truck-field mr-1"></i>{{ $ticket->supplier?->name ?? 'Sin proveedor' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4 mb-6" id="messages-container">
                @foreach($messages as $message)
                    <div class="flex items-start space-x-3 {{ $message->user_id === auth()->id() ? 'flex-row-reverse space-x-reverse' : '' }}" data-message-id="{{ $message->id }}">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas {{ $message->user_id === auth()->id() ? 'fa-user' : 'fa-headset' }} text-gray-500 text-sm"></i>
                        </div>
                        <div class="flex-1 max-w-[75%]">
                            <div class="{{ $message->user_id === auth()->id() ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-800' }} rounded-lg p-3">
                                <div class="text-sm">{!! nl2br(e($message->message)) !!}</div>
                                @if($message->image_path)
                                    <a href="{{ asset('storage/'.$message->image_path) }}" target="_blank" class="block mt-3">
                                        <img src="{{ asset('storage/'.$message->image_path) }}" alt="Imagen adjunta"
                                            class="max-h-56 rounded-lg border border-white/30 shadow-sm">
                                    </a>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $message->user->name }} - {{ $message->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($ticket->status !== 'closed')
                <div class="border-t pt-6">
                    <h3 class="font-medium mb-3">Responder</h3>
                    <form method="POST" action="{{ route('tickets.message', $ticket) }}" id="messageForm" enctype="multipart/form-data">
                        @csrf
                        <textarea name="message" id="message" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
                            placeholder="Escribe tu mensaje aqui..."></textarea>
                        <div class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Adjuntar imagen</label>
                            <input type="file" name="image" id="image" accept="image/*"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <p class="text-xs text-gray-500 mt-1">Opcional. Puedes enviar solo texto, solo imagen o ambos.</p>
                        </div>
                        <div class="flex justify-end mt-3">
                            <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                                <i class="fas fa-paper-plane mr-2"></i>Enviar
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <div class="text-center">
        <a href="{{ route('tickets.index') }}" class="text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left mr-2"></i>Volver a mis tickets
        </a>
    </div>
</div>

@push('scripts')
<script>
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
