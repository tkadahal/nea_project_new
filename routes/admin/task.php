<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\TaskController;
use Illuminate\Support\Facades\Route;

Route::controller(TaskController::class)->prefix('tasks')->name('tasks.')->group(function () {
    Route::post('filter', 'filter')->name('filter');
    Route::post('set-view', 'setViewPreference')->name('set-view');
    Route::get('gantt-chart', 'getGanttChart')->name('ganttChart');
    Route::get('users-by-projects', 'getUsersByProjects')->name('users_by_projects');
    Route::get('users-by-directorate-or-department', 'getUsersByDirectorateOrDepartment')->name('users_by_directorate_or_department');
    Route::get('projects/{directorate_id}', 'getProjects')->name('projects');
    Route::get('departments/{directorate_id}', 'getDepartments')->name('departments');
});
Route::prefix('task')->name('task.')->controller(TaskController::class)->group(function () {
    Route::post('load-more', 'loadMore')->name('loadMore');
    Route::post('updateStatus', 'updateStatus')->name('updateStatus');
    Route::get('{task}/{project?}', 'show')->name('show')->where(['task' => '[0-9]+', 'project' => '[0-9]+']);
    Route::get('{task}/edit/{project?}', 'edit')->name('edit')->where(['task' => '[0-9]+', 'project' => '[0-9]+']);
    Route::put('{task}/update/{project?}', 'update')->name('update')->where(['task' => '[0-9]+', 'project' => '[0-9]+']);
});
Route::resource('task', TaskController::class)->except(['show', 'edit', 'update']);
