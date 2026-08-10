<?php

use HasinHayder\TyroLogin\Tests\Fixtures\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config()->set('tyro-login.two_factor.enabled', true);
    config()->set('tyro-login.registration.auto_login', false);
});

function createUnconfirmedUser(): User {
    return User::forceCreate([
        'name' => 'Fresh User',
        'email' => 'fresh@example.com',
        'password' => Hash::make('password123'),
    ]);
}

it('reaches the 2FA setup page from the login flow for an unconfirmed user', function () {
    createUnconfirmedUser();

    $login = $this->post('/login', [
        'email' => 'fresh@example.com',
        'password' => 'password123',
    ]);

    // Login redirects the logged-out user to setup (login.id stashed in session).
    $login->assertRedirect('/two-factor/setup');

    $setup = $this->get('/two-factor/setup');
    $setup->assertStatus(200);
    $setup->assertSee('Two Factor');
});

it('does not put two-factor/setup behind the auth middleware (regression)', function () {
    $request = \Illuminate\Http\Request::create('/two-factor/setup', 'GET');
    $matched = app('router')->getRoutes()->match($request);

    expect($matched->gatherMiddleware())
        ->toContain('web')
        ->not()->toContain('auth')
        ->not()->toContain('guest');
});

it('also lets an already-authenticated user reach setup (Tyro-Dashboard workflow)', function () {
    $user = createUnconfirmedUser();
    Auth::login($user);

    $setup = $this->get('/two-factor/setup');
    $setup->assertStatus(200);
    $setup->assertSee('Two Factor');
});

it('shows recovery codes after confirming 2FA (route stays auth-reachable)', function () {
    config()->set('tyro-login.registration.auto_login', false);

    $user = createUnconfirmedUser();
    Auth::login($user);

    // Seed a secret so confirm() can verify the code (controller reads it
    // back via Crypt::decryptString since the fixture User has no cast).
    $secret = app(\PragmaRX\Google2FA\Google2FA::class)->generateSecretKey();
    $user->forceFill(['two_factor_secret' => \Illuminate\Support\Facades\Crypt::encryptString($secret)])->save();

    $validOtp = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret);

    $confirm = $this->post('/two-factor/confirm', ['code' => $validOtp]);

    // After confirm the user is logged in and forwarded to recovery codes.
    $confirm->assertRedirect('/two-factor/recovery-codes');

    $recovery = $this->get('/two-factor/recovery-codes');
    $recovery->assertStatus(200);
});
