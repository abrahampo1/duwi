<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('app.supported_locales', ['en', 'es', 'fa']);

        if (session('locale') && in_array(session('locale'), $supported)) {
            app()->setLocale(session('locale'));
        } else {
            $preferred = $request->getPreferredLanguage($supported);
            $locale = $preferred && in_array($preferred, $supported) ? $preferred : config('app.locale');
            app()->setLocale($locale);
            session(['locale' => $locale]);
        }

        return $next($request);
    }
}
