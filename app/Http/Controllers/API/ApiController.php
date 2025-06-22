<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

class ApiController extends Controller {

    public function __construct() {
    }

    public function success($data, int $status = 200) {
        $response = ['success' => true];
        $response = array_merge($response, is_string($data) ? ['message' => $data] : ['data' => $data]);
        return response()->json($response, $status);
    }

    public function error(string $message, int $status = 400) {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
