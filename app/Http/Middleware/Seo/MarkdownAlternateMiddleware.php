<?php

namespace App\Http\Middleware\Seo;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * HTML səhifəyə `Link: <...md>; rel="alternate"; type="text/markdown"` başlığı
 * əlavə edir - `<head>`-dəki `<link>` ilə eyni mənbədir (`markdown_url()`),
 * sadəcə HTML-i emal etmədən (HEAD sorğusu ilə) tapan agentlər üçündür.
 *
 * `web` qrupundadır: `markdown_url()` səhifənin Markdown variantı olmayanda
 * (panel, texniki ünvanlar, `.md`/`.txt`/`.xml` faylları) `null` qaytarır,
 * ona görə həmin cavablara heç nə əlavə olunmur. Xəta səhifəni sındırmamalıdır.
 */

class MarkdownAlternateMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!$request->isMethod('GET') || $response->isRedirection() || !$response->isSuccessful()) {
            return $response;
        }

        // Yalnız HTML cavaba - JSON/XML/plain-text cavablarda yeri yoxdur
        if (!str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        try {
            $url = markdown_url();
        } catch (\Throwable $e) {
            report($e);
            $url = null;
        }

        if ($url !== null) {
            $response->headers->set('Link', '<' . $url . '>; rel="alternate"; type="text/markdown"', false);
        }

        return $response;
    }
}
