<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ChartController;
use App\Http\Controllers\Admin\ProjectActivityScheduleController;

// Projects
Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
    Route::get('analytics', 'analytics')->name('analytics');
    Route::get('users/{directorate_id}', 'getUsers')->name('users');
    Route::get('departments/{directorate_id}', 'getDepartments')->name('departments');
    Route::get('budget/create', 'createBudget')->name('budget.create');
    Route::get('{project}/progress/chart', 'progressChart')->name('progress.chart');

    Route::get('{project}/chart', [ChartController::class, 'activityTree'])->name('chart');

    /*
            |--------------------------------------------------------------------------
            | PROJECT ACTIVITY SCHEDULES - Complete Routes Under Project
            |--------------------------------------------------------------------------
            */
    Route::controller(ProjectActivityScheduleController::class)->prefix('{project}/schedules')->name('schedules.')->group(function () {
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
        Route::post('bulk-update', 'bulkUpdate')->name('bulk-update');
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
    });
});
Route::resource('project', ProjectController::class);
