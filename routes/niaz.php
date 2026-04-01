<?php

use Illuminate\Support\Facades\Route;



Route::middleware(['auth:api', 'role:admin'])->group(function () {


});


Route::middleware(['auth:api', 'role:user'])->group(function () {


});
