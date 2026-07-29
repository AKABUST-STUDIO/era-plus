<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetUserLocale
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User
            && filled($user->locale)
            && array_key_exists($user->locale, config('app.locales'))
        ) {
            app()->setLocale($user->locale);
        }

        return $next($request);
    }
}
