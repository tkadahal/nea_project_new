<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\ChartController;
use App\Http\Controllers\Admin\ProjectController;
use Illuminate\Support\Facades\Route;

// Projects
Route::controller(ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
    Route::get('analytics', 'analytics')->name('analytics');
    Route::get('users/{directorate_id}', 'getUsers')->name('users');
    Route::get('departments/{directorate_id}', 'getDepartments')->name('departments');
    Route::get('budget/create', 'createBudget')->name('budget.create');
    Route::get('{project}/progress/chart', 'progressChart')->name('progress.chart');

    Route::get('{project}/chart', [ChartController::class, 'activityTree'])->name('chart');
});
Route::resource('project', ProjectController::class);
