<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ContractActivityScheduleController;
use Illuminate\Support\Facades\Route;

Route::prefix('schedules')
    ->name('schedules.')
    ->controller(ContractActivityScheduleController::class)
    ->group(function () {

        Route::get('overview', 'overview')->name('overview');
        Route::get('analytics', 'analyticsDashboard')->name('analytics');
        Route::get('all-files', 'allFiles')->name('all-files');
        Route::get('analytics-charts', 'analyticsCharts')->name('analytics-charts');

        // API subgroup
        Route::prefix('api')->name('api.')->group(function () {
            Route::get('contracts-comparison', 'apicontractsComparison')->name('contracts-comparison');
            Route::get('directorates-comparison', 'apiDirectoratesComparison')->name('directorates-comparison');
            Route::get('top-contracts', 'apiTopcontracts')->name('top-contracts');
            Route::get('contracts-by-directorate', 'apicontractsByDirectorate')->name('contracts-by-directorate');
            Route::get('contract-attention-counts', 'apicontractAttentionCounts')->name('contract-attention-counts');
            Route::get('progress-buckets', 'apiProgressBuckets')->name('progress-buckets');
            Route::get('activity-extremes', 'apiActivityExtremes')->name('activity-extremes');
            Route::get('slippages', 'apiSlippages')->name('slippages');
        });
    });
