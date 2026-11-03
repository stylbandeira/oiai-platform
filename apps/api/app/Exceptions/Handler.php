<?php

namespace App\Exceptions;

use App\Services\ExternalCallTelemetry;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Sentry\State\Scope;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

use function Sentry\captureException;
use function Sentry\configureScope;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
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
            if (app()->environment('testing') || blank(config('sentry.dsn'))) {
                return;
            }

            try {
                configureScope(function (Scope $scope): void {
                    $scope->setTag('environment', (string) config('app.env'));
                    $scope->setTag('correlation_id', app(ExternalCallTelemetry::class)->correlationId());
                });
                captureException($e);
            } catch (Throwable) {
                // Error tracking must never replace the application's error handling.
            }
        });

        $this->renderable(function (Throwable $exception, Request $request) {
            if (app()->environment('production') === false || $request->expectsJson() === false) {
                return null;
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            if ($status < 500) {
                return null;
            }

            return response()->json([
                'message' => 'Ocorreu um erro interno. Tente novamente mais tarde.',
            ], 500);
        });
    }
}
