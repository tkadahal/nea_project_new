<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AnalyticalDashboardController;
use Illuminate\Support\Facades\Route;

Route::controller(AnalyticalDashboardController::class)->group(function () {
    Route::get('summary', 'summary')->name('summary');
    Route::get('analytics/task', 'taskAnalytics')->name('analytics.task');
    Route::get('analytics/project', 'projectAnalytics')->name('analytics.project');
    Route::get('tasks/analytics/export', 'exportTaskAnalytics')->name('tasks.analytics.export');
    Route::get('projects/analytics/export', 'exportProjectAnalytics')->name('projects.analytics.export');
});
