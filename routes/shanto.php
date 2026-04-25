<?php

use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\NewsfeedController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\TimelineController;

Route::middleware(['auth:api', 'role:admin'])->group(function () {

});

Route::middleware(['auth:api', 'role:user'])->group(function () {

    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/profile/posts/{id}', [PostController::class, 'editProfilePost']);
    Route::post('/profile/posts/{id}', [PostController::class, 'updateProfilePost']);

    Route::delete('/profile/posts/{id}', [PostController::class, 'destroyProfilePost']);

    Route::get('/groups/{group}/posts/{post}', [PostController::class, 'editGroupPost']);
    Route::post('/groups/{group}/posts/{post}', [PostController::class, 'updateGroupPost']);
    Route::delete('/groups/{group}/posts/{post}', [PostController::class, 'destroyGroupPost']);

    Route::get('/newsfeed', [NewsfeedController::class, 'newsFeed']);
    // Route::get('/timeline', [TimelineController::class, 'timeline']);

    //Like
    Route::post('/toggle-like/{post}', [LikeController::class, 'toggleLike']);

});
