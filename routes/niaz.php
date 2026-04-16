<?php

use App\Http\Controllers\Api\UserConnectionController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\GroupController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'role:admin'])->group(function () {});

Route::middleware(['auth:api', 'role:user'])->group(function () {

    // group routes
    Route::get('/groups', [GroupController::class, 'index']);
    Route::post('/group-create', [GroupController::class, 'store']);
    Route::get('/group-show/{id}', [GroupController::class, 'show']);
    Route::post('/group-update/{id}', [GroupController::class, 'update']);
    // my group routes
    Route::get('/my-created-groups', [GroupController::class, 'myCreatedGroups']);
    // my joined group routes
    Route::get('/my-joined-groups', [GroupController::class, 'myJoinedGroups']);
    // group member join and leave routes
    Route::post('/group/join', [GroupController::class, 'joinGroup']);
    Route::post('/group/leave', [GroupController::class, 'leaveGroup']);
    // group invite routes
    Route::get('/group-invite-link/{id}', [GroupController::class, 'generateInviteLink']);
    Route::get('/group/join/invite/{group_id}', [GroupController::class, 'joinGroup'])
        ->name('group.invite.join')
        ->middleware('signed');

    // company, skill and institution suggestions for dropdown
    Route::get('/company-suggestions', [UserExperienceController::class, 'companySuggestions']);
    Route::get('/skill-suggestions', [UserExperienceController::class, 'skillSuggestions']);
    Route::get('/institution-suggestions', [UserEducationController::class, 'institutionSuggestions']);

    // user profile routes
    Route::get('/profile', [UserProfileController::class, 'show']);
    Route::put('/profile/update', [UserProfileController::class, 'update']);
    Route::post('/profile/images', [UserProfileController::class, 'updateImages']);

    // user experience routes
    Route::get('/experience/list', [UserExperienceController::class, 'index']);
    Route::post('/experience/add', [UserExperienceController::class, 'store']);
    Route::get('/experience/edit/{id}', [UserExperienceController::class, 'edit']);
    Route::post('/experience/update/{id}', [UserExperienceController::class, 'update']);
    Route::delete('/experience/delete/{id}', [UserExperienceController::class, 'destroy']);

    // user education routes
    Route::get('/education/list', [UserEducationController::class, 'index']);
    Route::post('/education/add', [UserEducationController::class, 'store']);
    Route::get('/education/edit/{id}', [UserEducationController::class, 'edit']);
    Route::post('/education/update/{id}', [UserEducationController::class, 'update']);
    Route::delete('/education/delete/{id}', [UserEducationController::class, 'destroy']);

    // user connection routes
    Route::get('/connections', [UserConnectionController::class, 'index']);
    Route::get('/connections/suggestions', [UserConnectionController::class, 'suggestions']);
    Route::get('/connections/requests', [UserConnectionController::class, 'requests']);
    Route::delete('/connections/{user}/remove', [UserConnectionController::class, 'removeConnection']);
    Route::post('/connections/{user}/follow', [UserConnectionController::class, 'follow']);
    Route::post('/connections/request', [UserConnectionController::class, 'sendRequest']);
    Route::post('/connections/requests/{connectionRequest}/accept', [UserConnectionController::class, 'accept']);
    Route::post('/connections/requests/{connectionRequest}/ignore', [UserConnectionController::class, 'ignore']);

    // followers and following routes
    Route::get('/connections/followers', [UserConnectionController::class, 'followers']);
    Route::get('/connections/following', [UserConnectionController::class, 'following']);
    Route::delete('/connections/{user}/unfollow', [UserConnectionController::class, 'unfollow']);
});
