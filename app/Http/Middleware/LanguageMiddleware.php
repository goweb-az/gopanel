<?php

namespace App\Http\Middleware;

use App\Models\Geography\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $this->language($request, $next);
        return $next($request);
    }


    private function language($request, $next)
    {
        $languages = Language::where("is_active", 1)->get()->pluck('code')->toArray();
        if (count($languages)) {
            $language = $request->route('language', 'az');
            if ($language == 'gopanel') {
                return $next($request);
            }
            if (in_array($language, $languages)) {
                App::setLocale($language);
                Session::put('locale', $language);
            } else {
                Log::channel("site")->warning("Gonderilen dil tapılmadı dil: {$language} [log LanguageMiddleware faylindan yazilib]", ['languages' => $languages]);
                abort(404);
            }
        } else {
            Log::channel("site")->warning("Dillər yaradılmayıb yaradılmayıb [log LanguageMiddleware faylindan yazilib]");
        }
    }
}
