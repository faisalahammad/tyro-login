# Two-Factor Authentication

**Tier:** 0 — Immutable
**Applies to:** `src/Http/Controllers/TwoFactorController.php`, `src/Traits/HasTwoFactorAuth.php`, `config/tyro-login.php` (two_factor section)
**Cross-references:** [security.md](security.md) (session regeneration, encrypted storage), [models-and-casts.md](models-and-casts.md) (EncryptedOrPlaintext cast, HasTwoFactorAuth trait), [controllers.md](controllers.md) (session for multi-step state)

Rules for TOTP-based 2FA setup, challenge, recovery codes, and the skip/ignore mechanisms.

---

## Store TOTP Secrets Encrypted at Rest

### Why It Matters

A TOTP secret can generate valid 6-digit codes every 30 seconds. If the database is breached and the secret is in plaintext, the attacker can generate valid 2FA codes for every user who has 2FA enabled. Encryption at rest ensures that a database breach does not compromise 2FA.

### Incorrect

```php
// Plaintext storage — database breach exposes all TOTP secrets
$user->two_factor_secret = $secret;
$user->save();
```

### Correct

```php
// Encrypted via EncryptedOrPlaintext cast — configured in the trait
trait HasTwoFactorAuth
{
    public function initializeHasTwoFactorAuth(): void
    {
        $this->mergeCasts([
            'two_factor_secret' => EncryptedOrPlaintext::class,
            'two_factor_recovery_codes' => EncryptedOrPlaintext::class,
            'two_factor_confirmed_at' => 'datetime',
        ]);
    }
}

// Storing the secret — cast encrypts automatically
$user->two_factor_secret = $secret; // Encrypted on save
```

### Notes

- Both `two_factor_secret` and `two_factor_recovery_codes` must use `EncryptedOrPlaintext`.
- The cast handles the legacy plaintext fallback for users who upgrade from a pre-encryption version.
- Never log or dump the TOTP secret or recovery codes.

---

## Session-Based Multi-Step 2FA Challenge

### Why It Matters

2FA challenge flows span multiple requests: the user logs in with credentials, is redirected to the challenge form, submits the TOTP code, and is finally authenticated. The user's identity and "remember me" preference must be stored in the session during this transition because the user is not yet fully authenticated.

### Incorrect

```php
// URL token for challenge state — leaks in logs, can be replayed
public function login(Request $request): RedirectResponse|View
{
    if ($user->hasEnabledTwoFactorAuthentication()) {
        $token = Str::random(40);
        Cache::put('2fa:' . $token, $user->id, now()->addMinutes(10));
        return redirect()->route('tyro-login.2fa.challenge', ['token' => $token]);
    }
}
```

### Correct

```php
// Session for challenge state — persists across the 2FA flow
public function login(Request $request): RedirectResponse|View
{
    if ($user->hasEnabledTwoFactorAuthentication()) {
        Auth::logout();
        $request->session()->put('login.id', $user->id);
        $request->session()->put('login.remember', $request->filled('remember'));
        return redirect()->route('tyro-login.two-factor.challenge');
    }
}

// Clear 2FA session state on challenge completion
public function verify(Request $request): RedirectResponse
{
    $userModel = config('tyro-login.user_model');
    $user = $userModel::find($request->session()->get('login.id'));

    // ... verify TOTP code ...

    Auth::login($user, $request->session()->get('login.remember', false));
    $request->session()->forget(['login.id', 'login.remember']);
    $request->session()->regenerate();
    return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
}
```

### Notes

- Session keys: `login.id` and `login.remember` — shared across 2FA challenge and 2FA setup flows (not the OTP flow, which uses `tyro-login.otp.*`).
- The user is logged out (`Auth::logout()`) before storing the session state to prevent partial authentication.
- Clear session state on: successful challenge, flow cancellation.
- Never store the TOTP code in the session — only store the user identifier.

---

## Use `pragmarx/google2fa` for TOTP Verification

### Why It Matters

TOTP is a standard algorithm (RFC 6238). Using a battle-tested library instead of implementing the algorithm from scratch eliminates implementation errors, timing attacks, and edge cases in time-window handling. `pragmarx/google2fa` is the de facto standard for TOTP in Laravel.

### Incorrect

```php
// Custom TOTP implementation — high risk of implementation bugs
public function verifyTotp(string $secret, string $code): bool
{
    return $this->customTotpVerify($secret, $code);
}
```

### Correct

```php
// Battle-tested library — correct implementation of RFC 6238
use PragmaRX\Google2FA\Google2FA;

public function verify(Request $request): RedirectResponse
{
    $userModel = config('tyro-login.user_model');
    $user = $userModel::find($request->session()->get('login.id'));

    $secretKey = $this->getTwoFactorSecret($user);

    $google2fa = new Google2FA;
    $valid = $google2fa->verifyKey($secretKey, $request->code);

    if (! $valid) {
        throw ValidationException::withMessages([
            'code' => ['The provided two factor authentication code was invalid.'],
        ]);
    }

    // Complete authentication
}
```

