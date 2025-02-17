<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookmarkController;

Route::middleware(['auth:sanctum', 'auth.check'])->group(function () {
    Route::apiResource('bookmark', BookmarkController::class);
    Route::get('/bookmark-user', [BookmarkController::class, 'show']);
    Route::post('/bookmark', [BookmarkController::class, 'bookmark']);
});
