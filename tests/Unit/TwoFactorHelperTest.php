<?php

use HasinHayder\TyroLogin\Helpers\TwoFactorHelper;

it('parses comma-separated role lists', function () {
    expect(TwoFactorHelper::parseRoles('user, admin ,,editor'))->toBe(['user', 'admin', 'editor'])
        ->and(TwoFactorHelper::parseRoles(''))->toBe([]);
});

it('matches roles via hasRole method', function () {
    $user = new class {
        public function hasRole(string $role): bool {
            return $role === 'admin';
        }
    };

    config()->set('tyro-login.two_factor.skip_roles', 'user,admin');

    expect(TwoFactorHelper::userHasSkipRole($user))->toBeTrue();
});

it('matches roles via the scalar role attribute fallback', function () {
    $user = new class {
        public string $role = 'user';
    };

    config()->set('tyro-login.two_factor.skip_roles', 'user');

    expect(TwoFactorHelper::userHasSkipRole($user))->toBeTrue();
});

it('returns false when the user has no role support', function () {
    $user = new class {};

    config()->set('tyro-login.two_factor.skip_roles', 'user');

    expect(TwoFactorHelper::userHasSkipRole($user))->toBeFalse()
        ->and(TwoFactorHelper::userHasForcedRole($user))->toBeFalse();
});

it('does not skip 2FA setup when the role is forced', function () {
    $user = new class {
        public string $role = 'user';
    };

    config()->set('tyro-login.two_factor.skip_roles', 'user');
    config()->set('tyro-login.two_factor.forced_roles', 'user');

    expect(TwoFactorHelper::userHasSkipRole($user))->toBeTrue()
        ->and(TwoFactorHelper::userHasForcedRole($user))->toBeTrue()
        ->and(TwoFactorHelper::userShouldSkipTwoFactorSetup($user))->toBeFalse();
});

it('skips 2FA setup only when the role is skipped and not forced', function () {
    $user = new class {
        public string $role = 'user';
    };

    config()->set('tyro-login.two_factor.skip_roles', 'user,editor');
    config()->set('tyro-login.two_factor.forced_roles', 'admin');

    expect(TwoFactorHelper::userShouldSkipTwoFactorSetup($user))->toBeTrue();
});

it('does not skip 2FA setup when no skip roles are configured', function () {
    $user = new class {
        public string $role = 'user';
    };

    config()->set('tyro-login.two_factor.skip_roles', '');
    config()->set('tyro-login.two_factor.forced_roles', '');

    expect(TwoFactorHelper::userShouldSkipTwoFactorSetup($user))->toBeFalse();
});
