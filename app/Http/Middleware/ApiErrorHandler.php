<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiErrorHandler {
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        try {
            return $next($request);
        } catch (NotFoundHttpException $exception) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'The requested API endpoint does not exist.'
                ], 404);
            }
        }
    }
}
