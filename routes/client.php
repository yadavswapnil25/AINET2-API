<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\Client\LoginController;
use App\Http\Controllers\Client\PaymentController;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API working fines!',
    ]);
});

Route::post('/membership-signup', [MembershipController::class, 'signup']);
Route::get('eventValidationHandle', [PaymentController::class, 'eventValidationHandle']);
Route::post('/auth/login', [LoginController::class, 'login'])->name('login');
Route::middleware('auth:api')->group(function () {
    Route::get('/auth/profile', [LoginController::class, 'profile']);
    Route::post('/auth/{id}/profile', [LoginController::class, 'updateProfile']);
});
