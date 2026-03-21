<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ExpenseController;

Route::controller(ExpenseController::class)->group(function () {
    Route::get('fiscal-years/by-date', 'byDate')->name('fiscal-years.by-date');
    Route::get('budgets/available', 'availableBudget')->name('budgets.available');
    Route::get('expenses/0', 'testShow')->name('expense.testShow');
});
Route::resource('expense', ExpenseController::class);
