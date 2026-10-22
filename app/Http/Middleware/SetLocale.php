<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', $request->cookie('locale', 'el'));

        if (! in_array($locale, ['en', 'el'], true)) {
            $locale = 'el';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
