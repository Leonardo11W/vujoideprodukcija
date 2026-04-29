<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = config('app.locale', 'bs');
        session()->put('locale', $locale);
        app()->setLocale($locale);

        return $next($request);
    }
}
