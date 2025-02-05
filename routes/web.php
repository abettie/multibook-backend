<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ImageController;

Route::get('/', function () {
    return view('welcome');
});

Route::apiResource('books', BookController::class);
Route::apiResource('items', ItemController::class);

Route::get('/images', [ImageController::class, 'index']);
Route::post('/images', [ImageController::class, 'store']);
Route::get('/images/{image}', [ImageController::class, 'show']);
// phpがPUTでmultipart/form-dataを受け取れないため、POSTで代用
Route::post('/updateImages/{image}', [ImageController::class, 'update']);
Route::delete('/images/{image}', [ImageController::class, 'destroy']);