<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/welcome');
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::get('/clear', function () {
    Artisan::call('optimize:clear');
    return "Cleared!";
});


require __DIR__.'/auth.php';
