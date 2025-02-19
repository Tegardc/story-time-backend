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

// require __DIR__ . '/api/auth.php';
// require __DIR__ . '/api/user.php';
// require __DIR__ . '/api/story.php';
// require __DIR__ . '/api/category.php';
// require __DIR__ . '/api/bookmark.php';
// require __DIR__ . '/api/upload.php';


Route::middleware(['auth:sanctum', 'auth.check'])->group(function () {
    //API USERS//
    Route::apiResource('users', UserController::class);
    Route::get('/users', [UserController::class, 'show'])->name('user.show');
    Route::put('/users', [UserController::class, 'update']);

    //API STORY??
    Route::apiResource('story', StoryController::class);
    // Route::post('/story/{id}', [StoryController::class, 'update'])->name('update');
    Route::put('/story/{id}', [StoryController::class, 'update'])->name('update');
    // Route::put('/stories/{id}', [StoryController::class, 'updateStory']);

    Route::get('/story-user', [StoryController::class, 'getStoryUser']);



    //API BOOKMARK??
    Route::apiResource('bookmark', BookmarkController::class);
    Route::get('/bookmark-user', [BookmarkController::class, 'show']);
    Route::post('/bookmark', [BookmarkController::class, 'bookmark']);
    Route::delete('/stories/{id}', [StoryController::class, 'deleteStory']);
    Route::post('/stories/{id}/restore', [StoryController::class, 'restore']);
    Route::get('/stories/trashed', [StoryController::class, 'trashedStories']);


    // Route::get('/story-user', [StoryController::class, 'getStoryUser']);
    // Route::delete('/story/{id}', [StoryController::class, 'destroy']);

});

Route::apiResource('category', CategoryController::class);
// Route::post('/login', [UserController::class, 'login'])->name('login');
// Route::post('/register', [UserController::class, 'regis'])->name('regis');
// Route::post('/logout', [UserController::class, 'logout'])->name('user.logout')->middleware(middleware: 'auth:sanctum');
Route::post('/upload', [UploadFileController::class, 'upload']);
Route::post('/upload-file', [UploadFileController::class, 'uploadFile']);
Route::get('/stories/popular', [StoryController::class, 'popularStory']);
Route::get('/stories/newest', [StoryController::class, 'newest']);
Route::get('/story', [StoryController::class, 'index'])->name('index');
Route::get('stories/similar/{id}', [StoryController::class, 'similarStory']);
Route::get('/stories/search', [StoryController::class, 'search']);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
// Route::post('/change-password', [UserController::class, 'changePassword']);

// Route::post('/upload-file', [UploadFileController::class, 'uploadFile']);
Route::get('/category', [CategoryController::class, 'index'])->name('index');
Route::get('/category/{id}', [CategoryController::class, 'show'])->name('show');
Route::get('/category-story', [CategoryController::class, 'showStoryByCategory']);
Route::get('/categories/{id}', [CategoryController::class, 'getStoryByCategory'])->name('getStoryByCategory');
Route::post('/category   ', [CategoryController::class, 'store'])->name('store');
Route::get('/story/detail/{id}', [StoryController::class, 'show']);
Route::get('/health', function () {
    return response()->json([
        'message' => 'Server jalan bro',
        'success' => true
    ], 200);
});
