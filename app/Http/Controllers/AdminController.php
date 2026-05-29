<?php

namespace App\Http\Controllers;

use App\Models\CannedResponse;
use App\Models\ActionLog;
use App\Models\Asset;
use App\Models\Bitacora;
use App\Models\Category;
use App\Models\ChangeRecord;
use App\Models\Department;
use App\Models\KnowledgeArticle;
use App\Models\NetworkRecord;
use App\Models\Office;
use App\Models\Supplier;
use App\Models\SystemRecord;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    private function authorizeAgent(): void
    {
        abort_unless(Auth::user()?->isAgent(), 403);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->isAdmin(), 403);
    }

    private function authorizeManager(): void
    {
        abort_unless(Auth::user()?->isManager(), 403);
    }

    public function dashboard(): View
    {
        $this->authorizeAgent();

        $ticketQuery = $this->visibleTicketsQuery();
        $stats = [
            'total_tickets' => (clone $ticketQuery)->count(),
            'open_tickets' => (clone $ticketQuery)->where('status', 'open')->count(),
            'in_progress' => (clone $ticketQuery)->where('status', 'in_progress')->count(),
            'resolved_today' => (clone $ticketQuery)->whereDate('resolved_at', today())->count(),
            'overdue' => (clone $ticketQuery)->whereNotIn('status', ['resolved', 'closed'])->whereNotNull('due_at')->where('due_at', '<', now())->count(),
            'unassigned' => (clone $ticketQuery)->whereNull('assigned_to')->whereNotIn('status', ['closed', 'resolved'])->count(),
            'urgent' => (clone $ticketQuery)->where('priority', 'urgent')->whereNotIn('status', ['closed', 'resolved'])->count(),
            'assets' => Asset::count(),
            'changes' => ChangeRecord::whereNotIn('status', ['completed', 'cancelled'])->count(),
        ];

        $myTickets = Ticket::where('assigned_to', Auth::id())->whereNotIn('status', ['closed', 'resolved'])->count();
        $recentTickets = $this->visibleTicketsQuery()->with(['user', 'department', 'assignee'])->latest()->limit(10)->get();
        $departmentTicketStats = (clone $ticketQuery)
            ->selectRaw("department_id, COUNT(*) as total, SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved")
            ->groupBy('department_id')
            ->get()
            ->keyBy('department_id');
        $departmentBitacoraStats = Bitacora::query()
            ->visibleTo(Auth::user())
            ->selectRaw('department_id, COUNT(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');
        $departmentProductivity = Department::orderBy('name')
            ->get()
            ->map(function (Department $department) use ($departmentTicketStats, $departmentBitacoraStats) {
                $tickets = $departmentTicketStats->get($department->id);
                $total = (int) ($tickets->total ?? 0);
                $resolved = (int) ($tickets->resolved ?? 0);
                $bitacoras = (int) ($departmentBitacoraStats[$department->id] ?? 0);

                return [
                    'name' => $department->name,
                    'tickets' => $total,
                    'resolved' => $resolved,
                    'bitacoras' => $bitacoras,
                    'score' => $resolved + $bitacoras,
                    'rate' => $total > 0 ? round(($resolved / $total) * 100) : 0,
                ];
            })
            ->sortByDesc('score')
            ->values();
        $maxDepartmentScore = max(1, (int) $departmentProductivity->max('score'));

        $userProductivity = User::query()
            ->whereIn('role', ['admin', 'support'])
            ->where('is_active', true)
            ->withCount([
                'assignedTickets as assigned_total',
                'assignedTickets as resolved_total' => fn ($query) => $query->whereIn('status', ['resolved', 'closed']),
                'assignedBitacoras as bitacoras_total',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'name' => $user->name,
                'role' => $user->role === 'admin' ? 'Administrador' : 'Soporte',
                'assigned' => (int) $user->assigned_total,
                'resolved' => (int) $user->resolved_total,
                'bitacoras' => (int) $user->bitacoras_total,
                'score' => (int) $user->resolved_total + (int) $user->bitacoras_total,
            ])
            ->sortByDesc('score')
            ->values();
        $maxUserScore = max(1, (int) $userProductivity->max('score'));

        return view('admin.dashboard', compact('stats', 'myTickets', 'recentTickets', 'departmentProductivity', 'maxDepartmentScore', 'userProductivity', 'maxUserScore'));
    }

    public function tickets(Request $request): View
    {
        $this->authorizeAgent();

        $tickets = $this->visibleTicketsQuery()
            ->with(['user', 'department', 'category', 'assignee', 'asset', 'supplier', 'creator'])
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('priority') && $request->priority !== 'all', fn ($query) => $query->where('priority', $request->priority))
            ->when($request->assigned === 'mine', fn ($query) => $query->where('assigned_to', Auth::id()))
            ->when($request->assigned === 'unassigned', fn ($query) => $query->whereNull('assigned_to'))
            ->latest()
            ->paginate(15);

        $statuses = Ticket::STATUSES;
        $priorities = Ticket::PRIORITIES;
        $agents = User::with('supportDepartments')
            ->whereIn('role', ['admin', 'support'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.tickets', compact('tickets', 'statuses', 'priorities', 'agents'));
    }

    public function bitacoras(Request $request): View|RedirectResponse
    {
        $this->authorizeAgent();

        $bitacoras = Bitacora::query()
            ->with(['ticket', 'user', 'technician', 'department', 'office'])
            ->visibleTo(Auth::user())
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('department_id') && $request->department_id !== 'all', fn ($query) => $query->where('department_id', $request->department_id))
            ->latest('reported_at')
            ->latest()
            ->paginate(15);
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();
        $technicians = User::with('supportDepartments')->whereIn('role', ['admin', 'support'])->where('is_active', true)->orderBy('name')->get();
        $statuses = Bitacora::STATUSES;
        $sourceTicket = null;

        if ($request->filled('ticket_id')) {
            $sourceTicket = Ticket::with(['user.office', 'department', 'assignee', 'asset'])
                ->findOrFail($request->integer('ticket_id'));
            abort_unless($this->canSeeTicket($sourceTicket), 403);

            if ($sourceTicket->bitacoras()->exists()) {
                return redirect()
                    ->route('admin.ticket.show', $sourceTicket)
                    ->with('success', 'Este ticket ya tiene una bitacora registrada.');
            }

            if (! $sourceTicket->user?->office_id) {
                return redirect()
                    ->route('admin.ticket.show', $sourceTicket)
                    ->with('error', 'No se puede crear la bitacora: el funcionario del ticket no tiene oficina asignada.');
            }
        }

        return view('admin.bitacoras', compact('bitacoras', 'departments', 'offices', 'users', 'technicians', 'statuses', 'sourceTicket'));
    }

    public function storeBitacora(Request $request): RedirectResponse
    {
        $this->authorizeAgent();

        $data = $this->validateBitacora($request);

        $bitacora = Bitacora::create($data);
        ActionLog::record('bitacora.creada', $bitacora, 'Bitacora creada.', ['ticket_id' => $bitacora->ticket_id]);

        if ($request->filled('return_ticket_id')) {
            $ticket = Ticket::find($request->integer('return_ticket_id'));

            if ($ticket && $this->canSeeTicket($ticket)) {
                return redirect()->route('admin.ticket.show', $ticket)->with('success', 'Bitacora creada correctamente.');
            }
        }

        return back()->with('success', 'Bitacora creada.');
    }

    public function updateBitacora(Request $request, Bitacora $bitacora): RedirectResponse
    {
        $this->authorizeAgent();
        abort_unless(Auth::user()->canViewBitacora($bitacora), 403);

        $bitacora->update($this->validateBitacora($request));
        ActionLog::record('bitacora.actualizada', $bitacora, 'Bitacora actualizada.', ['ticket_id' => $bitacora->ticket_id]);

        if ($request->filled('return_ticket_id')) {
            $ticket = Ticket::find($request->integer('return_ticket_id'));

            if ($ticket && $this->canSeeTicket($ticket)) {
                return redirect()->route('admin.ticket.show', $ticket)->with('success', 'Bitacora actualizada correctamente.');
            }
        }

        return back()->with('success', 'Bitacora actualizada.');
    }

    public function deleteBitacora(Bitacora $bitacora): RedirectResponse
    {
        $this->authorizeAgent();
        abort_unless(Auth::user()->canViewBitacora($bitacora), 403);

        ActionLog::record('bitacora.eliminada', $bitacora, 'Bitacora eliminada.', ['ticket_id' => $bitacora->ticket_id]);
        $bitacora->delete();

        return back()->with('success', 'Bitacora eliminada.');
    }

    public function assets(Request $request): View
    {
        $this->authorizeManager();

        $assets = Asset::with(['office', 'assignee'])
            ->when($request->filled('type') && $request->type !== 'all', fn ($query) => $query->where('type', $request->type))
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('office_id') && $request->office_id !== 'all', fn ($query) => $query->where('office_id', $request->office_id))
            ->when($request->filled('assigned_to') && $request->assigned_to !== 'all', fn ($query) => $query->where('assigned_to', $request->assigned_to))
            ->orderBy('name')
            ->paginate(15);
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get();
        $types = Asset::TYPES;
        $statuses = Asset::STATUSES;

        return view('admin.assets', compact('assets', 'offices', 'users', 'types', 'statuses'));
    }

    public function storeAsset(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        $asset = Asset::create($this->validateAsset($request));
        ActionLog::record('activo.creado', $asset, 'Activo creado.', ['asset_tag' => $asset->asset_tag]);

        return back()->with('success', 'Activo creado.');
    }

    public function updateAsset(Request $request, Asset $asset): RedirectResponse
    {
        $this->authorizeManager();

        $asset->update($this->validateAsset($request, $asset));
        ActionLog::record('activo.actualizado', $asset, 'Activo actualizado.', ['asset_tag' => $asset->asset_tag]);

        return back()->with('success', 'Activo actualizado.');
    }

    public function deleteAsset(Asset $asset): RedirectResponse
    {
        $this->authorizeManager();

        if ($asset->changes()->exists()) {
            return back()->with('error', 'No se puede eliminar un activo con cambios registrados.');
        }

        ActionLog::record('activo.eliminado', $asset, 'Activo eliminado.', ['asset_tag' => $asset->asset_tag]);
        $asset->delete();

        return back()->with('success', 'Activo eliminado.');
    }

    public function exportAssets(Request $request)
    {
        $this->authorizeManager();

        $rows = Asset::with(['office', 'assignee'])
            ->when($request->filled('type') && $request->type !== 'all', fn ($query) => $query->where('type', $request->type))
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('office_id') && $request->office_id !== 'all', fn ($query) => $query->where('office_id', $request->office_id))
            ->when($request->filled('assigned_to') && $request->assigned_to !== 'all', fn ($query) => $query->where('assigned_to', $request->assigned_to))
            ->orderBy('name')
            ->get()
            ->map(fn (Asset $asset) => [
            $asset->asset_tag,
            $asset->name,
            $asset->type,
            $asset->brand,
            $asset->model,
            $asset->serial_number,
            $asset->version,
            $asset->status,
            $asset->office?->name,
            $asset->assignee?->email ?? $asset->assignee?->name,
            $asset->purchase_date?->format('Y-m-d'),
            $asset->warranty_until?->format('Y-m-d'),
            $asset->notes,
        ])->all();

        return $this->csvDownload('inventario.csv', $this->assetCsvHeaders(), $rows);
    }

    public function assetTemplate()
    {
        $this->authorizeManager();

        return $this->csvDownload('plantilla_inventario.csv', $this->assetCsvHeaders(), [[
            'EQ-001',
            'Laptop Dell Latitude',
            'hardware',
            'Dell',
            'Latitude 5420',
            'SN123456',
            '',
            'active',
            'Sistemas',
            'usuario@correo.com',
            '2026-01-15',
            '2027-01-15',
            'Equipo asignado para soporte.',
        ]]);
    }

    public function importAssets(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        [$rows, $error] = $this->readCsvRows($request->file('file')->getRealPath(), $this->assetCsvHeaders());

        if ($error) {
            return back()->with('error', $error);
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $row['office_id'] = $this->resolveOfficeId($row['office'] ?? '');
            $row['assigned_to'] = $this->resolveUserId($row['assigned_to'] ?? '');

            $data = [
                'asset_tag' => $row['asset_tag'] ?? null,
                'name' => $row['name'] ?? null,
                'type' => $row['type'] ?? null,
                'brand' => $row['brand'] ?? null,
                'model' => $row['model'] ?? null,
                'serial_number' => $row['serial_number'] ?? null,
                'version' => $row['version'] ?? null,
                'status' => $row['status'] ?? null,
                'office_id' => $row['office_id'],
                'assigned_to' => $row['assigned_to'],
                'purchase_date' => $row['purchase_date'] ?: null,
                'warranty_until' => $row['warranty_until'] ?: null,
                'notes' => $row['notes'] ?? null,
            ];

            $validator = Validator::make($data, [
                'asset_tag' => ['required', 'string', 'max:80'],
                'name' => ['required', 'string', 'max:255'],
                'type' => ['required', Rule::in(array_keys(Asset::TYPES))],
                'brand' => ['required', 'string', 'max:255'],
                'model' => ['required', 'string', 'max:255'],
                'serial_number' => ['required', 'string', 'max:255'],
                'version' => ['nullable', 'string', 'max:255'],
                'status' => ['required', Rule::in(array_keys(Asset::STATUSES))],
                'office_id' => ['required', 'exists:offices,id'],
                'assigned_to' => ['required', 'exists:users,id'],
                'purchase_date' => ['nullable', 'date'],
                'warranty_until' => ['nullable', 'date'],
                'notes' => ['nullable', 'string'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Fila {$line}: ".$validator->errors()->first();
                continue;
            }

            Asset::updateOrCreate(['asset_tag' => $data['asset_tag']], $data);
            $imported++;
        }

        $message = "Importacion de inventario completada: {$imported} registros procesados.";

        ActionLog::record('inventario.importado', null, $message, ['errores' => $errors]);

        return back()->with($errors ? 'error' : 'success', $errors ? $message.' Errores: '.implode(' ', array_slice($errors, 0, 5)) : $message);
    }

    public function suppliers(): View
    {
        $this->authorizeManager();

        $suppliers = Supplier::orderBy('name')->paginate(15);

        return view('admin.suppliers', compact('suppliers'));
    }

    public function storeSupplier(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        $supplier = Supplier::create($this->validateSupplier($request));
        ActionLog::record('proveedor.creado', $supplier, 'Proveedor creado.', ['rif' => $supplier->rif]);

        return back()->with('success', 'Proveedor creado.');
    }

    public function updateSupplier(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeManager();

        $supplier->update($this->validateSupplier($request));
        ActionLog::record('proveedor.actualizado', $supplier, 'Proveedor actualizado.', ['rif' => $supplier->rif]);

        return back()->with('success', 'Proveedor actualizado.');
    }

    public function deleteSupplier(Supplier $supplier): RedirectResponse
    {
        $this->authorizeManager();

        ActionLog::record('proveedor.eliminado', $supplier, 'Proveedor eliminado.', ['rif' => $supplier->rif]);
        $supplier->delete();

        return back()->with('success', 'Proveedor eliminado.');
    }

    public function exportSuppliers()
    {
        $this->authorizeManager();

        $rows = Supplier::orderBy('name')->get()->map(fn (Supplier $supplier) => [
            $supplier->name,
            $supplier->rif,
            $supplier->contact_name,
            $supplier->email,
            $supplier->phone,
            $supplier->address,
            $supplier->notes,
            $supplier->is_active ? '1' : '0',
        ])->all();

        return $this->csvDownload('proveedores.csv', $this->supplierCsvHeaders(), $rows);
    }

    public function supplierTemplate()
    {
        $this->authorizeManager();

        return $this->csvDownload('plantilla_proveedores.csv', $this->supplierCsvHeaders(), [[
            'Proveedor Demo',
            'J-12345678-9',
            'Persona Contacto',
            'contacto@proveedor.com',
            '0412-0000000',
            'Direccion fiscal del proveedor',
            'Notas opcionales',
            '1',
        ]]);
    }

    public function importSuppliers(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
        ]);

        [$rows, $error] = $this->readCsvRows($request->file('file')->getRealPath(), $this->supplierCsvHeaders());

        if ($error) {
            return back()->with('error', $error);
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $data = [
                'name' => $row['name'] ?? null,
                'rif' => $row['rif'] ?? null,
                'contact_name' => $row['contact_name'] ?? null,
                'email' => $row['email'] ?? null,
                'phone' => $row['phone'] ?? null,
                'address' => $row['address'] ?? null,
                'notes' => $row['notes'] ?? null,
                'is_active' => $this->csvBoolean($row['is_active'] ?? '1'),
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'rif' => ['required', 'string', 'max:80'],
                'contact_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'phone' => ['required', 'string', 'max:80'],
                'address' => ['required', 'string'],
                'notes' => ['nullable', 'string'],
                'is_active' => ['boolean'],
            ]);

            if ($validator->fails()) {
                $errors[] = "Fila {$line}: ".$validator->errors()->first();
                continue;
            }

            Supplier::updateOrCreate(['rif' => $data['rif']], $data);
            $imported++;
        }

        $message = "Importacion de proveedores completada: {$imported} registros procesados.";
        ActionLog::record('proveedores.importados', null, $message, ['errores' => $errors]);

        return back()->with($errors ? 'error' : 'success', $errors ? $message.' Errores: '.implode(' ', array_slice($errors, 0, 5)) : $message);
    }

    public function changes(Request $request): View
    {
        $this->authorizeManager();

        $sourceTicket = null;

        if ($request->filled('ticket_id')) {
            $sourceTicket = Ticket::with(['user', 'department', 'assignee', 'asset'])->findOrFail($request->ticket_id);
            abort_unless($this->canSeeTicket($sourceTicket), 403);
        }

        $changes = ChangeRecord::with(['ticket', 'requester', 'assignee', 'department', 'asset'])
            ->when($request->filled('status') && $request->status !== 'all', fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('priority') && $request->priority !== 'all', fn ($query) => $query->where('priority', $request->priority))
            ->when($request->filled('ticket_id'), fn ($query) => $query->where('ticket_id', $request->ticket_id))
            ->latest('scheduled_at')
            ->latest()
            ->paginate(15);
        $users = User::where('is_active', true)->orderBy('name')->get();
        $technicians = User::whereIn('role', ['admin', 'support'])->where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $assets = Asset::orderBy('name')->get();
        $statuses = ChangeRecord::STATUSES;
        $priorities = ChangeRecord::PRIORITIES;

        return view('admin.changes', compact('changes', 'users', 'technicians', 'departments', 'assets', 'statuses', 'priorities', 'sourceTicket'));
    }

    public function reports(Request $request): View
    {
        $this->authorizeAgent();

        $period = $request->input('period', 'day');
        $user = Auth::user();
        $query = Bitacora::query()
            ->with(['ticket', 'user', 'technician', 'department', 'office'])
            ->visibleTo($user);
        $ticketQuery = Ticket::query()
            ->with(['user', 'department', 'assignee'])
            ->reportVisibleTo($user);

        if ($period === 'month') {
            $month = $request->input('month', now()->format('Y-m'));
            $query->whereYear('reported_at', substr($month, 0, 4))
                ->whereMonth('reported_at', substr($month, 5, 2));
            $ticketQuery->whereYear('created_at', substr($month, 0, 4))
                ->whereMonth('created_at', substr($month, 5, 2));
        } elseif ($period === 'range') {
            $from = $request->input('from', now()->startOfMonth()->toDateString());
            $to = $request->input('to', now()->toDateString());
            $query->whereBetween('reported_at', [
                "{$from} 00:00:00",
                "{$to} 23:59:59",
            ]);
            $ticketQuery->whereBetween('created_at', [
                "{$from} 00:00:00",
                "{$to} 23:59:59",
            ]);
        } else {
            $date = $request->input('date', now()->toDateString());
            $query->whereDate('reported_at', $date);
            $ticketQuery->whereDate('created_at', $date);
        }

        $bitacoras = $query->latest('reported_at')->get();
        $tickets = $ticketQuery->latest()->get();

        $stats = [
            'tickets_total' => $tickets->count(),
            'tickets_closed' => $tickets->whereIn('status', ['resolved', 'closed'])->count(),
            'tickets_open' => $tickets->whereIn('status', ['open', 'in_progress'])->count(),
            'bitacoras_total' => $bitacoras->count(),
            'bitacoras_closed' => $bitacoras->whereIn('status', ['resolved', 'closed'])->count(),
            'bitacoras_open' => $bitacoras->whereIn('status', ['open', 'in_progress'])->count(),
            'documented_tickets' => $bitacoras->whereNotNull('ticket_id')->unique('ticket_id')->count(),
        ];

        $byDepartment = $bitacoras
            ->groupBy(fn (Bitacora $bitacora) => $bitacora->department?->name ?? 'Sin tipo')
            ->map->count()
            ->sortDesc();

        $byTechnician = $bitacoras
            ->groupBy(fn (Bitacora $bitacora) => $bitacora->technician?->name ?? 'Sin soporte')
            ->map->count()
            ->sortDesc();

        $ticketsByStatus = $tickets
            ->groupBy(fn (Ticket $ticket) => ucfirst(str_replace('_', ' ', $ticket->status)))
            ->map->count()
            ->sortDesc();

        $ticketsByTechnician = $tickets
            ->groupBy(fn (Ticket $ticket) => $ticket->assignee?->name ?? 'Sin asignar')
            ->map->count()
            ->sortDesc();

        return view('admin.reports', compact('bitacoras', 'tickets', 'stats', 'byDepartment', 'byTechnician', 'ticketsByStatus', 'ticketsByTechnician', 'period'));
    }

    public function ticketAttendanceReport(): View
    {
        $this->authorizeAgent();

        return view('admin.ticket-attendance-report', [
            'dateFrom' => now()->startOfMonth()->toDateString(),
            'dateTo' => now()->toDateString(),
            'dataUrl' => route('admin.reports.tickets-attended.data'),
        ]);
    }

    public function ticketAttendanceReportData(Request $request): JsonResponse
    {
        $this->authorizeAgent();

        $data = $request->validate([
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ]);

        $from = "{$data['fecha_inicio']} 00:00:00";
        $to = "{$data['fecha_fin']} 23:59:59";
        $user = Auth::user();

        $tickets = Ticket::query()
            ->select([
                'id',
                'ticket_id',
                'user_id',
                'department_id',
                'category_id',
                'assigned_to',
                'subject',
                'status',
                'priority',
                'created_at',
                'resolved_at',
                'closed_at',
                'due_at',
            ])
            ->with([
                'user:id,name,office_id',
                'user.office:id,name',
                'department:id,name',
                'category:id,name',
                'assignee:id,name',
            ])
            ->reportVisibleTo($user)
            ->whereIn('status', ['resolved', 'closed'])
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('closed_at', [$from, $to])
                    ->orWhere(function ($resolvedQuery) use ($from, $to) {
                        $resolvedQuery->whereNull('closed_at')
                            ->whereBetween('resolved_at', [$from, $to]);
                    });
            })
            ->orderByRaw('COALESCE(closed_at, resolved_at) DESC')
            ->get();

        $total = $tickets->count();
        $onTime = $tickets->filter(function (Ticket $ticket) {
            $finishedAt = $ticket->closed_at ?: $ticket->resolved_at;

            return $finishedAt && $ticket->due_at && $finishedAt->lessThanOrEqualTo($ticket->due_at);
        })->count();
        $resolutionMinutes = $tickets
            ->map(function (Ticket $ticket) {
                $finishedAt = $ticket->closed_at ?: $ticket->resolved_at;

                return $finishedAt ? $ticket->created_at->diffInMinutes($finishedAt) : null;
            })
            ->filter(fn ($minutes) => $minutes !== null);
        $averageMinutes = (int) round($resolutionMinutes->avg() ?? 0);

        $ticketRows = $tickets->map(function (Ticket $ticket) {
            $finishedAt = $ticket->closed_at ?: $ticket->resolved_at;
            $minutes = $finishedAt ? $ticket->created_at->diffInMinutes($finishedAt) : null;

            return [
                'ticket_id' => $ticket->ticket_id,
                'created_at' => $ticket->created_at->format('d/m/Y H:i'),
                'closed_at' => $finishedAt?->format('d/m/Y H:i') ?? 'Sin cierre',
                'priority' => $ticket->priorityLabel(),
                'status' => $ticket->statusLabel(),
                'customer' => $ticket->user?->name ?? 'Sin usuario',
                'department_or_company' => $ticket->user?->office?->name ?? $ticket->department?->name ?? 'Sin dato',
                'agent' => $ticket->assignee?->name ?? 'Sin asignar',
                'category' => $ticket->category?->name ?? $ticket->department?->name ?? 'Sin categoria',
                'subject' => $ticket->subject,
                'duration' => $minutes !== null ? $this->formatDuration($minutes) : 'Sin cierre',
                'duration_minutes' => $minutes,
                'sla_on_time' => $finishedAt && $ticket->due_at ? $finishedAt->lessThanOrEqualTo($ticket->due_at) : false,
            ];
        })->values();

        $byCategory = $tickets
            ->groupBy(fn (Ticket $ticket) => $ticket->category?->name ?? $ticket->department?->name ?? 'Sin categoria')
            ->map->count()
            ->sortDesc()
            ->map(fn ($count, $label) => ['label' => $label, 'count' => $count])
            ->values();
        $byPriority = $tickets
            ->groupBy(fn (Ticket $ticket) => $ticket->priorityLabel())
            ->map->count()
            ->sortDesc()
            ->map(fn ($count, $label) => ['label' => $label, 'count' => $count])
            ->values();

        return response()->json([
            'filters' => [
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'],
            ],
            'kpis' => [
                'total_attended' => $total,
                'sla_on_time_percent' => $total > 0 ? round(($onTime / $total) * 100, 2) : 0,
                'average_resolution' => $this->formatDuration($averageMinutes),
                'average_resolution_minutes' => $averageMinutes,
            ],
            'distribution' => [
                'by_category' => $byCategory,
                'by_priority' => $byPriority,
            ],
            'tickets' => $ticketRows,
        ]);
    }

    public function auditLogs(Request $request): View
    {
        $this->authorizeAdmin();

        $logs = ActionLog::query()
            ->with('user')
            ->when($request->filled('action'), fn ($query) => $query->where('action', $request->action))
            ->latest()
            ->paginate(30);

        $actions = ActionLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('admin.audit-logs', compact('logs', 'actions'));
    }

    public function exportReports(Request $request)
    {
        $this->authorizeAgent();

        $user = Auth::user();
        $ticketQuery = Ticket::query()
            ->with(['user', 'department', 'assignee', 'asset', 'supplier'])
            ->reportVisibleTo($user);

        $period = $request->input('period', 'day');
        if ($period === 'month') {
            $month = $request->input('month', now()->format('Y-m'));
            $ticketQuery->whereYear('created_at', substr($month, 0, 4))->whereMonth('created_at', substr($month, 5, 2));
        } elseif ($period === 'range') {
            $from = $request->input('from', now()->startOfMonth()->toDateString());
            $to = $request->input('to', now()->toDateString());
            $ticketQuery->whereBetween('created_at', ["{$from} 00:00:00", "{$to} 23:59:59"]);
        } else {
            $ticketQuery->whereDate('created_at', $request->input('date', now()->toDateString()));
        }

        $rows = $ticketQuery->latest()->get()->map(fn (Ticket $ticket) => [
            $ticket->created_at->format('d/m/Y H:i'),
            $ticket->ticket_id,
            $ticket->user?->name,
            $ticket->subject,
            $ticket->department?->name,
            $ticket->priorityLabel(),
            $ticket->statusLabel(),
            $ticket->assignee?->name ?? 'Sin asignar',
            $ticket->asset?->asset_tag,
            $ticket->asset?->name,
            $ticket->supplier?->name,
            $ticket->due_at?->format('d/m/Y H:i'),
            $ticket->first_response_at?->format('d/m/Y H:i'),
            $ticket->resolved_at?->format('d/m/Y H:i'),
            $ticket->closed_at?->format('d/m/Y H:i'),
        ])->all();

        ActionLog::record('reporte.exportado', null, 'Reporte de tickets exportado.', ['period' => $period]);

        return $this->csvDownload('reporte_tickets.csv', [
            'fecha',
            'ticket',
            'funcionario',
            'asunto',
            'tipo',
            'prioridad',
            'estado',
            'soporte',
            'codigo_activo',
            'activo',
            'proveedor',
            'vence_sla',
            'primera_respuesta',
            'resuelto',
            'cerrado',
        ], $rows);
    }

    public function storeChange(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        $data = $this->validateChange($request);

        if (! empty($data['ticket_id'])) {
            $ticket = Ticket::findOrFail($data['ticket_id']);
            abort_unless($this->canSeeTicket($ticket), 403);
        }

        ChangeRecord::create($data);

        return back()->with('success', 'Cambio creado.');
    }

    public function updateChange(Request $request, ChangeRecord $change): RedirectResponse
    {
        $this->authorizeManager();

        $data = $this->validateChange($request);

        if (! empty($data['ticket_id'])) {
            $ticket = Ticket::findOrFail($data['ticket_id']);
            abort_unless($this->canSeeTicket($ticket), 403);
        }

        $change->update($data);

        return back()->with('success', 'Cambio actualizado.');
    }

    public function deleteChange(ChangeRecord $change): RedirectResponse
    {
        $this->authorizeManager();

        $change->delete();

        return back()->with('success', 'Cambio eliminado.');
    }

    public function network(Request $request): View
    {
        $this->authorizeManager();

        $records = NetworkRecord::query()
            ->when($request->filled('connection_type') && $request->connection_type !== 'all', function ($query) use ($request) {
                $query->where(function ($scope) use ($request) {
                    $scope->where('connection_type', $request->connection_type)
                        ->orWhere('connection_types', 'like', '%"'.$request->connection_type.'"%');
                });
            })
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->q);

                $query->where(function ($scope) use ($search) {
                    $scope->where('responsible', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('mac_address', 'like', "%{$search}%")
                        ->orWhere('network_interfaces', 'like', "%{$search}%")
                        ->orWhere('hostname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);
        $connectionTypes = NetworkRecord::CONNECTION_TYPES;

        return view('admin.network', compact('records', 'connectionTypes'));
    }

    public function storeNetwork(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        NetworkRecord::create($this->validateNetworkRecord($request));

        return back()->with('success', 'Registro de red creado.');
    }

    public function updateNetwork(Request $request, NetworkRecord $networkRecord): RedirectResponse
    {
        $this->authorizeManager();

        $networkRecord->update($this->validateNetworkRecord($request));

        return back()->with('success', 'Registro de red actualizado.');
    }

    public function deleteNetwork(NetworkRecord $networkRecord): RedirectResponse
    {
        $this->authorizeManager();

        $networkRecord->delete();

        return back()->with('success', 'Registro de red eliminado.');
    }

    public function systems(Request $request): View
    {
        $this->authorizeManager();

        $records = SystemRecord::query()
            ->with('systems')
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = trim((string) $request->q);

                $query->where(function ($scope) use ($search) {
                    $scope->where('responsible', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhereHas('systems', function ($assetQuery) use ($search) {
                            $assetQuery->where('assets.name', 'like', "%{$search}%")
                                ->orWhere('assets.version', 'like', "%{$search}%")
                                ->orWhere('asset_system_record.username', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15);
        $softwareAssets = Asset::where('type', 'software')->where('status', 'active')->orderBy('name')->get();

        return view('admin.systems', compact('records', 'softwareAssets'));
    }

    public function storeSystem(Request $request): RedirectResponse
    {
        $this->authorizeManager();

        $data = $this->validateSystemRecord($request);
        $record = SystemRecord::create($this->systemRecordPayload($data));
        $record->systems()->sync($this->systemAssetSyncPayload($data));

        return back()->with('success', 'Registro de sistema creado.');
    }

    public function updateSystem(Request $request, SystemRecord $systemRecord): RedirectResponse
    {
        $this->authorizeManager();

        $data = $this->validateSystemRecord($request);
        $systemRecord->update($this->systemRecordPayload($data));
        $systemRecord->systems()->sync($this->systemAssetSyncPayload($data));

        return back()->with('success', 'Registro de sistema actualizado.');
    }

    public function deleteSystem(SystemRecord $systemRecord): RedirectResponse
    {
        $this->authorizeManager();

        $systemRecord->delete();

        return back()->with('success', 'Registro de sistema eliminado.');
    }

    public function showTicket(Ticket $ticket): View
    {
        $this->authorizeAgent();
        abort_unless($this->canSeeTicket($ticket), 403);

        $ticket->load(['user', 'department', 'category', 'assignee', 'asset', 'supplier', 'creator']);
        $messages = $ticket->messages()->with('user')->oldest()->get();
        $agents = User::with('supportDepartments')
            ->whereIn('role', ['admin', 'support'])
            ->where('is_active', true)
            ->where(function ($query) use ($ticket) {
                $query->where('role', 'admin')
                    ->orWhereHas('supportDepartments', fn ($supportQuery) => $supportQuery->whereKey($ticket->department_id))
                    ->orWhere('department_id', $ticket->department_id);
            })
            ->orderBy('name')
            ->get();
        $cannedResponses = CannedResponse::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('department_id')->orWhere('department_id', $ticket->department_id))
            ->orderBy('title')
            ->get();

        $ticket->messages()
            ->where('user_id', '!=', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return view('admin.ticket-show', compact('ticket', 'messages', 'agents', 'cannedResponses'));
    }

    public function assignTicket(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_unless($this->canSeeTicket($ticket), 403);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        if (! empty($data['assigned_to'])) {
            $assignee = User::with('supportDepartments')->findOrFail($data['assigned_to']);

            if (! $assignee->isAdmin() && ! $assignee->handlesDepartment($ticket->department_id)) {
                throw ValidationException::withMessages([
                    'assigned_to' => 'El usuario seleccionado no atiende este tipo de soporte.',
                ]);
            }
        }

        $oldAssignee = $ticket->assigned_to;
        $ticket->update([
            'assigned_to' => $data['assigned_to'] ?: null,
            'status' => $data['assigned_to'] && $ticket->status === 'open' ? 'assigned' : $ticket->status,
        ]);
        ActionLog::record('ticket.asignado', $ticket, 'Asignacion de ticket actualizada.', [
            'from' => $oldAssignee,
            'to' => $ticket->assigned_to,
        ]);

        return back()->with('success', 'Asignación actualizada.');
    }

    public function assignTicketToMe(Ticket $ticket): RedirectResponse
    {
        $this->authorizeAgent();
        abort_unless($this->canSeeTicket($ticket), 403);
        abort_unless(! Auth::user()->isAdmin(), 403);
        abort_unless(Auth::user()->handlesDepartment($ticket->department_id), 403);

        if ($ticket->assigned_to && $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Este ticket ya esta asignado a otro usuario de soporte.');
        }

        $ticket->update([
            'assigned_to' => Auth::id(),
            'status' => $ticket->status === 'open' ? 'assigned' : $ticket->status,
        ]);
        ActionLog::record('ticket.autoasignado', $ticket, 'Ticket autoasignado por soporte.', ['assigned_to' => Auth::id()]);

        return back()->with('success', 'Ticket asignado a ti.');
    }

    public function updateStatus(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorizeAgent();
        abort_unless($this->canSeeTicket($ticket), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(Ticket::STATUSES)],
        ]);

        $now = now();

        $oldStatus = $ticket->status;
        $ticket->update([
            'status' => $data['status'],
            'resolved_at' => in_array($data['status'], ['resolved', 'closed'], true) ? ($ticket->resolved_at ?: $now) : $ticket->resolved_at,
            'closed_at' => $data['status'] === 'closed' ? $now : null,
            'reopened_at' => $data['status'] === 'reopened' ? $now : $ticket->reopened_at,
        ]);
        ActionLog::record('ticket.estado_actualizado', $ticket, 'Estado de ticket actualizado.', [
            'from' => $oldStatus,
            'to' => $ticket->status,
        ]);

        if (in_array($data['status'], ['resolved', 'closed'], true)) {
            return redirect()
                ->route('admin.bitacoras', ['ticket_id' => $ticket->id])
                ->with('success', 'Estado actualizado. Registra la bitacora de atencion para cerrar la informacion del soporte.');
        }

        return back()->with('success', 'Estado actualizado.');
    }

    public function deleteTicket(Ticket $ticket): RedirectResponse
    {
        $this->authorizeAdmin();

        $ticketNumber = $ticket->ticket_id;
        ActionLog::record('ticket.eliminado', $ticket, "Ticket {$ticketNumber} eliminado.");
        $ticket->delete();

        return back()->with('success', "Ticket {$ticketNumber} eliminado.");
    }

    public function departments(): View
    {
        $this->authorizeAdmin();

        $departments = Department::withCount(['tickets', 'bitacoras'])->orderBy('name')->get();

        return view('admin.departments', compact('departments'));
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'description' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Department::create([
            ...$data,
            'slug' => Str::slug($data['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tipo de soporte creado.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department)],
            'description' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department->update([
            ...$data,
            'slug' => Str::slug($data['name']),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Tipo de soporte actualizado.');
    }

    public function deleteDepartment(Department $department): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($department->tickets()->exists() || $department->bitacoras()->exists()) {
            return back()->with('error', 'No se puede eliminar un tipo de soporte con tickets o bitacoras.');
        }

        $department->delete();

        return back()->with('success', 'Tipo de soporte eliminado.');
    }

    public function categories(): View
    {
        $this->authorizeAdmin();

        $categories = Category::with('department')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.categories', compact('categories', 'departments'));
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);

        Category::create([
            ...$data,
            'slug' => Str::slug($data['name']),
        ]);

        return back()->with('success', 'Categoría creada.');
    }

    public function deleteCategory(Category $category): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($category->tickets()->exists()) {
            return back()->with('error', 'No se puede eliminar una categoría con tickets.');
        }

        $category->delete();

        return back()->with('success', 'Categoría eliminada.');
    }

    public function supportStaff(): View
    {
        $this->authorizeAdmin();

        $supportStaff = User::with('supportDepartments')->whereIn('role', ['admin', 'support'])->orderBy('role')->orderBy('name')->get();
        $users = User::where('role', 'user')->with('tickets')->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.support', compact('supportStaff', 'users', 'departments'));
    }

    public function users(): View
    {
        $this->authorizeAdmin();

        $users = User::with(['office', 'supportDepartments'])
            ->with('supportDepartment')
            ->withCount(['tickets', 'assignedTickets'])
            ->orderBy('name')
            ->get();
        $offices = Office::where('is_active', true)->orderBy('name')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $roles = ['user' => 'Funcionario', 'secretary_dti' => 'Secretaria DTI', 'support' => 'Soporte', 'admin' => 'Administrador'];

        return view('admin.users', compact('users', 'offices', 'departments', 'roles'));
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in(['user', 'secretary_dti', 'support', 'admin'])],
            'avatar_icon' => ['required', 'in:user,headset,laptop,shield,briefcase,seedling'],
            'avatar_color' => ['required', 'in:green,amber,orange,teal,slate,rose'],
            'avatar_image' => ['nullable', 'image', 'max:2048'],
            'office_id' => ['required', 'exists:offices,id'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['exists:departments,id'],
            'telegram_chat_id' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['role'] === 'support' && empty($data['department_ids'])) {
            throw ValidationException::withMessages([
                'department_ids' => 'Selecciona al menos un tipo de soporte que atendera este usuario.',
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'must_change_password' => true,
            'password_changed_at' => null,
            'role' => $data['role'],
            'avatar_icon' => $data['avatar_icon'],
            'avatar_color' => $data['avatar_color'],
            'avatar_path' => $request->file('avatar_image')?->store('avatars', 'public'),
            'office_id' => $data['office_id'] ?? null,
            'department_id' => $data['department_ids'][0] ?? null,
            'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->supportDepartments()->sync($data['role'] === 'support' ? ($data['department_ids'] ?? []) : []);

        return back()->with('success', 'Usuario creado.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role' => ['required', Rule::in(['user', 'secretary_dti', 'support', 'admin'])],
            'avatar_icon' => ['required', 'in:user,headset,laptop,shield,briefcase,seedling'],
            'avatar_color' => ['required', 'in:green,amber,orange,teal,slate,rose'],
            'avatar_image' => ['nullable', 'image', 'max:2048'],
            'remove_avatar_image' => ['nullable', 'boolean'],
            'office_id' => ['required', 'exists:offices,id'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['exists:departments,id'],
            'telegram_chat_id' => ['nullable', 'string', 'max:80'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($data['role'] === 'support' && empty($data['department_ids'])) {
            throw ValidationException::withMessages([
                'department_ids' => 'Selecciona al menos un tipo de soporte que atendera este usuario.',
            ]);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'avatar_icon' => $data['avatar_icon'],
            'avatar_color' => $data['avatar_color'],
            'office_id' => $data['office_id'] ?? null,
            'department_id' => $data['role'] === 'support' ? ($data['department_ids'][0] ?? null) : null,
            'telegram_chat_id' => $data['telegram_chat_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
            $payload['must_change_password'] = true;
            $payload['password_changed_at'] = null;
        }

        if ($request->boolean('remove_avatar_image') || $request->hasFile('avatar_image')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $payload['avatar_path'] = null;
        }

        if ($request->hasFile('avatar_image')) {
            $payload['avatar_path'] = $request->file('avatar_image')->store('avatars', 'public');
        }

        $user->update($payload);
        $user->supportDepartments()->sync($data['role'] === 'support' ? ($data['department_ids'] ?? []) : []);

        return back()->with('success', 'Usuario actualizado.');
    }

    public function deleteUser(User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($user->is(Auth::user())) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        if ($user->tickets()->exists() || $user->assignedTickets()->exists() || $user->messages()->exists()) {
            return back()->with('error', 'No se puede eliminar un usuario con actividad. Puedes desactivarlo desde editar.');
        }

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();

        return back()->with('success', 'Usuario eliminado.');
    }

    public function offices(): View
    {
        $this->authorizeAdmin();

        $offices = Office::with(['parent', 'children'])
            ->withCount('users')
            ->orderByRaw("CASE type WHEN 'secretaria' THEN 1 WHEN 'direccion' THEN 2 WHEN 'unidad_descentralizada' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();
        $officeOptions = $offices;
        $officeTree = $this->flattenOffices($offices);
        $types = Office::TYPES;

        return view('admin.offices', compact('offices', 'officeOptions', 'officeTree', 'types'));
    }

    public function storeOffice(Request $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:offices,name'],
            'type' => ['required', Rule::in(array_keys(Office::TYPES))],
            'parent_id' => ['nullable', 'exists:offices,id'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Office::create([
            ...$data,
            'slug' => Str::slug($data['name']),
            'parent_id' => $data['parent_id'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Oficina creada.');
    }

    public function updateOffice(Request $request, Office $office): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('offices', 'name')->ignore($office)],
            'type' => ['required', Rule::in(array_keys(Office::TYPES))],
            'parent_id' => ['nullable', 'exists:offices,id'],
            'description' => ['required', 'string'],
            'location' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $parentId = $data['parent_id'] ?? null;
        if ((int) $parentId === $office->id || $this->parentChainContains($parentId, $office->id)) {
            return back()->with('error', 'La oficina no puede depender de si misma ni de una oficina hija.');
        }

        $office->update([
            ...$data,
            'slug' => Str::slug($data['name']),
            'parent_id' => $parentId,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Oficina actualizada.');
    }

    public function deleteOffice(Office $office): RedirectResponse
    {
        $this->authorizeAdmin();

        if ($office->children()->exists()) {
            return back()->with('error', 'No se puede eliminar una oficina con oficinas dependientes.');
        }

        if ($office->users()->exists()) {
            return back()->with('error', 'No se puede eliminar una oficina con usuarios asignados.');
        }

        $office->delete();

        return back()->with('success', 'Oficina eliminada.');
    }

    private function parentChainContains(null|int|string $parentId, int $officeId): bool
    {
        while ($parentId) {
            if ((int) $parentId === $officeId) {
                return true;
            }

            $parentId = Office::whereKey($parentId)->value('parent_id');
        }

        return false;
    }

    private function visibleTicketsQuery()
    {
        $user = Auth::user();

        return Ticket::query()
            ->visibleTo($user);
    }

    private function canSeeTicket(Ticket $ticket): bool
    {
        $user = Auth::user();

        return $user->canViewTicket($ticket);
    }

    private function validateBitacora(Request $request): array
    {
        if ($request->filled('ticket_id')) {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'ticket_id' => ['required', 'exists:tickets,id'],
                'equipment' => ['nullable', 'string', 'max:255'],
                'description' => ['required', 'string'],
                'actions_taken' => ['required', 'string'],
                'result' => ['nullable', 'string'],
                'status' => ['required', Rule::in(array_keys(Bitacora::STATUSES))],
                'reported_at' => ['required', 'date'],
            ]);

            $ticket = Ticket::with(['user.office', 'asset'])->findOrFail($data['ticket_id']);
            abort_unless($this->canSeeTicket($ticket), 403);

            if (! $ticket->user?->office_id) {
                throw ValidationException::withMessages([
                    'office_id' => 'El funcionario del ticket no tiene oficina asignada. Asignale una oficina antes de registrar la bitacora.',
                ]);
            }

            $data['department_id'] = $ticket->department_id;
            $data['office_id'] = $ticket->user->office_id;
            $data['user_id'] = $ticket->user_id;
            $data['technician_id'] = $ticket->assigned_to ?: Auth::id();
            $data['location'] = $ticket->user->office?->location ?: $ticket->user->office?->name;
            $data['equipment'] = $ticket->asset
                ? trim(($ticket->asset->asset_tag ? $ticket->asset->asset_tag.' - ' : '').$ticket->asset->name)
                : ($data['equipment'] ?? null);

            return $data;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'office_id' => ['required', 'exists:offices,id'],
            'user_id' => ['required', 'exists:users,id'],
            'technician_id' => ['required', 'exists:users,id'],
            'equipment' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'actions_taken' => ['required', 'string'],
            'result' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Bitacora::STATUSES))],
            'reported_at' => ['required', 'date'],
        ]);

        return $data;
    }

    private function assetCsvHeaders(): array
    {
        return ['asset_tag', 'name', 'type', 'brand', 'model', 'serial_number', 'version', 'status', 'office', 'assigned_to', 'purchase_date', 'warranty_until', 'notes'];
    }

    private function supplierCsvHeaders(): array
    {
        return ['name', 'rif', 'contact_name', 'email', 'phone', 'address', 'notes', 'is_active'];
    }

    private function csvDownload(string $filename, array $headers, array $rows)
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function readCsvRows(string $path, array $requiredHeaders): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [[], 'No se pudo leer el archivo.'];
        }

        $headers = fgetcsv($handle);

        if (! $headers) {
            fclose($handle);
            return [[], 'El archivo no tiene encabezados.'];
        }

        $headers = array_map(fn ($header) => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $header)), $headers);
        $missing = array_diff($requiredHeaders, $headers);

        if ($missing) {
            fclose($handle);
            return [[], 'Faltan columnas en la plantilla: '.implode(', ', $missing).'.'];
        }

        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if (! array_filter($values, fn ($value) => trim((string) $value) !== '')) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = trim((string) ($values[$index] ?? ''));
            }
            $rows[] = $row;
        }

        fclose($handle);

        return [$rows, null];
    }

    private function resolveOfficeId(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return Office::whereKey((int) $value)->value('id');
        }

        return Office::where('name', $value)->value('id');
    }

    private function resolveUserId(string $value): ?int
    {
        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return User::whereKey((int) $value)->value('id');
        }

        return User::where('email', $value)->orWhere('name', $value)->value('id');
    }

    private function csvBoolean(string $value): bool
    {
        return in_array(strtolower(trim($value)), ['1', 'si', 'sí', 'true', 'activo', 'active'], true);
    }

    private function validateAsset(Request $request, ?Asset $asset = null): array
    {
        return $request->validate([
            'asset_tag' => ['required', 'string', 'max:80', Rule::unique('assets', 'asset_tag')->ignore($asset)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(array_keys(Asset::TYPES))],
            'brand' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'max:255'],
            'version' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Asset::STATUSES))],
            'office_id' => ['required', 'exists:offices,id'],
            'assigned_to' => ['required', 'exists:users,id'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function validateSupplier(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rif' => ['required', 'string', 'max:80'],
            'contact_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:80'],
            'address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function formatDuration(?int $minutes): string
    {
        $minutes = max(0, (int) $minutes);
        $days = intdiv($minutes, 1440);
        $hours = intdiv($minutes % 1440, 60);
        $remainingMinutes = $minutes % 60;

        return collect([
            $days > 0 ? "{$days}d" : null,
            $hours > 0 ? "{$hours}h" : null,
            $remainingMinutes > 0 || ($days === 0 && $hours === 0) ? "{$remainingMinutes}m" : null,
        ])->filter()->join(' ');
    }

    private function validateChange(Request $request): array
    {
        return $request->validate([
            'ticket_id' => ['nullable', 'exists:tickets,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(ChangeRecord::STATUSES))],
            'priority' => ['required', Rule::in(array_keys(ChangeRecord::PRIORITIES))],
            'requested_by' => ['required', 'exists:users,id'],
            'assigned_to' => ['required', 'exists:users,id'],
            'department_id' => ['required', 'exists:departments,id'],
            'asset_id' => ['nullable', 'exists:assets,id'],
            'scheduled_at' => ['required', 'date'],
            'completed_at' => ['nullable', 'date'],
            'description' => ['required', 'string'],
            'risk' => ['required', 'string'],
            'rollback_plan' => ['required', 'string'],
            'result' => ['nullable', 'string'],
        ]);
    }

    private function validateNetworkRecord(Request $request): array
    {
        $data = $request->validate([
            'responsible' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'ip_address' => ['nullable', 'ip'],
            'mac_address' => ['nullable', 'string', 'max:80'],
            'connection_types' => ['required', 'array', 'min:1'],
            'connection_types.*' => [Rule::in(array_keys(NetworkRecord::CONNECTION_TYPES))],
            'network_interfaces' => ['nullable', 'array'],
            'network_interfaces.*.ip_address' => ['nullable', 'ip'],
            'network_interfaces.*.mac_address' => ['nullable', 'string', 'max:80'],
            'connected_devices' => ['nullable', 'integer', 'min:0'],
            'mobile_devices' => ['nullable', 'integer', 'min:0'],
            'pc_devices' => ['nullable', 'integer', 'min:0'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'has_email' => ['nullable', 'boolean'],
            'email' => ['nullable', 'required_if:has_email,1', 'email', 'max:255'],
            'has_shared_folders' => ['nullable', 'boolean'],
            'shared_folders' => ['nullable', 'required_if:has_shared_folders,1', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['has_email'] = $request->boolean('has_email');
        $data['has_shared_folders'] = $request->boolean('has_shared_folders');
        $data['connection_type'] = $data['connection_types'][0] ?? 'cable';
        $data['connected_devices'] = (int) ($data['connected_devices'] ?? 0);
        $data['mobile_devices'] = (int) ($data['mobile_devices'] ?? 0);
        $data['pc_devices'] = (int) ($data['pc_devices'] ?? 0);
        $data['network_interfaces'] = collect($data['network_interfaces'] ?? [])
            ->map(fn ($row) => [
                'ip_address' => trim((string) ($row['ip_address'] ?? '')),
                'mac_address' => trim((string) ($row['mac_address'] ?? '')),
            ])
            ->filter(fn ($row) => $row['ip_address'] !== '' || $row['mac_address'] !== '')
            ->values()
            ->all();
        $data['ip_address'] = $data['network_interfaces'][0]['ip_address'] ?? null;
        $data['mac_address'] = $data['network_interfaces'][0]['mac_address'] ?? null;

        if (! $data['has_email']) {
            $data['email'] = null;
        }

        if (! $data['has_shared_folders']) {
            $data['shared_folders'] = null;
        }

        return $data;
    }

    private function validateSystemRecord(Request $request): array
    {
        $data = $request->validate([
            'responsible' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'asset_ids' => ['required', 'array', 'min:1'],
            'asset_ids.*' => ['exists:assets,id'],
            'asset_users' => ['nullable', 'array'],
            'asset_users.*' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        foreach ($data['asset_ids'] as $assetId) {
            if (trim((string) ($data['asset_users'][$assetId] ?? '')) === '') {
                $assetName = Asset::find($assetId)?->name ?? 'seleccionado';

                throw ValidationException::withMessages([
                    "asset_users.{$assetId}" => "Ingresa el usuario del sistema {$assetName}.",
                ]);
            }
        }

        return $data;
    }

    private function systemAssetSyncPayload(array $data): array
    {
        $assetUsers = $data['asset_users'] ?? [];

        return collect($data['asset_ids'] ?? [])
            ->mapWithKeys(fn ($assetId) => [
                $assetId => [
                    'username' => trim((string) ($assetUsers[$assetId] ?? '')) ?: null,
                ],
            ])
            ->all();
    }

    private function systemRecordPayload(array $data): array
    {
        return [
            'responsible' => $data['responsible'],
            'position' => $data['position'],
            'system_type' => null,
            'username' => null,
            'checklist' => null,
            'notes' => $data['notes'] ?? null,
        ];
    }

    private function validateKnowledgeArticle(Request $request): array
    {
        return $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:1000'],
            'content' => ['required', 'string'],
            'is_public' => ['nullable', 'boolean'],
        ]);
    }

    private function uniqueKnowledgeSlug(string $title, ?KnowledgeArticle $article = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $counter = 2;

        while (KnowledgeArticle::where('slug', $slug)->when($article, fn ($query) => $query->whereKeyNot($article->id))->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function flattenOffices($offices, null|int $parentId = null, int $depth = 0): array
    {
        return $offices
            ->where('parent_id', $parentId)
            ->flatMap(function (Office $office) use ($offices, $depth) {
                return [
                    ['office' => $office, 'depth' => $depth],
                    ...$this->flattenOffices($offices, $office->id, $depth + 1),
                ];
            })
            ->values()
            ->all();
    }

    public function promoteToSupport(Request $request, User $user): RedirectResponse
    {
        $this->authorizeAdmin();

        $data = $request->validate([
            'department_ids' => ['required', 'array', 'min:1'],
            'department_ids.*' => ['exists:departments,id'],
        ], [
            'department_ids.required' => 'Selecciona al menos un tipo de soporte para promover al usuario.',
            'department_ids.min' => 'Selecciona al menos un tipo de soporte para promover al usuario.',
        ]);

        $user->update([
            'role' => 'support',
            'department_id' => $data['department_ids'][0] ?? null,
        ]);
        $user->supportDepartments()->sync($data['department_ids']);

        return back()->with('success', 'Funcionario promovido a soporte.');
    }

    public function demoteFromSupport(User $user): RedirectResponse
    {
        $this->authorizeAdmin();
        abort_if($user->isAdmin(), 422, 'No se puede degradar un administrador.');

        $user->update([
            'role' => 'user',
            'department_id' => null,
        ]);
        $user->supportDepartments()->detach();

        return back()->with('success', 'Soporte degradado a funcionario.');
    }

    public function cannedResponses(): View
    {
        $this->authorizeAgent();

        $responses = CannedResponse::with('department')->orderBy('title')->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.canned-responses', compact('responses', 'departments'));
    }

    public function storeCannedResponse(Request $request): RedirectResponse
    {
        $this->authorizeAgent();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'shortcut' => ['required', 'string', 'max:80', 'unique:canned_responses,shortcut'],
            'content' => ['required', 'string'],
            'department_id' => ['nullable', 'exists:departments,id'],
        ]);

        CannedResponse::create([
            ...$data,
            'shortcut' => Str::slug($data['shortcut']),
            'is_active' => true,
        ]);

        return back()->with('success', 'Respuesta predefinida creada.');
    }

    public function deleteCannedResponse(CannedResponse $cannedResponse): RedirectResponse
    {
        $this->authorizeAgent();

        $cannedResponse->delete();

        return back()->with('success', 'Respuesta eliminada.');
    }

    public function knowledgeArticles(): View
    {
        $this->authorizeAgent();

        $articles = KnowledgeArticle::with(['department', 'author'])->latest()->paginate(15);
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('admin.knowledge', compact('articles', 'departments'));
    }

    public function storeKnowledgeArticle(Request $request): RedirectResponse
    {
        $this->authorizeAgent();

        $data = $this->validateKnowledgeArticle($request);
        $data['slug'] = $this->uniqueKnowledgeSlug($data['title']);
        $data['created_by'] = Auth::id();
        $data['is_public'] = $request->boolean('is_public');

        $article = KnowledgeArticle::create($data);
        ActionLog::record('conocimiento.creado', $article, 'Articulo de conocimiento creado.');

        return back()->with('success', 'Articulo creado.');
    }

    public function updateKnowledgeArticle(Request $request, KnowledgeArticle $article): RedirectResponse
    {
        $this->authorizeAgent();

        $data = $this->validateKnowledgeArticle($request);
        $data['slug'] = $article->title !== $data['title'] ? $this->uniqueKnowledgeSlug($data['title'], $article) : $article->slug;
        $data['is_public'] = $request->boolean('is_public');

        $article->update($data);
        ActionLog::record('conocimiento.actualizado', $article, 'Articulo de conocimiento actualizado.');

        return back()->with('success', 'Articulo actualizado.');
    }

    public function deleteKnowledgeArticle(KnowledgeArticle $article): RedirectResponse
    {
        $this->authorizeAgent();

        ActionLog::record('conocimiento.eliminado', $article, 'Articulo de conocimiento eliminado.');
        $article->delete();

        return back()->with('success', 'Articulo eliminado.');
    }
}