### Notes

- Instantiate `new Google2FA` directly — the class is imported from `PragmaRX\Google2FA\Google2FA`.
- Use `verifyKey()` for standard TOTP code verification.
- The library handles time-window drift automatically.
- For recovery codes, compare after decryption (see recovery codes rule).

---

## Generate Recovery Codes on 2FA Confirmation

### Why It Matters

Recovery codes are the user's safety net if they lose access to their authenticator app. They must be generated at the moment of 2FA setup confirmation (not before) because the setup can be abandoned. Each code must be single-use and stored hashed, not in plaintext.

### Incorrect

```php
// Recovery codes generated on setup page load — wasted if setup abandoned
public function showSetup(): View
{
    $codes = $this->generateRecoveryCodes();
    $user->two_factor_recovery_codes = encrypt(json_encode($codes));
    $user->save(); // Saved before 2FA is confirmed
}
```

### Correct

```php
// Recovery codes generated only after TOTP code is verified
public function confirm(Request $request): View|RedirectResponse
{
    $request->validate(['code' => ['required', 'string', 'size:6']]);

    $user = $this->getUserFromSession();

    if (! Google2FA::verifyKey($this->getTwoFactorSecret(), $request->input('code'))) {
        return back()->withErrors(['code' => __('Invalid code.')]);
    }

    // 2FA confirmed — now generate and store recovery codes
    $codes = $this->generateRecoveryCodes();
    $user->two_factor_recovery_codes = encrypt(json_encode($codes));
    $user->two_factor_confirmed_at = now();
    $user->save();

    // Render the codes as the direct response to this POST. Never redirect to
    // a separate GET recovery-codes page — that page would be directly
    // accessible (and would expose codes even when 2FA is disabled).
    return view('tyro-login::two-factor-recovery-codes', ['recoveryCodes' => $codes]);
}

protected function generateRecoveryCodes(): array
{
    $codes = [];
    for ($i = 0; $i < 8; $i++) {
        $codes[] = strtoupper(Str::random(10));
    }
    return $codes;
}
```

### Notes

- Generate 8 recovery codes by convention — this is the industry standard.
- Use `Str::random(10)` for code generation — these are long enough to be unguessable.
- Recovery codes are shown only once after setup — the user is responsible for saving them.
- Recovery codes are stored encrypted as a single JSON array.
- Never expose recovery codes through a standalone GET route: gate them behind the successful TOTP confirmation POST and abort with a 404 when `tyro-login.two_factor.enabled` is false. If the user's 2FA is already confirmed, redirect to the post-login destination instead of re-rendering or regenerating the codes.

---

## Config-Driven Forced 2FA for Specific Roles

### Why It Matters

Organizations often require 2FA for administrative roles (admins, super-admins) while allowing standard users to skip it. The forced roles list must be config-driven so that the consuming application can define which roles must use 2FA without overriding controllers.

### Incorrect

```php
// Hardcoded role check — consumer cannot customize
public function userHasForcedRole($user): bool
{
    return $user->hasRole('admin') || $user->hasRole('super-admin');
}
```

### Correct

```php
// Config-driven role check — consumer defines forced roles in config
public function userHasForcedRole($user): bool
{
    $forcedRoles = config('tyro-login.two_factor.forced_roles', []);

    if (empty($forcedRoles) || ! method_exists($user, 'hasRole')) {
        return false;
    }

    foreach ($forcedRoles as $role) {
        if ($user->hasRole($role)) {
            return true;
        }
    }

    return false;
}
```

### Notes

- The config defaults to an empty array (no forced roles).
- Use `method_exists($user, 'hasRole')` for soft integration with role packages.
- When 2FA is forced, the skip and ignore options must be hidden from the UI.
- All role checks must go through `HasinHayder\TyroLogin\Helpers\TwoFactorHelper` — never re-implement the hasRole()/role-attribute matching inline in controllers.

---

## Config-Driven Skip 2FA Roles with Forced-Role Precedence

### Why It Matters

Sites often want 2FA mandatory for admins while never nagging regular users with the setup prompt. `tyro-login.two_factor.skip_roles` (comma-separated) lists roles that are never prompted to set up 2FA — after registration, on login, magic link login, social login, or by visiting the setup page directly. `forced_roles` is the security boundary and must always win: a role listed in both `forced_roles` and `skip_roles` is still required to set up 2FA, so a misconfigured skip list can never weaken a forced requirement.

### Incorrect

```php
// skip_roles checked without forced-role precedence — weakens security
if (TwoFactorHelper::userHasSkipRole($user)) {
    return redirect()->intended(config('tyro-login.redirects.after_login', '/'));
}
```

### Correct

