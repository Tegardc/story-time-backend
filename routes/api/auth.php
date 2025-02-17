<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/register', [UserController::class, 'regis'])->name('register');
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
