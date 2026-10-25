<?php

declare(strict_types=1);

namespace App\Http\Middleware\Gopanel;

use App\Services\Gopanel\Admins\AdminPinService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Həssas əməliyyatdan əvvəl PIN təsdiqi (alias `gopanel.pin`).
 *
 * Bağlandığı route-u qoruyur. Heç nə soruşulmayan hallar - mövcud axın
 * olduğu kimi qalır:
 *  - `custom.security.admin_pin.enabled = false` (default);
 *  - admin PIN təyin etməyib;
 *  - son `ttl_minutes` ərzində artıq təsdiqləyib.
 *
 * Cavab sorğunun növünə görə iki cürdür:
 *  - AJAX / JSON → 423 + `pin_required`; `modules/security/admin-pin.js`
 *    bunu tutur, modal açır və təsdiqdən sonra sorğunu TƏKRARLAYIR;
 *  - adi GET keçidi (məs. backup endirmə linki) → təsdiq səhifəsinə
 *    yönləndirmə. Niyə: brauzer naviqasiyasına JSON qaytarmaq mənasızdır,
 *    istifadəçi boş səhifə görərdi. Hədəf URL sessiyada saxlanılır
 *    (query-də yox) - açıq yönləndirmə (open redirect) yaranmasın.
 */
final class RequireAdminPin
{
    /** Təsdiqdən sonra qayıdılacaq URL (sessiya açarı). */
    public const INTENDED_KEY = 'gopanel_pin_intended';

    /** Təsdiq səhifəsindəki «Geri» linki (sessiya açarı). */
    public const BACK_KEY = 'gopanel_pin_back';

    public function __construct(private readonly AdminPinService $pins)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('gopanel')->user();

        if ($admin === null || !$this->pins->needsConfirmation($admin)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax() || !$request->isMethod('GET')) {
            return response()->json([
                'status'       => 'error',
                'pin_required' => true,
                'message'      => 'Bu əməliyyat üçün PIN təsdiqi lazımdır.',
            ], 423);
        }

        $request->session()->put(self::INTENDED_KEY, $request->fullUrl());
        $request->session()->put(self::BACK_KEY, url()->previous(route('gopanel.index')));

        return redirect()->route('gopanel.pin.prompt');
    }
}
