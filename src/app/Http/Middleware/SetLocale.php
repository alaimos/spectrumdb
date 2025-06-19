<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Locale;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    public const array AVAILABLE_LOCALES = [
        'en' => 'English',
        'it' => 'Italian',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $language = $this->detectLanguage($request);

        if (isset($language)) {
            app()->setLocale($language);
        }

        return $next($request);
    }

    private function detectLanguage(Request $request): ?string
    {
        $language = null;
        if (($user = $request->user()) && isset($user->language)) {
            $language = $user->language;
        }
        if (! empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) && empty($language)) {
            $httpLocale = Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            $httpLanguage = explode('_', $httpLocale)[0];
            if (isset(self::AVAILABLE_LOCALES[$httpLanguage])) {
                $language = $httpLanguage;
            }

        }

        return $language;
    }
}
