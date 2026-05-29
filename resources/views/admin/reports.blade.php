@extends('layouts.app')

@section('title', 'Reportes')

@push('styles')
<style>
    @media print {
        body {
            background: #fff !important;
            color: #000 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        body * {
            visibility: hidden !important;
        }

        #printOnly,
        #printOnly * {
            visibility: visible !important;
        }

        #printOnly {
            display: block !important;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 0 !important;
            margin: 0 !important;
        }

        @page {
            margin: 10mm;
        }

        .screen-report {
            display: none !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        h2 {
            font-size: 13px;
            margin: 12px 0 6px 0;
        }

        .print-header {
            border-bottom: 2px solid #000;
            margin-bottom: 10px;
            padding-bottom: 8px;
        }

        .print-title {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0 0 4px 0;
        }

        .print-meta {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2px 18px;
            font-size: 10px;
        }

        .print-summary {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin: 8px 0 10px 0;
            font-size: 11px;
        }

        .print-summary div {
            border: 1px solid #000;
            padding: 5px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6 !important;
            font-weight: 700;
        }
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4">
    @php
        $periodLabel = match ($period) {
            'month' => 'Mes: '.request('month', now()->format('Y-m')),
            'range' => 'Rango: '.request('from', now()->startOfMonth()->toDateString()).' al '.request('to', now()->toDateString()),
            default => 'Dia: '.request('date', now()->toDateString()),
        };
        $printedAt = now()->format('d/m/Y H:i');
    @endphp
    <div id="printOnly" class="hidden">
        <div class="print-header">
            <div class="print-title">Reporte de soporte tecnico</div>
            <div class="print-meta">
                <div><strong>Periodo:</strong> {{ $periodLabel }}</div>
                <div><strong>Fecha y hora de impresion:</strong> {{ $printedAt }}</div>
                <div><strong>Impreso por:</strong> {{ auth()->user()?->name ?? 'Usuario' }}</div>
                <div><strong>Modulo:</strong> Reportes</div>
            </div>
            <div class="print-summary">
                <div><strong>Tickets del periodo:</strong> {{ $tickets->count() }}</div>
                <div><strong>Bitacoras del periodo:</strong> {{ $bitacoras->count() }}</div>
            </div>
        </div>

        <h2 class="font-bold mb-2">Tickets del periodo</h2>
        <table class="mb-6">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Ticket</th>
                    <th>Asunto</th>
                    <th>Tipo</th>
                    <th>Soporte</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tickets as $ticket)
                    <tr>
                        <td>{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $ticket->ticket_id }}</td>
                        <td>{{ $ticket->subject }}</td>
                        <td>{{ $ticket->department?->name ?? 'No aplica' }}</td>
                        <td>{{ $ticket->assignee?->name ?? 'Sin asignar' }}</td>
                        <td>{{ $ticket->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No hay tickets para el periodo seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2 class="font-bold mb-2">Bitacoras del periodo</h2>
        <table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Asunto</th>
                    <th>Tipo</th>
                    <th>Soporte</th>
                    <th>Funcionario</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($bitacoras as $bitacora)
                    <tr>
                        <td>{{ $bitacora->reported_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $bitacora->title }}</td>
                        <td>{{ $bitacora->department?->name ?? 'No aplica' }}</td>
                        <td>{{ $bitacora->technician?->name ?? 'No aplica' }}</td>
                        <td>{{ $bitacora->user?->name ?? 'No aplica' }}</td>
                        <td>{{ $bitacora->statusLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No hay bitacoras para el periodo seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="screen-report">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Reportes de soporte</h1>
            <p class="text-sm text-gray-500 mt-1">Tablas de tickets y bitacoras por periodo para imprimir o exportar.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <button type="button" onclick="window.print()" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900">
                <i class="fas fa-print mr-2"></i>Imprimir
            </button>
            <a href="{{ route('admin.reports.export', request()->query()) }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-download mr-2"></i>Exportar CSV
            </a>
        </div>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-5 gap-4 print:hidden">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Periodo</label>
            <select name="period" id="period" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="day" {{ request('period', 'day') === 'day' ? 'selected' : '' }}>Dia</option>
                <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>Mes</option>
                <option value="range" {{ request('period') === 'range' ? 'selected' : '' }}>Rango</option>
            </select>
        </div>
        <div class="period-field" data-period-field="day">
            <label class="block text-sm font-medium text-gray-700 mb-1">Dia</label>
            <input type="date" name="date" value="{{ request('date', now()->toDateString()) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div class="period-field" data-period-field="month">
            <label class="block text-sm font-medium text-gray-700 mb-1">Mes</label>
            <input type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div class="period-field" data-period-field="range">
            <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
            <input type="date" name="from" value="{{ request('from', now()->startOfMonth()->toDateString()) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div class="period-field" data-period-field="range">
            <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
            <input type="date" name="to" value="{{ request('to', now()->toDateString()) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
        </div>
        <div class="flex items-end gap-3">
            <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Generar</button>
            <a href="{{ route('admin.reports') }}" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50">Limpiar</a>
        </div>
    </form>

    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="border-b px-6 py-4">
            <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                <h2 class="font-semibold">Tickets del periodo</h2>
                <span class="text-sm text-gray-500">{{ $tickets->count() }} registros</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asunto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Soporte</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tickets as $ticket)
                        <tr>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">{{ $ticket->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm font-mono">{{ $ticket->ticket_id }}</td>
                            <td class="px-6 py-4 text-sm">{{ $ticket->subject }}</td>
                            <td class="px-6 py-4 text-sm">{{ $ticket->department?->name ?? 'No aplica' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $ticket->assignee?->name ?? 'Sin asignar' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $ticket->statusLabel() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay tickets para el periodo seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="border-b px-6 py-4">
            <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                <h2 class="font-semibold">Bitacoras del periodo</h2>
                <span class="text-sm text-gray-500">{{ $bitacoras->count() }} registros</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asunto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Soporte</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Funcionario</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($bitacoras as $bitacora)
                        <tr>
                            <td class="px-6 py-4 text-sm whitespace-nowrap">{{ $bitacora->reported_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm">
                                <div class="font-medium">{{ $bitacora->title }}</div>
                                <div class="text-xs text-gray-500">{{ Str::limit($bitacora->actions_taken, 80) }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $bitacora->department?->name ?? 'No aplica' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $bitacora->technician?->name ?? 'No aplica' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $bitacora->user?->name ?? 'No aplica' }}</td>
                            <td class="px-6 py-4 text-sm">{{ $bitacora->statusLabel() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay bitacoras para el periodo seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>

@push('scripts')
<script>
    function syncPeriodFields() {
        var selected = document.getElementById('period').value;
        document.querySelectorAll('[data-period-field]').forEach(function (field) {
            field.classList.toggle('hidden', field.dataset.periodField !== selected);
        });
    }

    document.getElementById('period').addEventListener('change', syncPeriodFields);
    syncPeriodFields();
</script>
@endpush
@endsection
