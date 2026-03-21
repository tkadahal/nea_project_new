<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ReportController;

Route::controller(ReportController::class)->prefix('reports')->name('reports.')->group(function () {
    Route::get('consolidated-annual', 'showConsolidatedAnnualReport')->name('consolidatedAnnual.view');
    Route::get('consolidated-annual/download', 'consolidatedAnnualReport')->name('consolidatedAnnual');
    Route::get('budgetReport', 'showBudgetReportView')->name('budgetReport.view');
    Route::get('budgetReport/download', 'budgetReport')->name('budgetReport');
    Route::get('preBudgetReport', 'showPreBudgetReportView')->name('preBudgetReport.view');
    Route::get('preBudgetReport/download', 'preBudgetReport')->name('preBudgetReport');
    Route::get('project-count', 'getProjectCount')->name('projectCount');
});
