<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PostController;

Route::middleware(['auth:api', 'role:admin'])->group(function () {

});

Route::middleware(['auth:api', 'role:user'])->group(function () {

    Route::post('/posts', [PostController::class, 'store']);

});
