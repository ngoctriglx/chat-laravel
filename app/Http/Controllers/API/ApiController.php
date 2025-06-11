<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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

    public function handleException($e) {
        \Log::error('API Error:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        // Handle validation exceptions with detailed error messages
        if ($e instanceof ValidationException) {
            $errors = $e->errors();
            $errorMessages = [];
            
            foreach ($errors as $field => $messages) {
                $errorMessages[] = ucfirst($field) . ': ' . implode(', ', $messages);
            }
            
            $message = count($errorMessages) > 1 
                ? 'Validation failed: ' . implode('; ', $errorMessages)
                : $errorMessages[0];
                
            return self::error($message, 422);
        }

        // Handle database query exceptions with detailed messages
        if ($e instanceof QueryException) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            // Handle specific database error codes
            switch ($errorCode) {
                case 23000: // Duplicate entry
                    if (preg_match('/Duplicate entry.*for key.*\'(.+?)\'/', $errorMessage, $matches)) {
                        $field = $matches[1];
                        return self::error("A record with this {$field} already exists.", 409);
                    }
                    return self::error('Duplicate entry found in database.', 409);
                    
                case 1452: // Foreign key constraint failure
                    return self::error('Cannot delete or update record due to foreign key constraints.', 409);
                    
                case 1451: // Cannot delete or update a parent row
                    return self::error('Cannot delete this record as it is referenced by other records.', 409);
                    
                case 1264: // Out of range value
                    return self::error('The provided value is out of the allowed range.', 422);
                    
                case 1366: // Incorrect integer value
                    return self::error('Invalid data type provided for a field.', 422);
                    
                case 1048: // Column cannot be null
                    if (preg_match('/Column \'(.+?)\' cannot be null/', $errorMessage, $matches)) {
                        $column = $matches[1];
                        return self::error("The field '{$column}' is required and cannot be empty.", 422);
                    }
                    return self::error('Required field is missing or empty.', 422);
                    
                case 1054: // Unknown column
                    if (preg_match('/Unknown column \'(.+?)\'/', $errorMessage, $matches)) {
                        $column = $matches[1];
                        return self::error("Unknown field '{$column}' in the request.", 422);
                    }
                    return self::error('Invalid field name provided.', 422);
                    
                case 1146: // Table doesn't exist
                    return self::error('Database table not found. Please contact administrator.', 500);
                    
                default:
                    // For other database errors, provide a more specific message
                    if (str_contains($errorMessage, 'SQLSTATE')) {
                        return self::error('Database operation failed. Please check your input and try again.', 500);
                    }
                    return self::error('Database error occurred.', 500);
            }
        }

        // Handle model not found exceptions
        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return self::error("The requested {$model} was not found.", 404);
        }

        // Handle HTTP exceptions
        if ($e instanceof NotFoundHttpException) {
            return self::error('The requested resource was not found.', 404);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return self::error('The requested method is not allowed for this resource.', 405);
        }

        // For production, return generic error; for development, you might want to return more details
        if (config('app.debug')) {
            return self::error('An unexpected error occurred: ' . $e->getMessage(), 500);
        }

        return self::error('An unexpected error occurred.', 500);
    }
}
