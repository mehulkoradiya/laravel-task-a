<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\UploadController;
use RahulHaque\Filepond\Http\Controllers\FilepondController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [FrontendController::class, 'dashboard'])->name('dashboard');
Route::get('/imports', [FrontendController::class, 'imports'])->name('imports');
Route::get('/uploads', [FrontendController::class, 'uploads'])->name('uploads');
Route::post('/uploads', [UploadController::class, 'store'])->name('uploads.store');

// Filepond routes for file upload
Route::post(config('filepond.server.url', '/filepond'), [config('filepond.controller', FilepondController::class), 'process'])->name('filepond-process');
Route::patch(config('filepond.server.url', '/filepond'), [config('filepond.controller', FilepondController::class), 'patch'])->name('filepond-patch');
Route::get(config('filepond.server.url', '/filepond'), [config('filepond.controller', FilepondController::class), 'head'])->name('filepond-head');
Route::delete(config('filepond.server.url', '/filepond'), [config('filepond.controller', FilepondController::class), 'revert'])->name('filepond-revert');