<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware(['auth:sanctum', 'auth.check'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::get('/users', [UserController::class, 'show'])->name('user.show');
    Route::put('/users', [UserController::class, 'update']);
});
