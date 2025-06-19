<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Locale;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $http_accept_language = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);

            $language = explode('_', $http_accept_language)[0];
            if ($language === 'en' || $language === 'it') {
                app()->setLocale($language);

                return $next($request);
            }
        }

        if (! $request->user()) {
            return $next($request);
        }

        /*
         @TODO
        $language = $request->user()->language;

        if (isset($language)) {
            app()->setLocale($language);
        }*/

        return $next($request);
    }
}
