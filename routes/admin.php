<?php

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    //change password route
    Route::post('/update-password', [UserController::class, 'updatePass']);
    // Route::put('/profile-update', [UserController::class, 'profileUpdate']);
});
