<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CommentController;
use Illuminate\Support\Facades\Route;

Route::controller(CommentController::class)->group(function () {
    Route::post('projects/{project}/comments', 'storeForProject')->name('projects.comments.store');
    Route::post('tasks/{task}/comments', 'storeForTask')->name('tasks.comments.store');
});
