@extends('layouts.app')

@section('title', 'Panel de Administracion')

@section('content')
<div class="max-w-7xl mx-auto px-4">
    <h1 class="text-2xl font-bold mb-6">Panel de Control</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['total_tickets'] }}</div>
            <div class="text-sm text-gray-600">Total Tickets</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-yellow-600">{{ $stats['open_tickets'] }}</div>
            <div class="text-sm text-gray-600">Abiertos</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-blue-600">{{ $stats['in_progress'] }}</div>
            <div class="text-sm text-gray-600">En Progreso</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-green-600">{{ $stats['resolved_today'] }}</div>
            <div class="text-sm text-gray-600">Resueltos Hoy</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-orange-600">{{ $stats['unassigned'] }}</div>
            <div class="text-sm text-gray-600">Sin Asignar</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-red-600">{{ $stats['urgent'] }}</div>
            <div class="text-sm text-gray-600">Urgentes</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <div class="text-2xl font-bold text-red-700">{{ $stats['overdue'] }}</div>
            <div class="text-sm text-gray-600">SLA vencidos</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Mis Tickets Asignados</h2>
            <div class="text-3xl font-bold text-blue-600">{{ $myTickets }}</div>
            <a href="{{ route('admin.tickets', ['assigned' => 'mine']) }}" class="text-sm text-blue-500 hover:text-blue-700 mt-2 inline-block">
                Ver mis tickets
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Tickets Sin Asignar</h2>
            <div class="text-3xl font-bold text-orange-600">{{ $stats['unassigned'] }}</div>
            <a href="{{ route('admin.tickets', ['assigned' => 'unassigned']) }}" class="text-sm text-blue-500 hover:text-blue-700 mt-2 inline-block">
                Asignar tickets
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Tickets Urgentes</h2>
            <div class="text-3xl font-bold text-red-600">{{ $stats['urgent'] }}</div>
            <a href="{{ route('admin.tickets', ['priority' => 'urgent']) }}" class="text-sm text-blue-500 hover:text-blue-700 mt-2 inline-block">
                Ver urgentes
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-col gap-1 mb-5">
                <h2 class="text-lg font-semibold">Productividad por tipo de soporte</h2>
                <p class="text-sm text-gray-500">Comparativa por tickets, tickets resueltos/cerrados y bitacoras.</p>
            </div>
            @php
                $departmentChartRows = $departmentProductivity->take(10);
                $departmentChartMax = max(1, (int) $departmentChartRows->flatMap(fn ($row) => [$row['tickets'], $row['resolved'], $row['bitacoras']])->max());
            @endphp
            <div class="mb-4 flex flex-wrap gap-3 text-xs text-gray-600">
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-emerald-500"></span>Tickets</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-sky-500"></span>Resueltos</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-amber-500"></span>Bitacoras</span>
            </div>
            <div class="overflow-x-auto pb-2">
                <div class="dashboard-column-chart min-w-[640px]">
                    @forelse($departmentChartRows as $row)
                        <div class="dashboard-column-group" title="{{ $row['name'] }}">
                            <div class="dashboard-column-values">
                                <div class="dashboard-column bg-emerald-500" style="height: {{ max(3, round(($row['tickets'] / $departmentChartMax) * 100)) }}%"><span>{{ $row['tickets'] }}</span></div>
                                <div class="dashboard-column bg-sky-500" style="height: {{ max(3, round(($row['resolved'] / $departmentChartMax) * 100)) }}%"><span>{{ $row['resolved'] }}</span></div>
                                <div class="dashboard-column bg-amber-500" style="height: {{ max(3, round(($row['bitacoras'] / $departmentChartMax) * 100)) }}%"><span>{{ $row['bitacoras'] }}</span></div>
                            </div>
                            <div class="mt-2 truncate text-center text-xs font-medium text-gray-600">{{ Str::limit($row['name'], 14) }}</div>
                            <div class="text-center text-[11px] text-gray-400">{{ $row['rate'] }}% cierre</div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">No hay datos por tipo de soporte.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex flex-col gap-1 mb-5">
                <h2 class="text-lg font-semibold">Productividad por usuario</h2>
                <p class="text-sm text-gray-500">Comparativa por asignados, resueltos/cerrados y bitacoras.</p>
            </div>
            @php
                $userChartRows = $userProductivity->take(10);
                $userChartMax = max(1, (int) $userChartRows->flatMap(fn ($row) => [$row['assigned'], $row['resolved'], $row['bitacoras']])->max());
            @endphp
            <div class="mb-4 flex flex-wrap gap-3 text-xs text-gray-600">
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-indigo-500"></span>Asignados</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-sky-500"></span>Resueltos</span>
                <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded-sm bg-emerald-500"></span>Bitacoras</span>
            </div>
            <div class="overflow-x-auto pb-2">
                <div class="dashboard-column-chart min-w-[640px]">
                    @forelse($userChartRows as $row)
                        <div class="dashboard-column-group" title="{{ $row['name'] }}">
                            <div class="dashboard-column-values">
                                <div class="dashboard-column bg-indigo-500" style="height: {{ max(3, round(($row['assigned'] / $userChartMax) * 100)) }}%"><span>{{ $row['assigned'] }}</span></div>
                                <div class="dashboard-column bg-sky-500" style="height: {{ max(3, round(($row['resolved'] / $userChartMax) * 100)) }}%"><span>{{ $row['resolved'] }}</span></div>
                                <div class="dashboard-column bg-emerald-500" style="height: {{ max(3, round(($row['bitacoras'] / $userChartMax) * 100)) }}%"><span>{{ $row['bitacoras'] }}</span></div>
                            </div>
                            <div class="mt-2 truncate text-center text-xs font-medium text-gray-600">{{ Str::limit($row['name'], 14) }}</div>
                            <div class="text-center text-[11px] text-gray-400">{{ $row['role'] }}</div>
                        </div>
                    @empty
                        <div class="rounded-lg border border-dashed border-gray-200 p-6 text-center text-sm text-gray-500">No hay datos por usuario.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="border-b px-6 py-4">
            <h2 class="text-lg font-semibold">Tickets Recientes</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usuario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asunto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asignado a</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($recentTickets as $ticket)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono">{{ $ticket->ticket_id }}</td>
                        <td class="px-6 py-4 text-sm">{{ $ticket->user->name }}</td>
                        <td class="px-6 py-4 text-sm">{{ Str::limit($ticket->subject, 40) }}</td>
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
                        <td class="px-6 py-4 text-sm">
                            {{ $ticket->assignee ? $ticket->assignee->name : 'Sin asignar' }}
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('admin.ticket.show', $ticket) }}" class="text-blue-600 hover:text-blue-800">
                                Ver <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
