<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ContractActivityScheduleController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\ContractExtensionController;
use Illuminate\Support\Facades\Route;

Route::controller(ContractController::class)->prefix('contracts')->name('contracts.')->group(function () {
    Route::get('projects/{directorate_id}', 'getProjects')->name('projects');
    Route::get('get-project-budget/{projectId}', 'getProjectBudget')->name('get-project-budget');

    /*
    |--------------------------------------------------------------------------
    | PROJECT ACTIVITY SCHEDULES - Complete Routes Under Project
    |--------------------------------------------------------------------------
    */
    Route::controller(ContractActivityScheduleController::class)->prefix('{contract}/schedules')->name('schedules.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('tree', 'tree')->name('tree');
        Route::get('dashboard', 'dashboard')->name('dashboard');
        Route::get('schedule-charts', 'charts')->name('charts');
        Route::get('api/burn-chart', 'burnChartData')->name('api.burn-chart');
        Route::get('api/s-curve', 'sCurveData')->name('api.s-curve');
        Route::get('api/activity-chart', 'activityChartData')->name('api.activity-chart');
        Route::get('api/gantt-data', 'ganttData')->name('api.gantt-data');
        Route::get('assign', 'assignForm')->name('assign-form');
        Route::post('assign', 'assign')->name('assign');
        Route::get('{schedule}/edit', 'edit')->name('edit');
        Route::put('{schedule}', 'update')->name('update');
        Route::get('quick-update', 'quickUpdate')->name('quick-update');
        Route::get('quick-update-date', 'quickUpdateDates')->name('quick-update-date');
        Route::post('bulk-update', 'bulkUpdate')->name('bulk-update');
        Route::post('bulk-update-date', 'bulkUpdateDates')->name('bulk-update-date');
        Route::post('{schedule}/date-revision', 'addDateRevision')->name('add-date-revision');
        Route::delete('date-revision/{revision}', 'deleteDateRevision')->name('delete-date-revision');
        Route::post('{schedule}/mark-not-needed', 'markAsNotNeeded')->name('mark-not-needed');
        Route::post('{schedule}/mark-active', 'markAsActive')->name('mark-active');
        Route::post('bulk-mark-status', 'bulkMarkStatus')->name('bulk-mark-status');
        Route::post('recalculate-timeline', 'recalculateTimeline')->name('recalculate-timeline');
        Route::get('{schedule}/dependencies', 'showDependencies')->name('dependencies');
        Route::get('critical-path', 'criticalPath')->name('critical-path');
        Route::get('files', 'filesPage')->name('files');
        Route::post('upload-file', 'uploadFile')->name('upload-file');
        Route::get('files/{file}/download', 'downloadFile')->name('download-file');
        Route::delete('files/{file}', 'deleteFile')->name('delete-file');
        Route::get('progressHistory', 'progressHistory')->name('progressHistory');
        Route::get('weeklyReport', 'weeklyReport')->name('weeklyReport');
        Route::get('velocityDashboard', 'velocityDashboard')->name('velocityDashboard');
        Route::get('gantt', 'ganttView')->name('gantt');
    });
});


Route::resource('contract', ContractController::class);

// Contract Extensions
Route::prefix('contract/{contract}/extensions')->name('contract.extensions.')->controller(ContractExtensionController::class)->group(function () {
    Route::get('create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('{extension}/edit', 'edit')->name('edit');
    Route::put('{extension}', 'update')->name('update');
    Route::delete('{extension}', 'destroy')->name('destroy');
});
