<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Models\User;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected RazorpayService $razorpay)
    {
    }

    public function eventValidationHandle(Request $request): JsonResponse
    {
        $email = (string) $request->input('email', '');
        $normalizedEmail = strtolower(trim($email));

        if ($normalizedEmail === '') {
            return response()->json([
                'status' => true,
                'message' => 'Validation passed. You can register.',
            ]);
        }

        // Find the user by email (case-insensitive, including soft deleted records)
        $user = User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if (!$user) {
            // If the user is not found, return a success message
            return response()->json([
                'status' => true,
                'message' => 'Validation passed. You can register.',
            ]);
        }

        // If the user is found, return a failure message
        return response()->json([
            'status' => false,
            'message' => 'You have already registered.',
        ]);
    }

    /**
     * Create a Razorpay order to initiate payment with auto capture.
     */
    public function createOrder(CreateOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $order = $this->razorpay->createOrder(
            $data['amount'],
            $data['currency'] ?? 'INR',
            $data['receipt'] ?? null,
            $data['notes'] ?? []
        );

        $customer = $data['customer'] ?? [];

        return response()->json([
            'status' => true,
            'message' => 'Order created successfully',
            'data' => [
                'order' => $order,
                'customer' => $customer,
                'currency' => $order['currency'] ?? ($data['currency'] ?? 'INR'),
                'amount' => $order['amount'] ?? ((int) round(((float) $data['amount']) * 100)),
            ],
        ]);
    }
}
