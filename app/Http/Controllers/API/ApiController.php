<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ApiController extends Controller {

    public function __construct() {
    }

    public function success($data, int $status = 200) {
        $response = ['success' => true];
        $response = array_merge($response, is_array($data) ? ['data' => $data] : ['message' => $data]);
        return response()->json($response, $status);
    }

    public function error(string $message, int $status = 400) {
        return response()->json(['success' => false, 'message' => $message], $status);
    }

    public function handleException($e) {
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
