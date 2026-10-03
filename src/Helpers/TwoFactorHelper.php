<?php

namespace HasinHayder\TyroLogin\Helpers;

class TwoFactorHelper {
    /**
     * Parse a comma-separated role list from config into a trimmed, non-empty array.
     */
    public static function parseRoles(string $roles): array {
        return array_values(array_filter(array_map('trim', explode(',', $roles))));
    }

    /**
     * Determine whether the user belongs to any of the given roles.
     *
     * Supports Spatie / Bouncer / Tyro style hasRole() methods and falls back
     * to a simple scalar 'role' attribute.
     */
    public static function userHasAnyRole($user, array $roles): bool {
        if (empty($roles)) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }

            return false;
        }

        if (isset($user->role)) {
            return in_array($user->role, $roles);
        }

        return false;
    }

    /**
     * Determine whether the user belongs to a role that must set up 2FA and cannot skip.
     */
    public static function userHasForcedRole($user): bool {
        $forcedRoles = (string) config('tyro-login.two_factor.forced_roles', '');

        if ($forcedRoles === '') {
            return false;
        }

        return self::userHasAnyRole($user, self::parseRoles($forcedRoles));
    }

    /**
     * Determine whether the user belongs to a role that should not be
     * prompted to set up 2FA.
     */
    public static function userHasSkipRole($user): bool {
        $skipRoles = (string) config('tyro-login.two_factor.skip_roles', '');

        if ($skipRoles === '') {
            return false;
        }

        return self::userHasAnyRole($user, self::parseRoles($skipRoles));
    }

    /**
     * Determine whether the user should be exempt from the 2FA setup prompt
     * because of their role.
     *
     * forced_roles always wins: a role listed in both forced_roles and
     * skip_roles is still required to set up 2FA.
     */
    public static function userShouldSkipTwoFactorSetup($user): bool {
        if (self::userHasForcedRole($user)) {
            return false;
        }

        return self::userHasSkipRole($user);
    }
}