```php
// forced_roles takes precedence inside userShouldSkipTwoFactorSetup()
if (config('tyro-login.two_factor.enabled', false)
    && ! TwoFactorHelper::userShouldSkipTwoFactorSetup($user)) {
    return redirect()->route('tyro-login.two-factor.setup');
}

// TwoFactorHelper::userShouldSkipTwoFactorSetup() returns true only when
// the user has a skip_roles role AND no forced_roles role.
```

### Notes

- Config: `tyro-login.two_factor.skip_roles` (`TYRO_LOGIN_2FA_SKIP_ROLES`, e.g. `"user,editor"`), default empty.
- Skip-role suppression applies to the setup prompt only — users who already confirmed 2FA still receive the challenge.
- Independent of `allow_skip` and the ignore cookie: role-based skipping works even when per-user skipping is disallowed.
- Apply the check at every setup-prompt site: registration auto-login, password login, magic link login, and social `handlePostLoginRedirect()`. In `TwoFactorController::showSetup()` the skip applies only to the mid-login flow (user resolved from `login.id` and not authenticated) — an authenticated skip-role user must still be able to open the setup page to enroll voluntarily.
- Never skip the challenge for a confirmed user anywhere: every 2FA branch must test `filled($user->two_factor_confirmed_at)` before any skip/ignore/skip-role logic, including the `skip()` and `ignore()` endpoints (they must redirect a confirmed mid-challenge user back to the challenge rather than log them in).

---

## Dual-Cast-Aware Secret Access Methods

### Why It Matters

The user model may or may not use the `HasTwoFactorAuth` trait (which adds the `EncryptedOrPlaintext` cast). The `TwoFactorController` must handle both cases: when the cast handles encryption automatically (via the trait) and when it must encrypt manually (no trait). The `hasCasts()` helper determines which approach to use.

### Incorrect

```php
// Assumes cast is always present — crashes if user model doesn't have the trait
protected function getTwoFactorSecret($user): ?string
{
    return Crypt::decryptString($user->two_factor_secret);
}
```

### Correct

```php
// Dual-cast-aware — handles both trait and non-trait user models
protected function getTwoFactorSecret($user): ?string
{
    if ($this->hasCasts($user, 'two_factor_secret')) {
        return $user->two_factor_secret;
    }

    try {
        return $user->two_factor_secret ? Crypt::decryptString($user->two_factor_secret) : null;
    } catch (\Exception $e) {
        try {
            return decrypt($user->two_factor_secret);
        } catch (\Exception $e2) {
            return $user->two_factor_secret;
        }
    }
}

protected function saveTwoFactorSecret($user, string $secret): void
{
    if ($this->hasCasts($user, 'two_factor_secret')) {
        $user->forceFill(['two_factor_secret' => $secret])->save();
        return;
    }

    $user->forceFill(['two_factor_secret' => Crypt::encryptString($secret)])->save();
}

protected function hasCasts($user, string $key): bool
{
    $casts = $user->getCasts();
    if (! array_key_exists($key, $casts)) {
        return false;
    }

    return str_contains($casts[$key], 'EncryptedOrPlaintext');
}
```

### Notes

- `hasCasts()` checks if the `EncryptedOrPlaintext` cast is active on the given attribute.
- When the cast is present, read/write the attribute directly — the cast handles encryption.
- When the cast is absent, manually encrypt/decrypt using `Crypt`.
- The fallback chain (`Crypt::decryptString` → `decrypt()` → raw value) handles legacy data formats.
- Apply the same pattern to recovery codes via `hasCasts($user, 'two_factor_recovery_codes')`.

---

## Cookie-Based 2FA Ignore with Configurable Duration

### Why It Matters

Some users may want to defer 2FA setup. An ignore cookie allows them to skip setup for a configurable number of days. The cookie is tied to the user ID so it cannot be transferred between users. This is opt-in — forced roles bypass the ignore mechanism entirely.

### Incorrect

```php
// Cookie with no user binding — can be transferred between browsers
public function ignore(Request $request): RedirectResponse
{
    return redirect()->route('tyro-login.dashboard')
        ->cookie('tyro_2fa_ignore', 'true', 60 * 24 * 30);
}
```

### Correct

```php
// User-bound cookie — cannot be transferred, configurable duration
public function ignore(Request $request): RedirectResponse
{
    $days = (int) config('tyro-login.two_factor.ignore_days', 30);

    return redirect()->route('tyro-login.dashboard')
        ->cookie(
            'tyro_2fa_ignore_' . $request->user()->getKey(),
            'true',
            now()->addDays($days)->diffInMinutes()
        );
}

// Check the cookie during login 2FA challenge
protected function userIgnored2FA($user): bool
{
    if ($this->userHasForcedRole($user)) {
        return false; // Forced roles cannot ignore
    }

    return (bool) request()->cookie('tyro_2fa_ignore_' . $user->getKey());
}
```

### Notes

- The cookie name includes the user ID: `tyro_2fa_ignore_{id}`.
- Forced roles bypass the ignore check entirely.
- The ignore duration is config-driven via `tyro-login.two_factor.ignore_days`.
