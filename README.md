# Tyro Login

[![Packagist](https://img.shields.io/packagist/v/hasinhayder/tyro-login?style=for-the-badge&logo=packagist&logoColor=white&label=Packagist)](https://packagist.org/packages/hasinhayder/tyro-login) [![Tests](https://img.shields.io/github/actions/workflow/status/hasinhayder/tyro-login/tests.yml?style=for-the-badge&label=Tests)](https://github.com/hasinhayder/tyro-login/actions/workflows/tests.yml) [![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com) [![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel)](https://laravel.com) [![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)](https://php.net) [![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE) [![CLI Ready](https://img.shields.io/badge/CLI-Ready-2EA44F?style=for-the-badge&logo=terminal&logoColor=white)](https://github.com/hasinhayder/tyro-login)

<p align="center">
<a href="https://hasinhayder.github.io/tyro-login/">Website</a> |
<a href="https://hasinhayder.github.io/tyro-login/doc.html">Documentation</a> |
<a href="https://github.com/hasinhayder/tyro-login">GitHub</a>
</p>

**Tyro Login** is a beautiful, production-ready authentication UI kit for Laravel 12 and 13. It gives you professional login and registration pages with 10 premium layouts, social OAuth, passkeys, TOTP 2FA, email OTP, magic links, invitations, lockout protection, captcha, and beautiful emails, all with zero build step and seamless integration with the [Tyro](https://github.com/hasinhayder/tyro) package.

## Features

| Feature | Description |
| --- | --- |
| **10 Premium Layouts** | centered, split-left, split-right, fullscreen, card, youtube-video, animated-birds, aurora-waves, particle-network, tidal |
| **Social Login** | Google, Facebook, GitHub, Twitter/X, LinkedIn, Bitbucket, GitLab, Slack via Laravel Socialite |
| **Passkeys** | Passwordless WebAuthn sign-in (Face ID, Touch ID, security keys) via `laravel/passkeys` |
| **TOTP 2FA** | Time-based one-time passwords compatible with Google Authenticator, Authy, Microsoft Authenticator |
| **Email OTP** | Two-factor login via email verification codes with resend cooldown |
| **Magic Links** | Passwordless email login links |
| **Invitation/Referral System** | Per-user invitation links with automatic signup tracking |
| **Email Verification** | Optional verification with signed, expiring links |
| **Password Reset** | Full forgot/reset flow with beautiful UI and emails |
| **Lockout Protection** | Cache-based brute-force protection with configurable attempts and duration |
| **Math Captcha** | Simple addition/subtraction captcha on login and registration |
| **Beautiful Emails** | Sleek HTML templates for OTP, reset, verification, welcome, and magic links |
| **Dark/Light Theme** | Automatic theme detection with manual toggle |
| **Tyro Integration** | Automatic role assignment for new users when Tyro is installed |
| **Zero Build Step** | No npm or webpack, just install and use |

## Requirements

- PHP 8.2 or higher
- Laravel 12.0 or higher

## Installation

```bash
composer require hasinhayder/tyro-login
php artisan tyro-login:install
```

That's it! Visit `/login` to see your new authentication pages.

Want more from the start?

```bash
# With social login (Laravel Socialite)
php artisan tyro-login:install --with-social

# With passkey (WebAuthn) login
php artisan tyro-login:install --with-passkeys
```

> **Updating to 2.3.0+?** Run `php artisan migrate` to set up the invitation/referral system.

## Quick tour

### Layouts

10 layouts are one env var away:

```env
TYRO_LOGIN_LAYOUT=centered        # or: split-left, split-right, fullscreen, card,
                                  #     youtube-video, animated-birds, aurora-waves,
                                  #     particle-network, tidal
TYRO_LOGIN_BACKGROUND_IMAGE=https://your-app.com/bg.jpg   # for split/fullscreen layouts
```

### Email OTP (two-factor via email)

```env
TYRO_LOGIN_OTP_ENABLED=true
TYRO_LOGIN_OTP_LENGTH=6
TYRO_LOGIN_OTP_EXPIRE=5
```

### TOTP 2FA (authenticator apps)

```env
TYRO_LOGIN_2FA_ENABLED=true
TYRO_LOGIN_2FA_ALLOW_SKIP=false   # force setup after login
TYRO_LOGIN_2FA_FORCED_ROLES=admin,superadmin
```

### Social login

```env
TYRO_LOGIN_SOCIAL_ENABLED=true
TYRO_LOGIN_SOCIAL_GOOGLE=true
TYRO_LOGIN_SOCIAL_GITHUB=true
# add client credentials to config/services.php
```

### Passkeys (passwordless WebAuthn)

```bash
php artisan tyro-login:setup-passkeys
```

Add the `PasskeyAuthenticatable` trait and `PasskeyUser` contract to your User model, then enable:

```env
TYRO_LOGIN_PASSKEYS_ENABLED=true
```

### Magic links

```env
TYRO_LOGIN_ENABLE_MAGIC_LINKS=true
```

### Lockout protection

```env
TYRO_LOGIN_LOCKOUT_MAX_ATTEMPTS=5
TYRO_LOGIN_LOCKOUT_DURATION=15
```

## Configuration

All options are env-configurable. Publish the config to customize further:

```bash
php artisan tyro-login:publish --config
```

| Env var | Default | Description |
| --- | --- | --- |
| `TYRO_LOGIN_LAYOUT` | `centered` | Auth page layout (10 options) |
| `TYRO_LOGIN_ROUTE_PREFIX` | `''` | Prefix for all auth routes |
| `TYRO_LOGIN_USER_MODEL` | `App\Models\User` | User model Tyro Login operates on |
| `TYRO_LOGIN_REGISTRATION_ENABLED` | `true` | Enable or disable registration |
| `TYRO_LOGIN_REGISTRATION_AUTO_LOGIN` | `true` | Log in users automatically after registration |
| `TYRO_LOGIN_REQUIRE_EMAIL_VERIFICATION` | `false` | Require email verification after registration |
| `TYRO_LOGIN_VERIFICATION_EXPIRE` | `60` | Email verification token expiry (minutes) |
| `TYRO_LOGIN_PASSWORD_RESET_EXPIRE` | `60` | Password reset token expiry (minutes) |
| `TYRO_LOGIN_REDIRECT_AFTER_LOGIN` | `/` | Redirect after successful login |
| `TYRO_LOGIN_REDIRECT_AFTER_LOGOUT` | `/login` | Redirect after logout |
| `TYRO_LOGIN_REDIRECT_AFTER_REGISTER` | `/` | Redirect after registration |
| `TYRO_LOGIN_REDIRECT_AFTER_EMAIL_VERIFICATION` | `/login` | Redirect after email verification |
| `TYRO_LOGIN_OTP_ENABLED` | `false` | Enable email OTP login |
| `TYRO_LOGIN_OTP_LENGTH` | `4` | OTP length (4-8 digits) |
| `TYRO_LOGIN_OTP_EXPIRE` | `5` | OTP expiry (minutes) |
| `TYRO_LOGIN_OTP_MAX_RESEND` | `3` | Maximum OTP resends |
| `TYRO_LOGIN_OTP_RESEND_COOLDOWN` | `60` | Seconds between resends |
| `TYRO_LOGIN_2FA_ENABLED` | `false` | Enable TOTP two-factor authentication |
| `TYRO_LOGIN_2FA_ALLOW_SKIP` | `false` | Allow users to skip 2FA setup |
| `TYRO_LOGIN_2FA_IGNORE_COOKIE_DAYS` | `30` | Days the skip-and-ignore cookie lasts |
| `TYRO_LOGIN_2FA_FORCED_ROLES` | `''` | Comma-separated roles that must set up 2FA |
| `TYRO_LOGIN_PASSKEYS_ENABLED` | `false` | Enable passkey (WebAuthn) login |
| `TYRO_LOGIN_PASSKEYS_CDN` | `esm.sh/@laravel/passkeys` | Browser client URL (override to self-host) |
| `TYRO_LOGIN_ENABLE_MAGIC_LINKS` | `false` | Enable magic link login |
| `TYRO_LOGIN_MAGIC_LINK_EXPIRE` | `5` | Magic link expiry (minutes) |
| `TYRO_LOGIN_DISABLE_PASSWORD` | `false` | Disable password login entirely |
| `TYRO_LOGIN_REMEMBER_ME` | `true` | Show remember-me checkbox |
| `TYRO_LOGIN_FORGOT_PASSWORD` | `true` | Show forgot-password link |
| `TYRO_LOGIN_LOGIN_FIELD` | `email` | Login field: `email`, `username`, or `both` |
| `TYRO_LOGIN_CAPTCHA_LOGIN` | `false` | Enable math captcha on login |
| `TYRO_LOGIN_CAPTCHA_REGISTER` | `false` | Enable math captcha on registration |
| `TYRO_LOGIN_LOCKOUT_ENABLED` | `true` | Enable lockout protection |
| `TYRO_LOGIN_LOCKOUT_MAX_ATTEMPTS` | `5` | Failed attempts before lockout |
| `TYRO_LOGIN_LOCKOUT_DURATION` | `15` | Lockout duration (minutes) |
| `TYRO_LOGIN_SOCIAL_ENABLED` | `false` | Enable social login |
| `TYRO_LOGIN_SOCIAL_GOOGLE` | `false` | Enable Google provider |
| `TYRO_LOGIN_SOCIAL_FACEBOOK` | `false` | Enable Facebook provider |
| `TYRO_LOGIN_SOCIAL_GITHUB` | `false` | Enable GitHub provider |
| `TYRO_LOGIN_SOCIAL_TWITTER` | `false` | Enable Twitter/X provider |
| `TYRO_LOGIN_SOCIAL_LINKEDIN` | `false` | Enable LinkedIn provider |
| `TYRO_LOGIN_SOCIAL_BITBUCKET` | `false` | Enable Bitbucket provider |
| `TYRO_LOGIN_SOCIAL_GITLAB` | `false` | Enable GitLab provider |
| `TYRO_LOGIN_SOCIAL_SLACK` | `false` | Enable Slack provider |
| `TYRO_LOGIN_SOCIAL_LINK_EXISTING` | `true` | Link social accounts to existing users by email |
| `TYRO_LOGIN_SOCIAL_AUTO_REGISTER` | `true` | Auto-create users from social login |
| `TYRO_LOGIN_SOCIAL_AUTO_VERIFY_EMAIL` | `true` | Auto-verify email after social login |
| `TYRO_LOGIN_ASSIGN_DEFAULT_ROLE` | `true` | Assign default role to new users (Tyro integration) |
| `TYRO_LOGIN_DEFAULT_ROLE_SLUG` | `user` | Default role slug for new users |
| `TYRO_LOGIN_PASSWORD_MIN_LENGTH` | `8` | Minimum password length |
| `TYRO_LOGIN_PASSWORD_MAX_LENGTH` | `null` | Maximum password length |
| `TYRO_LOGIN_PASSWORD_REQUIRE_CONFIRMATION` | `true` | Require password confirmation |
| `TYRO_LOGIN_PASSWORD_REQUIRE_UPPERCASE` | `false` | Require an uppercase letter |
| `TYRO_LOGIN_PASSWORD_REQUIRE_LOWERCASE` | `false` | Require a lowercase letter |
| `TYRO_LOGIN_PASSWORD_REQUIRE_NUMBERS` | `false` | Require a number |
| `TYRO_LOGIN_PASSWORD_REQUIRE_SPECIAL_CHARS` | `false` | Require a special character |
| `TYRO_LOGIN_PASSWORD_CHECK_COMMON` | `false` | Block common or compromised passwords |
| `TYRO_LOGIN_PASSWORD_DISALLOW_USER_INFO` | `false` | Reject passwords containing user info |
| `TYRO_LOGIN_DEBUG` | `false` | Privacy-safe debug logging (never enable in production) |
| `TYRO_LOGIN_APP_NAME` | `Laravel` | App name shown on auth pages |
| `TYRO_LOGIN_LOGO` | `null` | Logo URL (null uses text logo) |
| `TYRO_LOGIN_LOGO_DARK` | `null` | Dark-mode logo URL |
| `TYRO_LOGIN_LOGO_HEIGHT` | `48px` | Logo display height |
| `TYRO_LOGIN_LOGO_BORDER_RADIUS` | `0` | Logo corner radius (e.g. `50%`) |
| `TYRO_LOGIN_BACKGROUND_IMAGE` | Unsplash default | Background image for split/fullscreen layouts |
| `TYRO_LOGIN_VIDEO_URL` | default video | YouTube URL or ID for the video layout |
| `TYRO_LOGIN_VIDEO_BLUR` | `0px` | Video background blur |
| `TYRO_LOGIN_VIDEO_OVERLAY_COLOR` | `#111827` | Video overlay color |
| `TYRO_LOGIN_VIDEO_OVERLAY_OPACITY` | `0.1` | Video overlay opacity |
| `TYRO_LOGIN_VIDEO_SOUND` | `false` | Play video with sound |
| `TYRO_LOGIN_EMAIL_OTP` | `true` | Send OTP email |
| `TYRO_LOGIN_EMAIL_PASSWORD_RESET` | `true` | Send password reset email |
| `TYRO_LOGIN_EMAIL_VERIFY` | `true` | Send email verification email |
| `TYRO_LOGIN_EMAIL_WELCOME` | `true` | Send welcome email |
| `TYRO_LOGIN_EMAIL_MAGIC_LINK` | `true` | Send magic link email |

## CLI at a glance

| Command | Description |
| --- | --- |
| `tyro-login:install` | Install the package and publish configuration |
| `tyro-login:install --with-social` | Install with social login support |
| `tyro-login:install --with-passkeys` | Install with passkey (WebAuthn) support |
| `tyro-login:setup-passkeys` | Enable passkeys on an existing installation |
| `tyro-login:publish` | Publish config, views, email templates, and assets |
| `tyro-login:publish-style` | Publish styles (theme + components) |
| `tyro-login:publish-style --theme-only` | Publish only theme variables |
| `tyro-login:update-style` | Update published styles |
| `tyro-login:update-config` | Sync the latest config values |
| `tyro-login:status` | Show configuration and feature status |
| `tyro-login:verify-user` | Verify a user's email (by ID/email or `--all`) |
| `tyro-login:unverify-user` | Remove email verification (by ID/email or `--all`) |
| `tyro-login:reset-2fa` | Reset a user's 2FA (by ID or email) |
| `tyro-login:invite-links` | Create, list, remove, or flush invitation links |
| `tyro-login:magic-links` | Create, list, remove, or flush magic links |
| `tyro-login:version` | Display the installed version |
| `tyro-login:doc` | Open the documentation |
| `tyro-login:star` | Open GitHub to star the repository |
| `tyro-login:setup-ai-skill` | Install the Tyro AI skill for your agent |

Run `php artisan list tyro-login` to see every available command.

## Customization

Publish views, email templates, or everything:

```bash
php artisan tyro-login:publish --views
php artisan tyro-login:publish --emails
php artisan tyro-login:publish
```

Theme via shadcn CSS variables (publish-only-theme workflow):

```bash
php artisan tyro-login:publish-style --theme-only
```

Edit `resources/views/vendor/tyro-login/partials/shadcn-theme.blade.php`, or use the visual editor at [tweakcn.com](https://tweakcn.com).

## Security

Tyro Login ships with industry-standard security out of the box:

- **Encrypted storage** for OAuth tokens at rest (Laravel encryption)
- **Cryptographically secure** OTP generation (`random_int`)
- **Session regeneration** on login and logout to prevent fixation attacks
- **CSRF-protected** forms and logout (POST only)
- **Lockout protection** against brute-force attempts (cache-based)
- **Signed, expiring URLs** for email verification and password reset
- **Privacy-safe debug logging** with masked email addresses

## Integration with Tyro

Tyro Login integrates seamlessly with the [Tyro](https://github.com/hasinhayder/tyro) package: when a new user registers, Tyro Login can automatically assign a default role. Ensure your User model uses the `HasTyroRoles` trait:

```php
use HasinHayder\Tyro\Concerns\HasTyroRoles;

class User extends Authenticatable
{
    use HasTyroRoles;
}
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email hasin@hasin.me instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Hasin Hayder](https://github.com/hasinhayder)

---

<p align="center">
Made with love for the Laravel community by Hasin Hayder
</p>
