<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
            //
        });
        
        // Add custom handling for 403 Forbidden errors
        $this->renderable(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 403) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'error' => true,
                        'message' => $e->getMessage() ?: 'Your account has been disabled. Please contact support.',
                    ], 403);
                }
                
                // For web requests, render the custom 403 error page
                // return response()->view('errors.403', ['exception' => $e], 403);
            }
        });
    }
}
