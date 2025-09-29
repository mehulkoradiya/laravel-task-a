<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FrontendController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [FrontendController::class, 'dashboard'])->name('dashboard');
Route::get('/imports', [FrontendController::class, 'imports'])->name('imports');
Route::get('/uploads', [FrontendController::class, 'uploads'])->name('uploads');
