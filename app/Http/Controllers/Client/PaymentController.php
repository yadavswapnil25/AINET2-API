<?php

namespace App\Http\Controllers\Client;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;

class PaymentController extends Controller
{
      public function eventValidationHandle(Request $request): JsonResponse
    {
        $email = $request->input('email');

        // Find the user by email
        $user = User::where('email', $email)
            ->first();

        if (!$user) {
            // If the user is not found, return a success message
            return response()->json([
                'status' => true,
                'message' => 'Validation passed. You can register.',
            ]);
        }else{
            // If the user is found, return a failure message
            return response()->json([
                'status' => false,
                'message' => 'You have already registered.',
            ]);
        }
    }
}
