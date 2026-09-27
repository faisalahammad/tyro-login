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

it('shows recovery codes as the direct response to a successful confirmation POST', function () {
    config()->set('tyro-login.registration.auto_login', false);

    $user = createUnconfirmedUser();
    Auth::login($user);

    // Seed a secret so confirm() can verify the code (controller reads it
    // back via Crypt::decryptString since the fixture User has no cast).
    $secret = app(\PragmaRX\Google2FA\Google2FA::class)->generateSecretKey();
    $user->forceFill(['two_factor_secret' => \Illuminate\Support\Facades\Crypt::encryptString($secret)])->save();

    $validOtp = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret);

    $confirm = $this->post('/two-factor/confirm', ['code' => $validOtp]);

    // Recovery codes are rendered by the confirmation POST itself - no redirect
    // to a bookmarked/shareable page.
    $confirm->assertOk();
    $confirm->assertSee('Recovery Codes');

    $storedCodes = json_decode(
        \Illuminate\Support\Facades\Crypt::decryptString($user->fresh()->two_factor_recovery_codes),
        true
    );

    expect($storedCodes)->toHaveCount(8);
    $confirm->assertSee($storedCodes[0]);
});

it('never exposes recovery codes to a direct GET request', function () {
    $user = createUnconfirmedUser();
    Auth::login($user);

    $secret = app(\PragmaRX\Google2FA\Google2FA::class)->generateSecretKey();
    $user->forceFill(['two_factor_secret' => \Illuminate\Support\Facades\Crypt::encryptString($secret)])->save();

    $validOtp = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret);

    $this->post('/two-factor/confirm', ['code' => $validOtp])->assertOk();

    // The user now has recovery codes stored, but no URL can display them.
    expect($user->fresh()->two_factor_recovery_codes)->not->toBeNull();

    $this->get('/two-factor/recovery-codes')->assertNotFound();
});

it('shows recovery codes in the login flow and logs the user in', function () {
    config()->set('tyro-login.registration.auto_login', false);

    $user = createUnconfirmedUser();

    $secret = app(\PragmaRX\Google2FA\Google2FA::class)->generateSecretKey();
    $user->forceFill(['two_factor_secret' => \Illuminate\Support\Facades\Crypt::encryptString($secret)])->save();

    $validOtp = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret);

    $this->post('/login', [
        'email' => 'fresh@example.com',
        'password' => 'password123',
    ])->assertRedirect('/two-factor/setup');

    $confirm = $this->post('/two-factor/confirm', ['code' => $validOtp]);

    $confirm->assertOk();
    $confirm->assertSee('Recovery Codes');
    $this->assertAuthenticatedAs($user);
});

it('never exposes recovery codes to an unauthenticated direct request', function () {
    $this->get('/two-factor/recovery-codes')->assertNotFound();
});

it('disables the two-factor endpoints when 2FA is not enabled', function () {
    config()->set('tyro-login.two_factor.enabled', false);

    $user = createUnconfirmedUser();
    Auth::login($user);

    $this->get('/two-factor/recovery-codes')->assertNotFound();
    $this->post('/two-factor/confirm', ['code' => '123456'])->assertNotFound();
});

it('does not re-show or regenerate recovery codes when the confirmation POST is replayed', function () {
    $user = createUnconfirmedUser();
    Auth::login($user);

    $secret = app(\PragmaRX\Google2FA\Google2FA::class)->generateSecretKey();
    $user->forceFill(['two_factor_secret' => \Illuminate\Support\Facades\Crypt::encryptString($secret)])->save();

    $validOtp = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret);

    $this->post('/two-factor/confirm', ['code' => $validOtp])->assertOk();

    $codesAfterConfirm = $user->fresh()->two_factor_recovery_codes;

    $replay = $this->post('/two-factor/confirm', ['code' => $validOtp]);

    // The replay must short-circuit to a redirect (302), never a view response.
    $replay->assertRedirect('/');
    expect($user->fresh()->two_factor_recovery_codes)->toBe($codesAfterConfirm);
});

it('sends the user to the post-login destination when finishing setup from recovery codes', function () {
    config()->set('tyro-login.redirects.after_login', '/dashboard');
    config()->set('tyro-login.two_factor.allow_skip', false);

    $user = createUnconfirmedUser();
    Auth::login($user);

    $secret = app(\PragmaRX\Google2FA\Google2FA::class)->generateSecretKey();
    $user->forceFill(['two_factor_secret' => \Illuminate\Support\Facades\Crypt::encryptString($secret)])->save();

    $validOtp = app(\PragmaRX\Google2FA\Google2FA::class)->getCurrentOtp($secret);

    $this->post('/two-factor/confirm', ['code' => $validOtp])
        ->assertOk()
        ->assertSee('Recovery Codes');

    // Finish must not route through the skip endpoint (which 403s when
    // skipping is disabled) — it continues to the after-login destination.
    $this->post('/two-factor/finish')->assertRedirect('/dashboard');
});
