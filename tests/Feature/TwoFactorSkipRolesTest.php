<?php

use HasinHayder\TyroLogin\Tests\Fixtures\RoleUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    config()->set('tyro-login.two_factor.enabled', true);
    config()->set('tyro-login.user_model', RoleUser::class);
    config()->set('auth.providers.users.model', RoleUser::class);
});

function createRoleUser(string $role, string $email = 'role@example.com'): RoleUser {
    return RoleUser::forceCreate([
        'name' => 'Role User',
        'email' => $email,
        'password' => Hash::make('password123'),
        'role' => $role,
    ]);
}

// Registration flow

it('does not prompt 2FA setup after registration for a skip role', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');

    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
    expect(RoleUser::where('email', 'jane@example.com')->first()->role)->toBe('user');
});

it('still prompts 2FA setup after registration when forced_roles overrides skip_roles', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');
    config()->set('tyro-login.two_factor.forced_roles', 'user');

    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/two-factor/setup');

    $this->assertGuest();
});

it('still prompts 2FA setup after registration for a role not listed in skip_roles', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'editor');

    $this->post('/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/two-factor/setup');
});

// Login flow

it('logs a skip-role user in directly without the 2FA setup prompt', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');
    createRoleUser('user');

    $this->post('/login', [
        'email' => 'role@example.com',
        'password' => 'password123',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
});

it('skips the 2FA setup prompt even when allow_skip is disabled', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');
    config()->set('tyro-login.two_factor.allow_skip', false);
    createRoleUser('user');

    $this->post('/login', [
        'email' => 'role@example.com',
        'password' => 'password123',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
});

it('prompts 2FA setup for a role not listed in skip_roles', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user,editor');
    createRoleUser('admin');

    $this->post('/login', [
        'email' => 'role@example.com',
        'password' => 'password123',
    ])->assertRedirect('/two-factor/setup');

    $this->assertGuest();
});

it('lets forced_roles override skip_roles on login', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');
    config()->set('tyro-login.two_factor.forced_roles', 'user,admin');
    createRoleUser('user');

    $this->post('/login', [
        'email' => 'role@example.com',
        'password' => 'password123',
    ])->assertRedirect('/two-factor/setup');

    $this->assertGuest();
});

it('still challenges a skip-role user who already confirmed 2FA', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');

    $user = createRoleUser('user');
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->post('/login', [
        'email' => 'role@example.com',
        'password' => 'password123',
    ])->assertRedirect('/two-factor/challenge');

    $this->assertGuest();
});

it('never lets a confirmed 2FA user bypass the challenge via the skip endpoint', function () {
    config()->set('tyro-login.two_factor.allow_skip', true);

    $user = createRoleUser('user');
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->post('/login', [
        'email' => 'role@example.com',
        'password' => 'password123',
    ])->assertRedirect('/two-factor/challenge');

    $this->post('/two-factor/skip')
        ->assertRedirect('/two-factor/challenge');

    $this->assertGuest();
});

it('never lets a confirmed 2FA user bypass the challenge via the ignore endpoint', function () {
    config()->set('tyro-login.two_factor.allow_skip', true);

    $user = createRoleUser('user');
    $user->forceFill(['two_factor_confirmed_at' => now()])->save();

    $this->post('/login', [
        'email' => 'role@example.com',
        'password' => 'password123',
    ])->assertRedirect('/two-factor/challenge');

    $this->post('/two-factor/ignore')
        ->assertRedirect('/two-factor/challenge');

    $this->assertGuest();
});

// Setup page guard

it('redirects a skip-role user away from the setup page during the login flow', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');

    $user = createRoleUser('user');

    // Simulate the automatic post-login prompt: logged out, login.id stashed.
    session(['login.id' => $user->id, 'login.remember' => false]);

    $this->get('/two-factor/setup')->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});

it('lets an authenticated skip-role user open the setup page to enroll voluntarily', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');

    $user = createRoleUser('user');
    Auth::login($user);

    $this->get('/two-factor/setup')->assertOk()->assertSee('Two Factor');
});

it('shows the setup page to a skip-role user when the role is also forced', function () {
    config()->set('tyro-login.two_factor.skip_roles', 'user');
    config()->set('tyro-login.two_factor.forced_roles', 'user');

    $user = createRoleUser('user');
    Auth::login($user);

    $this->get('/two-factor/setup')->assertOk()->assertSee('Two Factor');
});
