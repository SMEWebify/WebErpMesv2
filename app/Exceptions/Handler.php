<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
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
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable   $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable  $exception)
    {
        // return parent::render($request, $exception);
        if ($exception instanceof HttpExceptionInterface) {
            if (env('APP_ENV') === 'production' && $exception->getCode() == 403) {
                return response()->view('errors.403', [], 403);
            }
            if (env('APP_ENV') === 'production' && $exception->getCode() == 404) {
                return response()->view('errors.404', [], 404);
            }
            if (env('APP_ENV') === 'production' && $exception->getCode() == 419) {
                return response()->view('errors.419', [], 419);
            }
            if (env('APP_ENV') === 'production' && $exception->getCode() == 500) {
                return response()->view('errors.500', [], 500);
            }
        }

        return parent::render($request, $exception);
    }
}
