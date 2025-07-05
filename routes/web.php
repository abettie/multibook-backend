<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/', function () {
    return view('welcome');
});

Route::apiResource('books', BookController::class);
Route::post('/books/{book}/thumbnail', [BookController::class, 'updateThumbnail'])
    ->name('books.updateThumbnail');
Route::apiResource('items', ItemController::class);

Route::get('/images', [ImageController::class, 'index']);
Route::post('/images', [ImageController::class, 'store']);
Route::get('/images/{image}', [ImageController::class, 'show']);
// phpがPUTでmultipart/form-dataを受け取れないため、POSTで代用
Route::post('/updateImages/{image}', [ImageController::class, 'update']);
Route::delete('/images/{image}', [ImageController::class, 'destroy']);

Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('/auth/logincheck', [LoginController::class, 'check']);