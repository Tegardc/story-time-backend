<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StoryController;

Route::get('/stories/popular', [StoryController::class, 'popularStory']);
Route::get('/stories/newest', [StoryController::class, 'newest']);
Route::get('/stories/similar/{id}', [StoryController::class, 'similarStory']);
Route::get('/stories/search', [StoryController::class, 'search']);
Route::get('/story', [StoryController::class, 'index']);
Route::get('/story/detail/{id}', [StoryController::class, 'show']);



Route::middleware(['auth:sanctum', 'auth.check'])->group(function () {

    Route::get('/story-user', [StoryController::class, 'getStoryUser']);
    Route::post('/story', [
        StoryController::class,
        'store'
    ]);
    Route::put('/story/{id}', [
        StoryController::class,
        'update'
    ]);
    Route::delete('/stories/{id}', [StoryController::class, 'deleteStory']);
    Route::get('/stories/trashed', [StoryController::class, 'trashedStories']);
    Route::post('/stories/{id}/restore', [StoryController::class, 'restore']);
});
