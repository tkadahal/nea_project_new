<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ProjectExpenseController;
use App\Http\Controllers\Admin\ProjectExpenseFundingAllocationController;
use Illuminate\Support\Facades\Route;

Route::controller(ProjectExpenseController::class)->prefix('projectExpense')->name('projectExpense.')->group(function () {
    Route::get('downloadExcel/{projectId}/{fiscalYearId}', 'downloadTemplate')->name('template.download');
    Route::get('{project}/{fiscalYear}/upload', 'uploadView')->name('upload.view');
    Route::post('{project}/{fiscalYear}/upload', 'upload')->name('upload');
    Route::get('download/excel/{projectId}/{fiscalYearId}', 'downloadExcel')->name('excel.download');
    Route::get('show/{projectId}/{fiscalYearId}', 'show')->name('show');
    Route::get('next-quarter/{project}/{fiscalYear}', 'nextQuarterAjax')->name('nextQuarter');
    Route::get('{projectId}/{fiscalYearId}', 'getForProject')->name('getForProject');
});
Route::resource('projectExpense', ProjectExpenseController::class)->except(['show', 'edit', 'update']);

// Project Expense Funding Allocations
Route::post('project-expense-funding-allocations/load-data', [ProjectExpenseFundingAllocationController::class, 'loadData'])->name('projectExpenseFundingAllocations.loadData');
Route::resource('project-expense-funding-allocation', ProjectExpenseFundingAllocationController::class)
    ->names('projectExpenseFundingAllocation')
    ->only(['index', 'create', 'store']);
