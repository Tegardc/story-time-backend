<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UploadFileController;

Route::post('/upload', [UploadFileController::class, 'upload']);
Route::post('/upload-file', [UploadFileController::class, 'uploadFile']);
