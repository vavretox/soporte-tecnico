@extends('layouts.app')

@section('title', 'Gestionar Tickets')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Gestionar Tickets</h1>
        <div class="flex space-x-3">
            <a href="{{ route('admin.dashboard') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-chart-line mr-2"></i>Panel
            </a>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="all">Todos</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                            {{ \App\Models\Ticket::STATUS_LABELS[$status] ?? $status }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prioridad</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="all">Todas</option>
                    @foreach($priorities as $priority)
                        <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>
                            {{ \App\Models\Ticket::PRIORITY_LABELS[$priority] ?? $priority }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Asignacion</label>
                <select name="assigned" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Todos</option>
                    <option value="mine" {{ request('assigned') == 'mine' ? 'selected' : '' }}>Mis tickets</option>
                    <option value="unassigned" {{ request('assigned') == 'unassigned' ? 'selected' : '' }}>Sin asignar</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asunto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asignado a</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($tickets as $ticket)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono">{{ $ticket->ticket_id }}</td>
                        <td class="px-6 py-4 text-sm">{{ $ticket->user->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ Str::limit($ticket->subject, 40) }}</td>
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
                            @if($ticket->isOverdue())
                                <div class="mt-1 text-xs font-semibold text-red-600">SLA vencido</div>
                            @endif
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
                        <td class="px-6 py-4 text-sm">
                            {{ $ticket->assignee ? $ticket->assignee->name : 'Sin asignar' }}
                        </td>
                        <td class="px-6 py-4 text-sm">{{ $ticket->created_at->format('d/m/Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-2 min-w-48">
                                <a href="{{ route('admin.ticket.show', $ticket) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100">
                                    <i class="fas fa-eye"></i>Ver
                                </a>

                                @if(auth()->user()->isAdmin())
                                    <form method="POST" action="{{ route('admin.ticket.assign', $ticket) }}" class="flex gap-2">
                                        @csrf
                                        <select name="assigned_to" class="w-full px-2 py-2 text-sm border border-gray-300 rounded-lg">
                                            <option value="">Sin asignar</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}" {{ $ticket->assigned_to == $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="rounded-lg bg-green-600 px-3 py-2 text-white hover:bg-green-700" title="Asignar">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('admin.ticket.delete', $ticket) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Eliminar el ticket {{ $ticket->ticket_id }}? Esta accion no se puede deshacer.')"
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100">
                                            <i class="fas fa-trash"></i>Eliminar
                                        </button>
                                    </form>
                                @elseif(! $ticket->assigned_to)
                                    <form method="POST" action="{{ route('admin.ticket.assign-self', $ticket) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">
                                            <i class="fas fa-user-check"></i>Asignarme
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $tickets->appends(request()->query())->links() }}
    </div>
</div>
@endsection
