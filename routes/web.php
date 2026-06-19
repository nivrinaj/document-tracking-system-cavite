<?php

use App\Http\Controllers\DashboardController;
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
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* -------------------- QR scan landing -------------------- */
    // This is the URL encoded in the QR code. Auth middleware forces a login
    // first; the policy then decides whether this user may see the document.
    Route::get('/track/{document:tracking_code}', [TrackController::class, 'show'])->name('track.show');

    /* -------------------- Document tracking -------------------- */
    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
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
    Route::post('/documents/{document}/archive', [DocumentController::class, 'archive'])->name('documents.archive');

    // QR image + printable slip
    Route::get('/documents/{document}/qrcode', [DocumentController::class, 'qrcode'])->name('documents.qrcode');
    Route::get('/documents/{document}/print', [DocumentController::class, 'print'])->name('documents.print');

    /* -------------------- Admin modules -------------------- */
    Route::resource('users', UserController::class)->middleware('permission:users.manage');
    Route::resource('divisions', DivisionController::class)->middleware('permission:divisions.manage');
    Route::resource('roles', RoleController::class)->middleware('permission:roles.manage');

    /* -------------------- Reports -------------------- */
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('permission:reports.view');
    Route::get('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate')->middleware('permission:reports.view');

    /* -------------------- Notifications -------------------- */
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unreadCount');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'readAll'])->name('notifications.readAll');

    /* -------------------- Logs / history -------------------- */
    // Accessible to everyone; the controller scopes what each user can see.
    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');

    /* -------------------- System configuration -------------------- */
    Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit')->middleware('permission:settings.manage');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update')->middleware('permission:settings.manage');

    /* -------------------- Documentation & Changelog (Super Admin only) -------------------- */
    Route::middleware('role:Super Admin')->group(function () {
        Route::get('/changelog', [\App\Http\Controllers\ChangelogController::class, 'index'])->name('changelog.index');
        Route::get('/documentation', [DocumentationController::class, 'index'])->name('documentation.index');
        Route::get('/documentation/create', [DocumentationController::class, 'create'])->name('documentation.create');
        Route::post('/documentation', [DocumentationController::class, 'store'])->name('documentation.store');
        Route::get('/documentation/{page:slug}', [DocumentationController::class, 'show'])->name('documentation.show');
        Route::get('/documentation/{page:slug}/edit', [DocumentationController::class, 'edit'])->name('documentation.edit');
        Route::put('/documentation/{page}', [DocumentationController::class, 'update'])->name('documentation.update');
        Route::delete('/documentation/{page}', [DocumentationController::class, 'destroy'])->name('documentation.destroy');
    });

    /* -------------------- Profile (Breeze) -------------------- */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
