<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use function response;

trait Response
{
    /**
     * Return a success JSON response.
     *
     * @param string|null $message
     * @param int         $code
     * @param mixed       $data
     * @return JsonResponse
     */
    protected function success(string $message, int $code = 200, mixed $data = null): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param string|null       $message
     * @param int               $code
     * @param array|string|null $extra
     * @return JsonResponse
     */
    protected function error(?string $message, int $code, array|string $extra = null): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $extra
        ], $code);
    }
}
