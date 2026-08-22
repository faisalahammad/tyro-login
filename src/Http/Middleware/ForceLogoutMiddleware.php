<?php

namespace HasinHayder\TyroLogin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ForceLogoutMiddleware {
    /**
     * Log the current user out if a force-logout flag exists for them.
     */
    public function handle(Request $request, Closure $next): Response {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $key = 'tyro-login-force-logout-'.$user->getAuthIdentifier();

        if (! Cache::has($key)) {
            return $next($request);
        }

        Cache::forget($key);

        Auth::logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect(config('tyro-login.redirects.after_logout', '/login'));
    }
}
