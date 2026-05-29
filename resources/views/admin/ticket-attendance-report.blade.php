@extends('layouts.app')

@section('title', 'Reporte de Tickets Atendidos')

@push('styles')
<style>
    .report-chart-row {
        display: grid;
        grid-template-columns: minmax(120px, 1fr) 4fr 52px;
        gap: 10px;
        align-items: center;
    }

    .report-chart-bar {
        height: 12px;
        border-radius: 999px;
        background: linear-gradient(90deg, #22c55e, #14b8a6);
        min-width: 4px;
    }

    @media print {
        nav,
        aside,
        #backToTop,
        #scrollProgress,
        .report-actions,
        .report-form,
        .print\:hidden {
            display: none !important;
        }

        body {
            background: #fff !important;
        }

        main {
            padding: 0 !important;
        }

        .app-page {
            animation: none !important;
        }

        .print-report {
            max-width: none !important;
            padding: 0 !important;
        }

        .bg-white,
        .shadow {
            box-shadow: none !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th,
        td {
            border: 1px solid #111;
            padding: 4px;
        }

        .report-chart-bar {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    }
</style>
@endpush

@section('content')
<div class="print-report max-w-7xl mx-auto px-4">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Reporte de tickets atendidos</h1>
            <p class="text-sm text-gray-500 mt-1">Consulta dinamica por rango de fechas, optimizada para pantalla, impresion o PDF.</p>
        </div>
        <div class="report-actions flex flex-wrap gap-3">
            <button type="button" onclick="window.print()" class="rounded-lg bg-gray-800 px-4 py-2 text-white hover:bg-gray-900">
                <i class="fas fa-print mr-2"></i>Imprimir
            </button>
        </div>
    </div>

    <form id="ticketReportForm" class="report-form bg-white rounded-lg shadow p-4 mb-6 grid grid-cols-1 md:grid-cols-4 gap-4" data-url="{{ $dataUrl }}">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio</label>
            <input type="date" name="fecha_inicio" value="{{ $dateFrom }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin</label>
            <input type="date" name="fecha_fin" value="{{ $dateTo }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2">
        </div>
        <div class="flex items-end md:col-span-2">
            <button type="submit" class="rounded-lg bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
                <i class="fas fa-chart-column mr-2"></i>Generar Reporte
            </button>
        </div>
    </form>

    <div id="ticketReportLoading" class="hidden bg-white rounded-lg shadow p-6 text-sm text-gray-600 mb-6">
        <i class="fas fa-spinner fa-spin mr-2"></i>Generando reporte...
    </div>

    <div id="ticketReportContent" class="space-y-6">
        <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">Selecciona un rango de fechas y genera el reporte.</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ticketReportForm = document.getElementById('ticketReportForm');
const ticketReportContent = document.getElementById('ticketReportContent');
const ticketReportLoading = document.getElementById('ticketReportLoading');

ticketReportForm.addEventListener('submit', function(event) {
    event.preventDefault();
    loadTicketReport();
});

document.addEventListener('DOMContentLoaded', loadTicketReport);

async function loadTicketReport() {
    const params = new URLSearchParams(new FormData(ticketReportForm));
    const url = ticketReportForm.dataset.url + '?' + params.toString();

    ticketReportLoading.classList.remove('hidden');

    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (!response.ok) {
            throw new Error('No se pudo generar el reporte.');
        }

        renderTicketReport(await response.json());
    } catch (error) {
        ticketReportContent.innerHTML = '<div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">' + escapeHtml(error.message) + '</div>';
    } finally {
        ticketReportLoading.classList.add('hidden');
    }
}

function renderTicketReport(data) {
    ticketReportContent.innerHTML = `
        <div class="bg-white rounded-lg shadow p-6">
            <div class="mb-4 flex flex-col gap-1">
                <h2 class="text-lg font-semibold">Resumen Ejecutivo</h2>
                <p class="text-sm text-gray-500">Periodo: ${escapeHtml(data.filters.fecha_inicio)} al ${escapeHtml(data.filters.fecha_fin)}</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                ${kpiCard('Tickets atendidos', data.kpis.total_attended, 'fa-ticket')}
                ${kpiCard('SLA a tiempo', data.kpis.sla_on_time_percent + '%', 'fa-clock')}
                ${kpiCard('Tiempo promedio', data.kpis.average_resolution, 'fa-stopwatch')}
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            ${distributionCard('Distribucion por categoria', data.distribution.by_category)}
            ${distributionCard('Distribucion por prioridad', data.distribution.by_priority)}
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b px-6 py-4">
                <h2 class="text-lg font-semibold">Detalle Analitico</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ticket</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creacion</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cierre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prioridad</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Departamento/Empresa</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Soporte</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Categoria</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Asunto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duracion</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">SLA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${data.tickets.length ? data.tickets.map(ticketRow).join('') : '<tr><td colspan="12" class="px-6 py-8 text-center text-gray-500">No hay tickets atendidos en el rango seleccionado.</td></tr>'}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function kpiCard(label, value, icon) {
    return `
        <div class="rounded-lg border border-gray-200 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">${label}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900">${value}</div>
                </div>
                <i class="fas ${icon} text-2xl text-blue-600"></i>
            </div>
        </div>
    `;
}

function distributionCard(title, rows) {
    const max = Math.max(1, ...rows.map(row => row.count));
    const content = rows.length
        ? rows.map(row => `
            <div class="report-chart-row">
                <div class="truncate text-sm font-medium text-gray-700">${escapeHtml(row.label)}</div>
                <div class="rounded-full bg-gray-100"><div class="report-chart-bar" style="width: ${Math.max(4, Math.round((row.count / max) * 100))}%"></div></div>
                <div class="text-right text-sm font-semibold text-gray-700">${row.count}</div>
            </div>
        `).join('')
        : '<div class="rounded-lg border border-dashed border-gray-200 p-4 text-sm text-gray-500">Sin datos.</div>';

    return `
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">${title}</h2>
            <div class="space-y-3">${content}</div>
        </div>
    `;
}

function ticketRow(ticket) {
    return `
        <tr>
            <td class="px-4 py-3 text-sm font-mono">${escapeHtml(ticket.ticket_id)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.created_at)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.closed_at)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.priority)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.status)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.customer)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.department_or_company)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.agent)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.category)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.subject)}</td>
            <td class="px-4 py-3 text-sm">${escapeHtml(ticket.duration)}</td>
            <td class="px-4 py-3 text-sm">${ticket.sla_on_time ? 'A tiempo' : 'Fuera de SLA'}</td>
        </tr>
    `;
}

function escapeHtml(value) {
    return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
@endpush
