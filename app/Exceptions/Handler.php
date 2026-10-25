<?php

namespace App\Exceptions;

use App\Exceptions\Domain\DomainException;
use App\Http\Responses\Api\ErrorResponse;
use App\Services\Activity\LogService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
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

            // İş qaydası istisnası gözlənilən haldır (balans çatmır, status uyğun deyil) -
            // sistem xətası jurnalını doldurmamalıdır.
            if ($e instanceof DomainException) {
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
        // API: vahid zərf (04-api.md §1). Panel/sayt JSON cavabları aşağıda olduğu kimi qalır -
        // crud.js `status` açarına bağlıdır, onu dəyişmək paneli sındırar.
        if ($request->is('api/*')) {
            return $this->renderApiException($e);
        }

        // return parent::render($request, $e);
        // ✅ Validation Exception (422) - errors qaytarsın
        if ($e instanceof ValidationException && ($request->wantsJson() || $request->ajax() || $request->expectsJson())) {
            return response()->json([
                // `status` panelin JSON müqaviləsidir (bax: 02-gopanel.md § 4) -
                // crud.js cavabı məhz bu açara görə uğur/xəta kimi tanıyır.
                'status'    => 'error',
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
            } elseif ($e instanceof DomainException) {
                // İş qaydası istisnası panel AJAX-ında da öz status/kodu ilə qayıtsın (500 yox)
                return response()->json([
                    'status'  => 'error',
                    'success' => false,
                    'code'    => $e->errorCode(),
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], $e->status());
            } elseif ($e instanceof AuthorizationException) {
                // FormRequest::authorize() false → əvvəl 500 qaytarırdı, panel «server xətası» göstərirdi
                $status = 403;
            } elseif ($e instanceof AuthenticationException) {
                $status = 401;
            } elseif ($e instanceof ModelNotFoundException) {
                $status = 404;
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

    /**
     * API istisnasını `{success:false, message, code, errors}` zərfinə çevirir.
     *
     * Daxili detal (exception sinfi, trace, SQL) cavaba YAZILMIR - o, yuxarıdakı
     * `reportable` ilə `system-errors` jurnalına gedir. 500-də istifadəçiyə ümumi mesaj.
     */
    protected function renderApiException(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException     => ErrorResponse::validation($e->errors()),
            $e instanceof DomainException         => ErrorResponse::make($e->getMessage(), $e->status(), $e->errorCode(), $e->errors()),
            $e instanceof AuthenticationException => ErrorResponse::make('Doğrulanmamış istifadəçi.', 401),
            $e instanceof AuthorizationException  => ErrorResponse::make('Bu əməliyyat üçün icazəniz yoxdur.', 403),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException   => ErrorResponse::make('Məlumat tapılmadı.', 404),
            $e instanceof ThrottleRequestsException => ErrorResponse::make('Çox sayda sorğu göndərildi. Bir az sonra yenidən cəhd edin.', 429),
            $e instanceof HttpExceptionInterface  => ErrorResponse::make(
                $e->getStatusCode() < 500 && $e->getMessage() !== '' ? $e->getMessage() : 'Sorğu yerinə yetirilmədi.',
                $e->getStatusCode(),
            ),
            default => ErrorResponse::make(
                config('app.debug') ? $e->getMessage() : 'Server xətası baş verdi.',
                500,
            ),
        };
    }
}
