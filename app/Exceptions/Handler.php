<?php

namespace App\Exceptions;

use App\Helpers\ApiFormatter;
use App\Models\LogModel;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Tymon\JWTAuth\Facades\JWTAuth;

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

    public function render($request, Throwable $exception)
    {
        //Tangani error 404 not found
        if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $user = null;
            try {
                $user = JWTAuth::parseToken()->authenticate();
            } catch (\Exception $e) {
                $user = null;
            }

            $filteredRequest = ApiFormatter::filterSensitiveData($request->all());
            LogModel::create([
                'user_id' => $user ? $user->id : null,
                'log_method' => $request->method(),
                'log_url' => $request->fullUrl(),
                'log_ip' => $request->ip(),
                'log_request' => json_encode($filteredRequest),
                'log_response' => json_encode(ApiFormatter::createJson(404, 'Not Found', 'Route not Found')),
            ]);

            return response()->json(ApiFormatter::createJson(404, 'Not FOund', 'Route not Found'), 404);
        }

        return parent::render($request, $exception);
    }
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
