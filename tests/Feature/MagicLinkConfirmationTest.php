<?php

use HasinHayder\TyroLogin\Tests\Fixtures\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    Config::set('tyro-login.features.magic_links_enabled', true);
    Config::set('tyro-login.features.magic_link_require_confirmation', true);
});

it('shows confirmation page when user accesses magic link via GET', function () {
    $user = User::forceCreate([
        'name' => 'Magic User',
        'email' => 'magic@example.com',
        'password' => Hash::make('secret'),
    ]);

    $hash = 'valid-test-hash-123';
    Cache::put("tyro_magic_link_{$hash}", [
        'hash' => $hash,
        'user_id' => $user->id,
        'expires_at' => now()->addMinutes(10)->timestamp,
        'created_at' => now()->timestamp,
        'used' => false,
        'ip' => null,
    ], now()->addMinutes(10));

    // Crawlers/messengers hitting GET /mlogin?hash=...
    $response = $this->get("/mlogin?hash={$hash}");

    $response->assertStatus(200);
    $response->assertSee('Confirm Login');
    $response->assertSee('magic@example.com');
    $response->assertSee('Do you want to log in?');
    $response->assertSee('Log In');

    // Link must NOT be consumed by the GET request
    $cached = Cache::get("tyro_magic_link_{$hash}");
    expect($cached['used'])->toBeFalse();
    $this->assertGuest();
});

it('consumes the magic link and logs in when user confirms via POST', function () {
    $user = User::forceCreate([
        'name' => 'Magic User',
        'email' => 'magic@example.com',
        'password' => Hash::make('secret'),
    ]);

    $hash = 'valid-test-hash-456';
    Cache::put("tyro_magic_link_{$hash}", [
        'hash' => $hash,
        'user_id' => $user->id,
        'expires_at' => now()->addMinutes(10)->timestamp,
        'created_at' => now()->timestamp,
        'used' => false,
        'ip' => null,
    ], now()->addMinutes(10));

    $response = $this->post('/mlogin', [
        'hash' => $hash,
    ]);

    $response->assertRedirect(config('tyro-login.redirects.after_login', '/'));
    $this->assertAuthenticatedAs($user);

    // Link should now be marked as used
    $cached = Cache::get("tyro_magic_link_{$hash}");
    expect($cached['used'])->toBeTrue();
});

it('rejects an already used magic link on confirmation page and submit', function () {
    $user = User::forceCreate([
        'name' => 'Magic User',
        'email' => 'magic@example.com',
        'password' => Hash::make('secret'),
    ]);

    $hash = 'already-used-hash';
    Cache::put("tyro_magic_link_{$hash}", [
        'hash' => $hash,
        'user_id' => $user->id,
        'expires_at' => now()->addMinutes(10)->timestamp,
        'created_at' => now()->timestamp,
        'used' => true,
        'ip' => '127.0.0.1',
    ], now()->addMinutes(10));

    // GET request
    $getResponse = $this->get("/mlogin?hash={$hash}");
    $getResponse->assertRedirect(route('tyro-login.login'));
    $getResponse->assertSessionHasErrors(['login' => 'This magic link has already been used.']);

    // POST request
    $postResponse = $this->post('/mlogin', ['hash' => $hash]);
    $postResponse->assertRedirect(route('tyro-login.login'));
    $postResponse->assertSessionHasErrors(['login' => 'This magic link has already been used.']);
    $this->assertGuest();
});

it('rejects expired or non-existent magic links', function () {
    $getResponse = $this->get('/mlogin?hash=non-existent');
    $getResponse->assertRedirect(route('tyro-login.login'));
    $getResponse->assertSessionHasErrors(['login' => 'Invalid or expired magic link.']);

    $postResponse = $this->post('/mlogin', ['hash' => 'non-existent']);
    $postResponse->assertRedirect(route('tyro-login.login'));
    $postResponse->assertSessionHasErrors(['login' => 'Invalid or expired magic link.']);
});

it('rejects magic links when the feature is disabled', function () {
    Config::set('tyro-login.features.magic_links_enabled', false);

    $getResponse = $this->get('/mlogin?hash=any-hash');
    $getResponse->assertRedirect(route('tyro-login.login'));
    $getResponse->assertSessionHasErrors(['login' => 'Magic links are currently disabled.']);

    $postResponse = $this->post('/mlogin', ['hash' => 'any-hash']);
    $postResponse->assertRedirect(route('tyro-login.login'));
    $postResponse->assertSessionHasErrors(['login' => 'Magic links are currently disabled.']);
});

it('directly logs in when magic_link_require_confirmation is disabled', function () {
    Config::set('tyro-login.features.magic_link_require_confirmation', false);

    $user = User::forceCreate([
        'name' => 'Magic User',
        'email' => 'magic@example.com',
        'password' => Hash::make('secret'),
    ]);

    $hash = 'bypass-confirm-hash';
    Cache::put("tyro_magic_link_{$hash}", [
        'hash' => $hash,
        'user_id' => $user->id,
        'expires_at' => now()->addMinutes(10)->timestamp,
        'created_at' => now()->timestamp,
        'used' => false,
        'ip' => null,
    ], now()->addMinutes(10));

    $response = $this->get("/mlogin?hash={$hash}");

    $response->assertRedirect(config('tyro-login.redirects.after_login', '/'));
    $this->assertAuthenticatedAs($user);

    $cached = Cache::get("tyro_magic_link_{$hash}");
    expect($cached['used'])->toBeTrue();
});
