<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\CommentController;

Route::controller(CommentController::class)->group(function () {
    Route::post('projects/{project}/comments', 'storeForProject')->name('projects.comments.store');
    Route::post('tasks/{task}/comments', 'storeForTask')->name('tasks.comments.store');
});
