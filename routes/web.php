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

// Media streaming routes with full HTTP 206 Byte-Range support for smooth scrubbing and seeking
Route::get('/storage/posts/{filename}', function (string $filename) {
    $path = storage_path('app/public/posts/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'public, max-age=604800',
    ]);
});

Route::get('/storage/industries/posts/{filename}', function (string $filename) {
    $path = storage_path('app/public/industries/posts/' . $filename);
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Accept-Ranges' => 'bytes',
        'Cache-Control' => 'public, max-age=604800',
    ]);
});

// Sub-route files: Admin Web Panel & Web Auth
require __DIR__.'/web/admin.php';
require __DIR__.'/web/auth.php';
