<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TimelineController;

Route::middleware(['auth:api', 'role:admin'])->group(function () {

});

Route::middleware(['auth:api', 'role:user'])->group(function () {

    // Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/{id}', [PostController::class, 'edit']);
    Route::post('/posts/{id}', [PostController::class, 'update']);
    Route::delete('/posts/{id}', [PostController::class, 'destroy']);

    Route::post('remove-post/{postId}/group/{groupId}', [PostController::class, 'removeFromGroup']);

    Route::get('/timeline', [TimelineController::class, 'timeline']);

});
