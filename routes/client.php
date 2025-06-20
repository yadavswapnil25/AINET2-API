<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MembershipController;

Route::get('/test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API working fine!',
    ]);
});

Route::post('/membership-signup', [MembershipController::class, 'signup']);