<style>
    .dashboard-column-chart {
        display: grid;
        grid-auto-flow: column;
        grid-auto-columns: minmax(76px, 1fr);
        gap: 18px;
        min-height: 300px;
        padding: 18px 12px 4px;
        border-left: 1px solid #e5e7eb;
        border-bottom: 1px solid #e5e7eb;
        background:
            linear-gradient(to top, rgba(148, 163, 184, .22) 1px, transparent 1px) 0 0 / 100% 25%,
            linear-gradient(180deg, #ffffff, #f8fafc);
        border-radius: 8px;
    }

    .dashboard-column-group {
        display: flex;
        min-width: 0;
        flex-direction: column;
        justify-content: flex-end;
    }

    .dashboard-column-values {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        gap: 5px;
        height: 220px;
    }

    .dashboard-column {
        position: relative;
        width: 16px;
        min-height: 8px;
        border-radius: 5px 5px 0 0;
        box-shadow: inset 0 -8px 14px rgba(15, 23, 42, .12);
        transform-origin: bottom;
        animation: columnGrow 760ms cubic-bezier(.2,.8,.2,1) both;
    }

    .dashboard-column span {
        position: absolute;
        bottom: calc(100% + 4px);
        left: 50%;
        transform: translateX(-50%);
        font-size: 10px;
        font-weight: 700;
        color: #475569;
    }

    @keyframes columnGrow {
        from {
            transform: scaleY(.08);
            opacity: .35;
        }
        to {
            transform: scaleY(1);
            opacity: 1;
        }
    }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.dashboard-column').forEach(function(bar, index) {
    bar.style.animationDelay = (index * 45) + 'ms';
});
</script>
@endpush
@endsection
