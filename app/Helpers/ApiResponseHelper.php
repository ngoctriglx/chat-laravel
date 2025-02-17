<?php

namespace App\Helpers;

use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Throwable;

class ApiResponseHelper
{
    public static function success($data, int $status = 200)
    {
        $response = ['success' => true];
        $response = array_merge($response, is_array($data) ? ['data' => $data] : ['message' => $data]);
        return response()->json($response, $status);
    }

    public static function error(string $message, int $status = 400)
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    public static function handleException(Throwable $e)
    {
        \Log::error('API Error:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($e instanceof ValidationException) {
            return self::error($e->getMessage(), 422);
        }

        if ($e instanceof QueryException) {
            return self::error('Database error occurred.', 500);
        }

        return self::error('An unexpected error occurred.', 500);
    }
}
