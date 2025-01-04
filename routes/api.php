<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UploadFileController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:sanctum', 'auth.check'])->group(function () {
    Route::get('/users-id', [UserController::class, 'show'])->name('user.show');
    // Route::put('/users', [UserController::class, 'update'])->name('update');
    // Route::put('/users/{id}', [UserController::class, 'updateById']);
    Route::put('/user', [UserController::class, 'updateUser']);
    Route::apiResource('users', UserController::class);
    Route::apiResource('story', StoryController::class);
    Route::apiResource('bookmark', BookmarkController::class);
    Route::apiResource('category', CategoryController::class);
    // Route::get('/story-user', [StoryController::class, 'getStoryUser']);
    // Route::delete('/story/{id}', [StoryController::class, 'destroy']);
    Route::get('/bookmark-user', [BookmarkController::class, 'show']);
    Route::post('/bookmark', [BookmarkController::class, 'bookmark']);
});

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
// Route::post('/change-password', [UserController::class, 'changePassword']);
Route::post('/login', [UserController::class, 'login'])->name('login');
Route::post('/register', [UserController::class, 'regis'])->name('regis');
Route::post('/logout', [UserController::class, 'logout'])->name('user.logout')->middleware(middleware: 'auth:sanctum');
Route::post('/upload', [UploadFileController::class, 'uploadFile']);

Route::get('/category', [CategoryController::class, 'index'])->name('index');
Route::get('/category/{id}', [CategoryController::class, 'show'])->name('show');
Route::get('/categories/{id}', [CategoryController::class, 'getStoryByCategory'])->name('getStoryByCategory');
Route::post('/category', [CategoryController::class, 'store'])->name('store');

Route::post('/story/{id}', [StoryController::class, 'update'])->name('update');
Route::put('/stories/{id}', [StoryController::class, 'updateStory']);
Route::get('/story', [StoryController::class, 'index'])->name('index');
Route::get('/story/{id}', [StoryController::class, 'show'])->name('show');

Route::get('/stories/popular', [StoryController::class, 'popularStory']);
Route::get('/stories/newest', [StoryController::class, 'newest']);
