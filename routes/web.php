<?php

declare(strict_types=1);

use App\Http\Controllers\Api\SecuritySettingsController;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/welcome');
Route::get('/welcome', fn (): Factory|View => view('welcome'))->name('welcome');

Route::get('/payment/success', fn (): Factory|View => view('payment-success'))->name('payment.success');
Route::get('/payment/cancel', fn (): Factory|View => view('payment-cancel'))->name('payment.cancel');
Route::get('/subscription-test', fn (): Factory|View => view('subscription-test'))->name('subscription.test');

// Security Routes when user clicks on the email link for suspicious login activities
Route::get('/security/email-resolve/{id}', [SecuritySettingsController::class, 'resolveFromEmail'])
    ->name('security.email-resolve')->middleware('signed');
Route::post('/security/update-password', [SecuritySettingsController::class, 'updatePasswordFromAlert'])
    ->name('security.update-password')->middleware('signed');

// Sub-route files: Admin Web Panel & Web Auth
require __DIR__.'/web/admin.php';
require __DIR__.'/web/auth.php';
