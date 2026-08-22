<?php

use HasinHayder\TyroLogin\Events\ForceLogout;
use HasinHayder\TyroLogin\Tests\Fixtures\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware(['web', 'auth'])->group(function () {
        Route::get('/_force-logout-test', fn () => 'ok');
    });
});

function createTestUser(): User {
    return User::forceCreate([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
}

it('creates a force logout cache key when the event is dispatched', function () {
    event(new ForceLogout(2));

    expect(Cache::has('tyro-login-force-logout-2'))->toBeTrue();
});

it('stores a timestamp value in the cache key', function () {
    event(new ForceLogout(2));

    expect(Cache::get('tyro-login-force-logout-2'))->toBeInt();
});

it('creates a force logout cache key via the artisan command with an ID', function () {
    $user = createTestUser();

    $this->artisan('tyro-login:logout', ['identifier' => (string) $user->id])
        ->assertSuccessful();

    expect(Cache::has('tyro-login-force-logout-'.$user->id))->toBeTrue();
});

it('creates a force logout cache key via the artisan command with an email', function () {
    $user = createTestUser();

    $this->artisan('tyro-login:logout', ['identifier' => $user->email])
        ->assertSuccessful();

    expect(Cache::has('tyro-login-force-logout-'.$user->id))->toBeTrue();
});

it('fails when the user does not exist', function () {
    $this->artisan('tyro-login:logout', ['identifier' => 'ghost@example.com'])
        ->assertFailed();

    expect(Cache::has('tyro-login-force-logout-1'))->toBeFalse();
});

it('forces the user out on their next request and clears the cache', function () {
    $user = createTestUser();
    $this->actingAs($user);

    event(new ForceLogout($user->id));

    $this->get('/_force-logout-test')->assertRedirect('/login');

    $this->assertGuest();
    expect(Cache::has('tyro-login-force-logout-'.$user->id))->toBeFalse();
});

it('works via the artisan command end to end', function () {
    $user = createTestUser();
    $this->actingAs($user);

    $this->artisan('tyro-login:logout', ['identifier' => (string) $user->id])
        ->assertSuccessful();

    $this->get('/_force-logout-test')->assertRedirect('/login');
    $this->assertGuest();
});

it('does not disturb authenticated users without a flag', function () {
    $user = createTestUser();
    $this->actingAs($user);

    $this->get('/_force-logout-test')->assertStatus(200);

    $this->assertAuthenticated();
});

it('lets guests pass through untouched', function () {
    event(new ForceLogout(99));

    $this->get('/_force-logout-test')->assertRedirect();

    expect(Cache::has('tyro-login-force-logout-99'))->toBeTrue();
});

it('defaults the ttl to session lifetime plus 10 minutes', function () {
    expect(config('tyro-login.force_logout.ttl'))
        ->toBe((int) config('session.lifetime') + 1);
});

it('registers the middleware on the web group exactly once', function () {
    $this->get('/_force-logout-test');

    $middleware = request()->route()->middleware();

    expect($middleware)->toContain(\HasinHayder\TyroLogin\Http\Middleware\ForceLogoutMiddleware::class);
    expect(array_keys($middleware, \HasinHayder\TyroLogin\Http\Middleware\ForceLogoutMiddleware::class))->toHaveCount(1);
});
