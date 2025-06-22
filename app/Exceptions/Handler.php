<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            \Log::error('API Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    private function handleApiException($request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            $errors = $e->errors();
            $errorMessages = [];
            
            foreach ($errors as $field => $messages) {
                $errorMessages[] = ucfirst($field) . ': ' . implode(', ', $messages);
            }
            
            $message = count($errorMessages) > 1 
                ? 'Validation failed: ' . implode('; ', $errorMessages)
                : $errorMessages[0];
                
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        if ($e instanceof QueryException) {
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            switch ($errorCode) {
                case 23000:
                    if (preg_match('/Duplicate entry.*for key.*\'(.+?)\'/', $errorMessage, $matches)) {
                        $field = $matches[1];
                        return response()->json(['success' => false, 'message' => "A record with this {$field} already exists."], 409);
                    }
                    return response()->json(['success' => false, 'message' => 'Duplicate entry found in database.'], 409);
                    
                case 1452:
                    return response()->json(['success' => false, 'message' => 'Cannot delete or update record due to foreign key constraints.'], 409);
                    
                case 1451:
                    return response()->json(['success' => false, 'message' => 'Cannot delete this record as it is referenced by other records.'], 409);
                    
                case 1264:
                    return response()->json(['success' => false, 'message' => 'The provided value is out of the allowed range.'], 422);
                    
                case 1366:
                    return response()->json(['success' => false, 'message' => 'Invalid data type provided for a field.'], 422);
                    
                case 1048:
                    if (preg_match('/Column \'(.+?)\' cannot be null/', $errorMessage, $matches)) {
                        $column = $matches[1];
                        return response()->json(['success' => false, 'message' => "The field '{$column}' is required and cannot be empty."], 422);
                    }
                    return response()->json(['success' => false, 'message' => 'Required field is missing or empty.'], 422);
                    
                case 1054:
                    if (preg_match('/Unknown column \'(.+?)\'/', $errorMessage, $matches)) {
                        $column = $matches[1];
                        return response()->json(['success' => false, 'message' => "Unknown field '{$column}' in the request."], 422);
                    }
                    return response()->json(['success' => false, 'message' => 'Invalid field name provided.'], 422);
                    
                case 1146:
                    return response()->json(['success' => false, 'message' => 'Database table not found. Please contact administrator.'], 500);
                    
                default:
                    if (str_contains($errorMessage, 'SQLSTATE')) {
                        return response()->json(['success' => false, 'message' => 'Database operation failed. Please check your input and try again.'], 500);
                    }
                    return response()->json(['success' => false, 'message' => 'Database error occurred.'], 500);
            }
        }

        if ($e instanceof ModelNotFoundException) {
            $model = class_basename($e->getModel());
            return response()->json(['success' => false, 'message' => "The requested {$model} was not found."], 404);
        }

        if ($e instanceof NotFoundHttpException) {
            return response()->json(['success' => false, 'message' => 'The requested resource was not found.'], 404);
        }

        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json(['success' => false, 'message' => 'The requested method is not allowed for this resource.'], 405);
        }

        if (config('app.debug')) {
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
    }
} 