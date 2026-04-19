<?php

namespace App\Http\Middleware;

use App\Models\Geography\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $this->language($request);

        if ($response) {
            return $response;
        }

        return $next($request);
    }

    private function language(Request $request): ?Response
    {
        $defaultLanguage = Language::getDefaultCode(config('app.locale', 'az'));
        $languages = Language::where('is_active', 1)->pluck('code')->toArray();

        if (!count($languages)) {
            Log::channel('site')->warning('Diller yaradilmayib [log LanguageMiddleware faylindan yazilib]');
            App::setLocale($defaultLanguage);
            Session::put('locale', $defaultLanguage);
            return null;
        }

        $routeLanguage = $request->route('language') ?? $request->segment(1);

        if ($routeLanguage === 'gopanel') {
            return null;
        }

        $language = $routeLanguage;

        if (!$language || !in_array($language, $languages, true)) {
            $sessionLanguage = Session::get('locale');
            $language = in_array($sessionLanguage, $languages, true)
                ? $sessionLanguage
                : $defaultLanguage;
        }

        if (!$request->segment(1) && $request->path() === '/') {
            App::setLocale($language);
            Session::put('locale', $language);

            return redirect()->route("site.{$language}.home.index");
        }

        if (in_array($language, $languages, true)) {
            App::setLocale($language);
            Session::put('locale', $language);
            return null;
        }

        Log::channel('site')->warning(
            "Gonderilen dil tapilmadi dil: {$language} [log LanguageMiddleware faylindan yazilib]",
            ['languages' => $languages]
        );

        abort(404);
    }
}
