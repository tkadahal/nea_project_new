<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\FileController;

Route::controller(FileController::class)->group(function () {
    Route::get('files', 'index')->name('file.index');
    Route::post('{model}/{id}/files', 'store')->name('files.store');
    Route::get('files/{file}/download', 'download')->name('files.download');
    Route::delete('files/{file}', 'destroy')->name('files.destroy');
});
