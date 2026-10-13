<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ExpireOwnerSession
{
    public const STARTED_AT = 'owner_authenticated_at';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() === null) {
            return $next($request);
        }

        $started = $request->session()->get(self::STARTED_AT);
        $limit = now()->subMinutes((int) config('session.absolute_lifetime'))->getTimestamp();

        if (! is_int($started) || $started < $limit) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        }

        return $next($request);
    }
}
