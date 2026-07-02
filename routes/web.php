<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DivisionController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkCalendarController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'active', 'password.changed'])->group(function () {

    Route::get('/password/change', [ProfileController::class, 'mustChange'])->name('password.mustChange');
    Route::put('/password/change', [ProfileController::class, 'mustChangeUpdate'])->name('password.mustChange.update');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* -------------------- QR scan landing -------------------- */
    // This is the URL encoded in the QR code. Auth middleware forces a login
    // first; the policy then decides whether this user may see the document.
    Route::get('/track/{document:tracking_code}', [TrackController::class, 'show'])->name('track.show');

    /* -------------------- Document tracking -------------------- */
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/batch-receive', [DocumentController::class, 'batchReceive'])->name('documents.batchReceive')->middleware('permission:documents.receive');
    Route::post('/documents/batch-receive', [DocumentController::class, 'batchReceiveStore'])->name('documents.batchReceive.store')->middleware('permission:documents.receive');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create')->middleware('permission:documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store')->middleware('permission:documents.create');
    Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
    Route::get('/documents/{document}/edit', [DocumentController::class, 'edit'])->name('documents.edit');
    Route::put('/documents/{document}', [DocumentController::class, 'update'])->name('documents.update');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Workflow actions
    Route::post('/documents/{document}/assign', [DocumentController::class, 'assign'])->name('documents.assign');
    Route::post('/documents/{document}/release', [DocumentController::class, 'release'])->name('documents.release');
    Route::post('/documents/{document}/receive', [DocumentController::class, 'receive'])->name('documents.receive');
    Route::post('/documents/{document}/forward', [DocumentController::class, 'forward'])->name('documents.forward');
    Route::post('/documents/{document}/forward-to-head', [DocumentController::class, 'forwardToHead'])->name('documents.forwardToHead');
    Route::post('/documents/{document}/claim-from-head', [DocumentController::class, 'claimFromHead'])->name('documents.claimFromHead');
    Route::post('/documents/{document}/archive', [DocumentController::class, 'archive'])->name('documents.archive');
    Route::post('/documents/{document}/transfer', [DocumentController::class, 'transfer'])->name('documents.transfer');
    Route::post('/documents/{document}/pending', [DocumentController::class, 'pending'])->name('documents.pending');
    Route::post('/documents/{document}/resume', [DocumentController::class, 'resume'])->name('documents.resume');
    Route::post('/documents/{document}/reject', [DocumentController::class, 'reject'])->name('documents.reject');

    // Attachments
    Route::post('/documents/{document}/attachments', [\App\Http\Controllers\AttachmentController::class, 'store'])->name('attachments.store');
    Route::post('/documents/{document}/digital-copy', [\App\Http\Controllers\AttachmentController::class, 'storeDigitalCopy'])->name('attachments.digitalCopy');
    Route::get('/attachments/{attachment}', [\App\Http\Controllers\AttachmentController::class, 'download'])->name('attachments.download');
    Route::delete('/attachments/{attachment}', [\App\Http\Controllers\AttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::post('/documents/{document}/distribute', [DocumentController::class, 'distribute'])->name('documents.distribute');
    Route::post('/documents/{document}/link', [DocumentController::class, 'linkDocument'])->name('documents.link');
    Route::delete('/documents/{document}/link/{related}', [DocumentController::class, 'unlinkDocument'])->name('documents.unlink');
    Route::post('/documents/{document}/items/{item}/decision', [DocumentController::class, 'itemDecision'])->name('documents.items.decision');
    Route::post('/documents/{document}/acknowledge', [DocumentController::class, 'acknowledge'])->name('documents.acknowledge');
    Route::post('/documents/{document}/reopen', [DocumentController::class, 'reopen'])->name('documents.reopen');

    // QR image + printable slip
    Route::get('/documents/{document}/qrcode', [DocumentController::class, 'qrcode'])->name('documents.qrcode');
    Route::get('/documents/{document}/print', [DocumentController::class, 'print'])->name('documents.print');

    /* -------------------- Admin modules -------------------- */
    Route::resource('users', UserController::class)->middleware('permission:users.manage');
    Route::resource('departments', DepartmentController::class)->middleware('permission:departments.manage');
    Route::resource('divisions', DivisionController::class)->middleware('permission:divisions.manage');
    Route::resource('roles', RoleController::class)->middleware('permission:roles.manage');

    /* -------------------- Reports -------------------- */
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('permission:reports.view');
    Route::get('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate')->middleware('permission:reports.view');
    Route::get('/reports/e-record', [ReportController::class, 'erecord'])->name('reports.erecord')->middleware('permission:reports.view');
    Route::get('/reports/transmittal', [ReportController::class, 'transmittal'])->name('reports.transmittal')->middleware('permission:reports.view');
    Route::get('/reports/document-aging', [ReportController::class, 'docTrack'])->name('reports.doctrack')->middleware('permission:reports.view');

    /* -------------------- Messaging (chat) -------------------- */
    Route::get('/messages', [\App\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/unread-count', [\App\Http\Controllers\MessageController::class, 'unreadCount'])->name('messages.unreadCount');
    Route::get('/messages/conversations', [\App\Http\Controllers\MessageController::class, 'conversations'])->name('messages.conversations');
    Route::get('/messages/people', [\App\Http\Controllers\MessageController::class, 'people'])->name('messages.people');
    Route::post('/messages/start', [\App\Http\Controllers\MessageController::class, 'start'])->name('messages.start');
    Route::post('/messages/group', [\App\Http\Controllers\MessageController::class, 'group'])->name('messages.group');
    Route::get('/messages/{conversation}', [\App\Http\Controllers\MessageController::class, 'show'])->name('messages.show');
    Route::get('/messages/{conversation}/poll', [\App\Http\Controllers\MessageController::class, 'poll'])->name('messages.poll');
    Route::post('/messages/{conversation}', [\App\Http\Controllers\MessageController::class, 'store'])->name('messages.store');

    /* -------------------- Notifications -------------------- */
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::get('/notifications/feed', [\App\Http\Controllers\NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.readAll');

    /* -------------------- Logs / history -------------------- */
    // Accessible to everyone; the controller scopes what each user can see.
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    /* -------------------- Accounting setup (funds, RC, nature) -------------------- */
    Route::middleware('system-role:super_admin')->prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/setup', [\App\Http\Controllers\AccountingController::class, 'index'])->name('index');
        Route::post('/funds', [\App\Http\Controllers\AccountingController::class, 'storeFund'])->name('funds.store');
        Route::put('/funds/{fund}', [\App\Http\Controllers\AccountingController::class, 'updateFund'])->name('funds.update');
        Route::delete('/funds/{fund}', [\App\Http\Controllers\AccountingController::class, 'destroyFund'])->name('funds.destroy');
        Route::post('/centers', [\App\Http\Controllers\AccountingController::class, 'storeCenter'])->name('centers.store');
        Route::put('/centers/{center}', [\App\Http\Controllers\AccountingController::class, 'updateCenter'])->name('centers.update');
        Route::delete('/centers/{center}', [\App\Http\Controllers\AccountingController::class, 'destroyCenter'])->name('centers.destroy');
        Route::post('/centers/{center}/projects', [\App\Http\Controllers\AccountingController::class, 'storeProject'])->name('centers.projects.store');
        Route::put('/projects/{project}', [\App\Http\Controllers\AccountingController::class, 'updateProject'])->name('centers.projects.update');
        Route::delete('/projects/{project}', [\App\Http\Controllers\AccountingController::class, 'destroyProject'])->name('centers.projects.destroy');
        Route::put('/hospital-rc-required', [\App\Http\Controllers\AccountingController::class, 'updateHospitalRcRequired'])->name('hospital-rc.required');
        Route::put('/overdue', [\App\Http\Controllers\AccountingController::class, 'updateOverdue'])->name('overdue.update');
        Route::post('/natures', [\App\Http\Controllers\AccountingController::class, 'storeNature'])->name('natures.store');
        Route::put('/natures/{nature}', [\App\Http\Controllers\AccountingController::class, 'updateNature'])->name('natures.update');
        Route::delete('/natures/{nature}', [\App\Http\Controllers\AccountingController::class, 'destroyNature'])->name('natures.destroy');
    });

    /* -------------------- Work calendar (working hours, holidays, leave) -------------------- */
    Route::middleware('system-role:super_admin')->prefix('work-calendar')->name('work-calendar.')->group(function () {
        Route::get('/settings', [WorkCalendarController::class, 'settings'])->name('settings');
        Route::put('/settings', [WorkCalendarController::class, 'saveSettings'])->name('settings.save');
        Route::get('/holidays', [WorkCalendarController::class, 'holidays'])->name('holidays');
        Route::post('/holidays', [WorkCalendarController::class, 'storeHoliday'])->name('holidays.store');
        Route::delete('/holidays/{calendarDay}', [WorkCalendarController::class, 'destroyHoliday'])->name('holidays.destroy');
    });
    Route::middleware('permission:calendar.manage')->prefix('work-calendar')->name('work-calendar.')->group(function () {
        Route::get('/team', [WorkCalendarController::class, 'team'])->name('team');
        Route::post('/team', [WorkCalendarController::class, 'storeTeam'])->name('team.store');
        Route::delete('/team/{calendarDay}', [WorkCalendarController::class, 'destroyTeam'])->name('team.destroy');
    });

    /* -------------------- System configuration -------------------- */
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit')->middleware('permission:settings.manage');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update')->middleware('permission:settings.manage');
    Route::post('/settings/reset-data', [SettingController::class, 'resetData'])->name('settings.resetData')->middleware('system-role:super_admin');

    /* -------------------- Documentation & Changelog (Super Admin only) -------------------- */
    Route::middleware('system-role:super_admin')->group(function () {
        Route::get('/reports/settings', [ReportController::class, 'settings'])->name('reports.settings');
        Route::put('/reports/settings', [ReportController::class, 'saveSettings'])->name('reports.settings.save');
        Route::get('/qr-slip/settings', [\App\Http\Controllers\QrSlipController::class, 'edit'])->name('qr-slip.settings');
        Route::put('/qr-slip/settings', [\App\Http\Controllers\QrSlipController::class, 'update'])->name('qr-slip.settings.save');
        Route::resource('document-types', \App\Http\Controllers\DocumentTypeController::class)->except('show');
        Route::get('/changelog', [\App\Http\Controllers\ChangelogController::class, 'index'])->name('changelog.index');
        Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');
        Route::get('/documentation/create', [DocumentationController::class, 'create'])->name('documentation.create');
        Route::post('/documentation', [DocumentationController::class, 'store'])->name('documentation.store');
        Route::get('/documentation/{page:slug}', [DocumentationController::class, 'show'])->name('documentation.show');
        Route::get('/documentation/{page:slug}/edit', [DocumentationController::class, 'edit'])->name('documentation.edit');
        Route::put('/documentation/{page}', [DocumentationController::class, 'update'])->name('documentation.update');
        Route::delete('/documentation/{page}', [DocumentationController::class, 'destroy'])->name('documentation.destroy');

        Route::get('/backups', [\App\Http\Controllers\BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [\App\Http\Controllers\BackupController::class, 'store'])->name('backups.store');
        Route::get('/backups/{filename}/download', [\App\Http\Controllers\BackupController::class, 'download'])->name('backups.download')->where('filename', '[A-Za-z0-9_\-\.]+');
        Route::delete('/backups/{filename}', [\App\Http\Controllers\BackupController::class, 'destroy'])->name('backups.destroy')->where('filename', '[A-Za-z0-9_\-\.]+');
    });

    /* -------------------- Profile (Breeze) -------------------- */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
