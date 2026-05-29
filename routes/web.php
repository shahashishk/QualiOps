<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GitConnectionController;
use App\Http\Controllers\NotificationHistoryController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectSonarMetricsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchedulerController;
use App\Http\Controllers\ScanHistoryController;
use App\Http\Controllers\ScanQueueController;
use App\Http\Controllers\SonarConnectionController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\SystemMaintenanceController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/theme', [ThemeController::class, 'update'])->name('theme.update');
    Route::resource('users', UserController::class);
    Route::patch('/users/{user}/status', [UserController::class, 'toggleStatus'])->name('users.status');
    Route::resource('roles', RoleController::class)->except(['show']);
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/export', [ReportController::class, 'store'])->name('reports.export');
    Route::get('/reports/{export_history}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::resource('git-connections', GitConnectionController::class)->except(['show']);
    Route::patch('/git-connections/{git_connection}/status', [GitConnectionController::class, 'toggleStatus'])->name('git-connections.status');
    Route::post('/git-connections/{git_connection}/test', [GitConnectionController::class, 'test'])->name('git-connections.test');
    Route::resource('projects', ProjectController::class)->except(['destroy']);
    Route::patch('/projects/{project}/status', [ProjectController::class, 'toggleStatus'])->name('projects.status');
    Route::post('/projects/fetch-branches', [ProjectController::class, 'fetchBranches'])->name('projects.fetch-branches');
    Route::post('/projects/test-git-url', [ProjectController::class, 'testGitUrl'])->name('projects.test-git-url');
    Route::post('/projects/{project}/sync-sonar-metrics', [ProjectSonarMetricsController::class, 'store'])->name('projects.sync-sonar-metrics');
    Route::get('/scheduler', [SchedulerController::class, 'index'])->name('scheduler.index');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{audit_log}', [AuditLogController::class, 'show'])->name('audit-logs.show');
    Route::get('/scan-queue', [ScanQueueController::class, 'index'])->name('scan-queue.index');
    Route::get('/scan-queue/{scan_queue}', [ScanQueueController::class, 'show'])->name('scan-queue.show');
    Route::post('/scan-queue/{scan_queue}/cancel', [ScanQueueController::class, 'cancel'])->name('scan-queue.cancel');
    Route::post('/scan-queue/{scan_queue}/retry', [ScanQueueController::class, 'retry'])->name('scan-queue.retry');
    Route::post('/scan-queue/{scan_queue}/requeue', [ScanQueueController::class, 'requeue'])->name('scan-queue.requeue');
    Route::post('/scan-queue/{scan_queue}/recover', [ScanQueueController::class, 'recover'])->name('scan-queue.recover');
    Route::post('/projects/{project}/scan-now', [ScanQueueController::class, 'store'])->name('projects.scan-now');
    Route::get('/scan-history', [ScanHistoryController::class, 'index'])->name('scan-history.index');
    Route::get('/scan-history/{scan_history}', [ScanHistoryController::class, 'show'])->name('scan-history.show');
    Route::get('/scan-history/{scan_history}/logs', [ScanHistoryController::class, 'logs'])->name('scan-history.logs');
    Route::resource('sonar-connections', SonarConnectionController::class)->except(['show']);
    Route::patch('/sonar-connections/{sonar_connection}/status', [SonarConnectionController::class, 'toggleStatus'])->name('sonar-connections.status');
    Route::post('/sonar-connections/{sonar_connection}/test', [SonarConnectionController::class, 'test'])->name('sonar-connections.test');
    Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health.index');
    Route::get('/system-settings/maintenance', [SystemMaintenanceController::class, 'index'])->name('system-maintenance.index');
    Route::get('/notifications/settings', [NotificationSettingsController::class, 'edit'])->name('notifications.settings.edit');
    Route::put('/notifications/settings', [NotificationSettingsController::class, 'update'])->name('notifications.settings.update');
    Route::get('/notifications/history', [NotificationHistoryController::class, 'index'])->name('notifications.history.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
