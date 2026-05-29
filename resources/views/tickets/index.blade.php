@extends('layouts.app')

@section('title', 'Mis Tickets')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Mis Tickets</h1>
            <p class="text-sm text-gray-600">Seguimiento de tus solicitudes de soporte y trabajo realizado.</p>
        </div>
        <a href="{{ auth()->user()->isSecretaryDti() ? route('tickets.physical.create') : route('tickets.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 text-center">
            <i class="fas fa-plus mr-2"></i>{{ auth()->user()->isSecretaryDti() ? 'Nuevo Ticket Fisico' : 'Nuevo Ticket' }}
        </a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($tickets->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asunto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo de soporte</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asignado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($tickets as $ticket)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-mono">{{ $ticket->ticket_id }}</td>
                            <td class="px-6 py-4 text-sm font-medium">{{ Str::limit($ticket->subject, 50) }}</td>
                            <td class="px-6 py-4 text-sm">{{ $ticket->department->name }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full
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
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($ticket->priority === 'urgent') bg-red-100 text-red-800
                                    @elseif($ticket->priority === 'high') bg-orange-100 text-orange-800
                                    @elseif($ticket->priority === 'medium') bg-yellow-100 text-yellow-800
                                    @else bg-green-100 text-green-800 @endif">
                                    {{ $ticket->priorityLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $ticket->assignee->name ?? 'Sin asignar' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $ticket->created_at->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-800">
                                    Ver <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center">
                <div class="text-4xl text-gray-300 mb-3"><i class="fas fa-ticket-alt"></i></div>
                <h2 class="text-lg font-semibold mb-1">Aun no tienes tickets</h2>
                <p class="text-gray-600 mb-4">Crea tu primera solicitud para que soporte pueda ayudarte.</p>
                <a href="{{ auth()->user()->isSecretaryDti() ? route('tickets.physical.create') : route('tickets.create') }}" class="inline-block bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    {{ auth()->user()->isSecretaryDti() ? 'Crear Ticket Fisico' : 'Crear Ticket' }}
                </a>
            </div>
        @endif
    </div>

    <div class="mt-4">
        {{ $tickets->links() }}
    </div>
</div>
@endsection
