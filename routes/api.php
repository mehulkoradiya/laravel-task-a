<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\UploadController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/products/import', [ImportController::class, 'uploadCsv']);
Route::get('/products/import/{id}/status', [ImportController::class, 'status']);
