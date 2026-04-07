<?php

use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth:api', 'role:admin'])->group(function () {});


Route::middleware(['auth:api', 'role:user'])->group(function () {

    //group routes
    Route::get('/groups', [GroupController::class, 'index']);

    Route::post('/group-create', [GroupController::class, 'store']);
    Route::get('/group-show/{id}', [GroupController::class, 'show']);
    Route::post('/group-update/{id}', [GroupController::class, 'update']);

    //group member join and leave routes
    Route::post('/group/join', [GroupController::class, 'joinGroup']);
    Route::post('/group/leave', [GroupController::class, 'leaveGroup']);

    //group invite routes
    Route::get('/group-invite-link/{id}', [GroupController::class, 'generateInviteLink']);
    Route::get('/group/join/invite/{group_id}', [GroupController::class, 'joinGroup'])
        ->name('group.invite.join')
        ->middleware('signed');
});
