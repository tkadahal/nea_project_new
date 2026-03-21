<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\ContractExtensionController;

Route::controller(ContractController::class)->prefix('contracts')->name('contracts.')->group(function () {
    Route::get('projects/{directorate_id}', 'getProjects')->name('projects');
    Route::get('get-project-budget/{projectId}', 'getProjectBudget')->name('get-project-budget');
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
