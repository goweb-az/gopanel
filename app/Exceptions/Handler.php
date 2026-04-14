<?php

namespace App\Exceptions;

use App\Services\Activity\LogService;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
            // Laravel tərəfindən Validation xətaları onsuz da block-lanır (Log-a düşmür).
            // Lakin siz əllə `throw new \Exception("... boş ola bilməz")` atırsınızsa, 
            // və bunun sistem xətası (QueryException, TypeError və s.) kimi qeydə alınmamasını istəyirsinizsə:
            if (get_class($e) === \Exception::class) {
                return;
            }

            LogService::channel('system-errors')->error($e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'url'   => request()->fullUrl(),
                'ip'    => request()->ip(),
                'trace' => $e->getTraceAsString(),
            ]);
        });


        $this->renderable(function (ValidationException $e, $request) {
            if ($request->wantsJson())
                return response()->json([
                    'success' => false,
                    'message' => 'Məlumatlar düzgün deyil',
                    'errors' => $e->errors(),
                    'exception' => $e
                ], 422);
        });

        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->wantsJson())
                return response()->json([
                    'success'   => false,
                    'message'   => $e->getMessage(),
                    'exception' => $e
                ], 404);
        });

        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->header('Device') == 'app')
                return response()->json([
                    'success'   => false,
                    'message'   => 'Model tapılmadı',
                    'exception' => $e
                ], 404);
        });

        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->wantsJson())
                return response()->json([
                    'success'   => false,
                    'message'   => 'Doğrulanmamış İstifadəçi',
                    'exception' => $e
                ], 401);
        });
    }


    public function render($request, Throwable $e)
    {
        // return parent::render($request, $e);
        // ✅ Validation Exception (422) - errors qaytarsın
        if ($e instanceof ValidationException && ($request->wantsJson() || $request->ajax() || $request->expectsJson())) {
            return response()->json([
                'success'   => false,
                'message'   => $e->validator->errors()->first(),
                'errors'    => $e->errors(),
                'exception' => class_basename($e),
            ], 422);
        }

        // ✅ AJAX / API JSON response (digər xətalar)
        if ($request->wantsJson() || $request->ajax() || $request->expectsJson()) {
            $status = 500;

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            }

            return response()->json([
                'success'   => false,
                'message'   => $e->getMessage() ?: 'Server xətası',
                'exception' => class_basename($e),
            ], $status);
        }

        // if ($e instanceof HttpExceptionInterface && $e->getStatusCode() == 404) {
        //     return response()->view('panel.pages.errors.404', [], 404);
        // }

        // if ($e instanceof HttpExceptionInterface && $e->getStatusCode() == 500) {
        //     return response()->view('panel.pages.errors.500', [], 500);
        // }

        // if (!($e instanceof HttpExceptionInterface)) {
        //     return response()->view('panel.pages.errors.500', [], 500);
        // }

        return parent::render($request, $e);
    }
}
