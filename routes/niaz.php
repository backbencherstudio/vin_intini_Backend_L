<?php

use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth:api', 'role:admin'])->group(function () {


});


Route::middleware(['auth:api', 'role:user'])->group(function () {

    //group routes
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/group-create', [GroupController::class, 'store']);

    Route::post('/group/join', [GroupController::class, 'joinGroup']);
    Route::post('/group/leave', [GroupController::class, 'leaveGroup']);

});
