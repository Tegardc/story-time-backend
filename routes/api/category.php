<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;

Route::apiResource('category', CategoryController::class);
Route::get('/category-story', [CategoryController::class, 'showStoryByCategory']);
// Route::get('/categories/{id}', [CategoryController::class, 'getStoryByCategory'])->name('getStoryByCategory');
Route::get('/category/{id}', [CategoryController::class, 'show'])->name('show');
