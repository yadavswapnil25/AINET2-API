<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\Client\PaymentController;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API working fine!',
    ]);
});

Route::post('/membership-signup', [MembershipController::class, 'signup']);
 Route::get('eventValidationHandle', [PaymentController::class, 'eventValidationHandle']);