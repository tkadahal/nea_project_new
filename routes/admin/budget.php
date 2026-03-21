<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\BudgetController;
use App\Http\Controllers\Admin\BudgetQuaterAllocationController;

Route::controller(BudgetController::class)->prefix('budget')->name('budget.')->group(function () {
    Route::get('download-template', 'downloadTemplate')->name('download-template');
    Route::get('upload', 'uploadIndex')->name('upload.index');
    Route::post('upload', 'upload')->name('upload');
    Route::get('{budget}/remaining', 'remaining')->name('remaining');
    Route::get('filter-projects', 'filterProjects')->name('filter-projects');
});
Route::prefix('budgets')->name('budget.')->group(function () {
    Route::get('duplicates', [BudgetController::class, 'listDuplicates'])->name('duplicates');
    Route::post('clean-duplicates', [BudgetController::class, 'cleanDuplicates'])->name('cleanDuplicates');
});
Route::resource('budget', BudgetController::class);

// Budget Quarter Allocations
Route::controller(BudgetQuaterAllocationController::class)->prefix('budget-quater-allocation')->name('budgetQuaterAllocation.')
    ->group(function () {
        Route::get('download-template', 'downloadTemplate')->name('download-template');
        Route::get('upload-template', 'uploadIndex')->name('uploadIndex');
        Route::post('upload-template', 'uploadTemplate')->name('uploadTemplate');
    });

// Separate route for AJAX load budgets
Route::post('budget-quater-allocations/load-budgets', [BudgetQuaterAllocationController::class, 'loadBudgets'])->name('budgetQuaterAllocations.loadBudgets');

// Resource routes
Route::resource('budgetQuaterAllocation', BudgetQuaterAllocationController::class);
