<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ProjectActivityScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('schedules')
    ->name('schedules.')
    ->controller(ProjectActivityScheduleController::class)
    ->group(function () {

        Route::get('overview', 'overview')->name('overview');
        Route::get('analytics', 'analyticsDashboard')->name('analytics');
        Route::get('all-files', 'allFiles')->name('all-files');
        Route::get('analytics-charts', 'analyticsCharts')->name('analytics-charts');

        // API subgroup
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('projects-comparison', 'apiProjectsComparison')->name('projects-comparison');
            Route::get('directorates-comparison', 'apiDirectoratesComparison')->name('directorates-comparison');
            Route::get('top-projects', 'apiTopProjects')->name('top-projects');
            Route::get('projects-by-directorate', 'apiProjectsByDirectorate')->name('projects-by-directorate');
            Route::get('project-attention-counts', 'apiProjectAttentionCounts')->name('project-attention-counts');
            Route::get('progress-buckets', 'apiProgressBuckets')->name('progress-buckets');
            Route::get('activity-extremes', 'apiActivityExtremes')->name('activity-extremes');
            Route::get('slippages', 'apiSlippages')->name('slippages');
        });
    });
