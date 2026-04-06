<?php

use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth:api', 'role:admin'])->group(function () {


});


Route::middleware(['auth:api', 'role:user'])->group(function () {
    
    //group routes

    Route::post('/group-create', [GroupController::class, 'store']);

});
