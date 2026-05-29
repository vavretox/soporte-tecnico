<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KnowledgeController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\TicketController;
use App\Models\Category;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/api/categories/{department}', function ($departmentId) {
    return Category::where('department_id', $departmentId)->get();
})->middleware(['auth', 'password.change']);

Route::middleware(['auth'])->group(function () {
    Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
    Route::post('/password/change', [AuthController::class, 'updatePassword'])->name('password.update');
});

Route::middleware(['auth', 'password.change'])->group(function () {
    Route::post('/profile/avatar', [AuthController::class, 'updateAvatar'])->name('profile.avatar');
    Route::get('/dashboard', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('/notifications/messages', [TicketController::class, 'globalMessages'])->name('notifications.messages');
    Route::get('/knowledge', [KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::get('/knowledge/{article:slug}', [KnowledgeController::class, 'show'])->name('knowledge.show');
    Route::get('/telegram', [TelegramController::class, 'index'])->name('telegram.index');
    Route::post('/telegram/regenerate', [TelegramController::class, 'regenerate'])->name('telegram.regenerate');
    Route::post('/telegram/sync', [TelegramController::class, 'sync'])->name('telegram.sync');
    Route::delete('/telegram/disconnect', [TelegramController::class, 'disconnect'])->name('telegram.disconnect');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/physical/create', [TicketController::class, 'createPhysical'])->name('tickets.physical.create');
    Route::post('/tickets/physical', [TicketController::class, 'storePhysical'])->name('tickets.physical.store');
    Route::get('/tickets/{ticket}/physical/print', [TicketController::class, 'printPhysical'])->name('tickets.physical.print');
    Route::get('/tickets/{ticket}/attachments/image', [TicketController::class, 'ticketImage'])->name('tickets.image');
    Route::get('/tickets/{ticket}/attachments/physical-pdf', [TicketController::class, 'physicalPdf'])->name('tickets.physical.pdf');
    Route::get('/tickets/{ticket}/messages/{message}/image', [TicketController::class, 'messageImage'])->name('tickets.messages.image');
    Route::get('/tickets/{ticket}/messages', [TicketController::class, 'messages'])->name('tickets.messages');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/message', [TicketController::class, 'addMessage'])->name('tickets.message');
    Route::post('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
});

Route::middleware(['auth', 'password.change'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/tickets', [AdminController::class, 'tickets'])->name('admin.tickets');
    Route::get('/tickets/{ticket}', [AdminController::class, 'showTicket'])->name('admin.ticket.show');
    Route::post('/tickets/{ticket}/assign', [AdminController::class, 'assignTicket'])->name('admin.ticket.assign');
    Route::post('/tickets/{ticket}/assign-self', [AdminController::class, 'assignTicketToMe'])->name('admin.ticket.assign-self');
    Route::post('/tickets/{ticket}/status', [AdminController::class, 'updateStatus'])->name('admin.ticket.status');
    Route::delete('/tickets/{ticket}', [AdminController::class, 'deleteTicket'])->name('admin.ticket.delete');

    Route::get('/bitacoras', [AdminController::class, 'bitacoras'])->name('admin.bitacoras');
    Route::post('/bitacoras', [AdminController::class, 'storeBitacora'])->name('admin.bitacoras.store');
    Route::put('/bitacoras/{bitacora}', [AdminController::class, 'updateBitacora'])->name('admin.bitacoras.update');
    Route::delete('/bitacoras/{bitacora}', [AdminController::class, 'deleteBitacora'])->name('admin.bitacoras.delete');

    Route::get('/assets', [AdminController::class, 'assets'])->name('admin.assets');
    Route::post('/assets', [AdminController::class, 'storeAsset'])->name('admin.assets.store');
    Route::post('/assets/import', [AdminController::class, 'importAssets'])->name('admin.assets.import');
    Route::get('/assets/export', [AdminController::class, 'exportAssets'])->name('admin.assets.export');
    Route::get('/assets/template', [AdminController::class, 'assetTemplate'])->name('admin.assets.template');
    Route::put('/assets/{asset}', [AdminController::class, 'updateAsset'])->name('admin.assets.update');
    Route::delete('/assets/{asset}', [AdminController::class, 'deleteAsset'])->name('admin.assets.delete');

    Route::get('/suppliers', [AdminController::class, 'suppliers'])->name('admin.suppliers');
    Route::post('/suppliers', [AdminController::class, 'storeSupplier'])->name('admin.suppliers.store');
    Route::post('/suppliers/import', [AdminController::class, 'importSuppliers'])->name('admin.suppliers.import');
    Route::get('/suppliers/export', [AdminController::class, 'exportSuppliers'])->name('admin.suppliers.export');
    Route::get('/suppliers/template', [AdminController::class, 'supplierTemplate'])->name('admin.suppliers.template');
    Route::put('/suppliers/{supplier}', [AdminController::class, 'updateSupplier'])->name('admin.suppliers.update');
    Route::delete('/suppliers/{supplier}', [AdminController::class, 'deleteSupplier'])->name('admin.suppliers.delete');

    Route::get('/changes', [AdminController::class, 'changes'])->name('admin.changes');
    Route::post('/changes', [AdminController::class, 'storeChange'])->name('admin.changes.store');
    Route::put('/changes/{change}', [AdminController::class, 'updateChange'])->name('admin.changes.update');
    Route::delete('/changes/{change}', [AdminController::class, 'deleteChange'])->name('admin.changes.delete');

    Route::get('/network', [AdminController::class, 'network'])->name('admin.network');
    Route::post('/network', [AdminController::class, 'storeNetwork'])->name('admin.network.store');
    Route::put('/network/{networkRecord}', [AdminController::class, 'updateNetwork'])->name('admin.network.update');
    Route::delete('/network/{networkRecord}', [AdminController::class, 'deleteNetwork'])->name('admin.network.delete');

    Route::get('/systems', [AdminController::class, 'systems'])->name('admin.systems');
    Route::post('/systems', [AdminController::class, 'storeSystem'])->name('admin.systems.store');
    Route::put('/systems/{systemRecord}', [AdminController::class, 'updateSystem'])->name('admin.systems.update');
    Route::delete('/systems/{systemRecord}', [AdminController::class, 'deleteSystem'])->name('admin.systems.delete');

    Route::get('/reports', [AdminController::class, 'reports'])->name('admin.reports');
    Route::get('/reports/tickets-attended', [AdminController::class, 'ticketAttendanceReport'])->name('admin.reports.tickets-attended');
    Route::get('/reports/tickets-attended/data', [AdminController::class, 'ticketAttendanceReportData'])->name('admin.reports.tickets-attended.data');
    Route::get('/reports/export', [AdminController::class, 'exportReports'])->name('admin.reports.export');
    Route::get('/audit-logs', [AdminController::class, 'auditLogs'])->name('admin.audit-logs');

    Route::get('/departments', [AdminController::class, 'departments'])->name('admin.departments');
    Route::post('/departments', [AdminController::class, 'storeDepartment'])->name('admin.departments.store');
    Route::put('/departments/{department}', [AdminController::class, 'updateDepartment'])->name('admin.departments.update');
    Route::delete('/departments/{department}', [AdminController::class, 'deleteDepartment'])->name('admin.departments.delete');

    Route::get('/categories', [AdminController::class, 'categories'])->name('admin.categories');
    Route::post('/categories', [AdminController::class, 'storeCategory'])->name('admin.categories.store');
    Route::delete('/categories/{category}', [AdminController::class, 'deleteCategory'])->name('admin.categories.delete');

    Route::get('/support', [AdminController::class, 'supportStaff'])->name('admin.support');
    Route::post('/support/{user}/promote', [AdminController::class, 'promoteToSupport'])->name('admin.support.promote');
    Route::post('/support/{user}/demote', [AdminController::class, 'demoteFromSupport'])->name('admin.support.demote');

    Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('admin.users.delete');

    Route::get('/offices', [AdminController::class, 'offices'])->name('admin.offices');
    Route::post('/offices', [AdminController::class, 'storeOffice'])->name('admin.offices.store');
    Route::put('/offices/{office}', [AdminController::class, 'updateOffice'])->name('admin.offices.update');
    Route::delete('/offices/{office}', [AdminController::class, 'deleteOffice'])->name('admin.offices.delete');

    Route::get('/canned-responses', [AdminController::class, 'cannedResponses'])->name('admin.canned');
    Route::post('/canned-responses', [AdminController::class, 'storeCannedResponse'])->name('admin.canned.store');
    Route::delete('/canned-responses/{cannedResponse}', [AdminController::class, 'deleteCannedResponse'])->name('admin.canned.delete');

    Route::get('/knowledge', [AdminController::class, 'knowledgeArticles'])->name('admin.knowledge');
    Route::post('/knowledge', [AdminController::class, 'storeKnowledgeArticle'])->name('admin.knowledge.store');
    Route::put('/knowledge/{article}', [AdminController::class, 'updateKnowledgeArticle'])->name('admin.knowledge.update');
    Route::delete('/knowledge/{article}', [AdminController::class, 'deleteKnowledgeArticle'])->name('admin.knowledge.delete');
});
