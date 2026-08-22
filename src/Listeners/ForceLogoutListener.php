<?php

namespace HasinHayder\TyroLogin\Listeners;

use HasinHayder\TyroLogin\Events\ForceLogout;
use Illuminate\Support\Facades\Cache;

class ForceLogoutListener {
    /**
     * Write the force-logout flag to the cache for the given user.
     */
    public function handle(ForceLogout $event): void {
        $ttl = (int) config('tyro-login.force_logout.ttl', (int) config('session.lifetime', 120) + 1);

        Cache::put(
            'tyro-login-force-logout-'.$event->userId,
            now()->timestamp,
            $ttl > 0 ? now()->addMinutes($ttl) : null
        );
    }
}
