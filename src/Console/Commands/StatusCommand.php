<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use Composer\InstalledVersions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class StatusCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:status';

    /**
     * The console command description.
     */
    protected $description = 'Display the configuration and feature status of Tyro Login';

    /**
     * Fallback version when the package is not installed via Composer.
     */
    protected const FALLBACK_VERSION = '2.12.0';

    /**
     * Execute the console command.
     */
    public function handle(): int {
        $configPublished = file_exists(config_path('tyro-login.php'));

        $this->info('');
        $this->info('  ╔════════════════════════════════════════╗');
        $this->info('  ║                                        ║');
        $this->info('  ║        Tyro Login Status               ║');
        $this->info('  ║                                        ║');
        $this->info('  ╚════════════════════════════════════════╝');
        $this->info('');

        if (! $configPublished) {
            $this->warn('  ⚠ Config not published — showing package defaults.');
            $this->line('    Run: php artisan tyro-login:publish --config to customize.');
            $this->line('');
        }

        $this->versionInfo();
        $this->featureMatrix();
        $this->healthChecks();
        $this->layoutInfo();
        $this->routeInfo();

        $this->info('');

        return self::SUCCESS;
    }

    /**
     * Display version information.
     */
    protected function versionInfo(): void {
        $version = InstalledVersions::isInstalled('hasinhayder/tyro-login')
            ? InstalledVersions::getPrettyVersion('hasinhayder/tyro-login')
            : self::FALLBACK_VERSION;

        $this->info('  Version: <comment>'.($version ?? self::FALLBACK_VERSION).'</comment>');
        $this->info('  Laravel: <comment>'.app()->version().'</comment>');
        $this->info('  PHP: <comment>'.PHP_VERSION.'</comment>');
        $this->info('');
    }

    /**
     * Display the feature matrix table.
     */
    protected function featureMatrix(): void {
        $features = [
            ['Registration', $this->enabled(config('tyro-login.registration.enabled', true))],
            ['  auto-login after register', $this->enabled(config('tyro-login.registration.auto_login', true))],
            ['  require email verification', $this->enabled(config('tyro-login.registration.require_email_verification', false))],
            ['Email verification', $this->enabled(config('tyro-login.emails.verify_email.enabled', true))],
            ['Forgot password', $this->enabled(config('tyro-login.features.forgot_password', true))],
            ['Magic links', $this->enabled(config('tyro-login.features.magic_links_enabled', false))],
            ['Passkeys (WebAuthn)', $this->enabled(config('tyro-login.passkeys.enabled', false))],
            ['OTP login', $this->enabled(config('tyro-login.otp.enabled', false))],
            ['Two-factor auth (TOTP)', $this->enabled(config('tyro-login.two_factor.enabled', false))],
            ['Captcha', $this->enabled(config('tyro-login.captcha.enabled_login', false) || config('tyro-login.captcha.enabled_register', false))],
            ['Social login', $this->enabled(config('tyro-login.social.enabled', false))],
            ['Lockout protection', $this->enabled(config('tyro-login.lockout.enabled', true))],
        ];

        $this->table(['Feature', 'Status'], $features);
        $this->info('');
    }

    /**
     * Display configuration health checks.
     */
    protected function healthChecks(): void {
        $this->info('  Health checks:');
        $this->info('');

        $checks = [
            ['Config file published', $this->configPublishedCheck()],
            ['APP_KEY set', $this->appKeyCheck()],
            ['Cache driver', $this->cacheDriverCheck()],
            ['User model resolvable', $this->userModelCheck()],
            ['Socialite installed', $this->socialiteCheck()],
            ['laravel/passkeys installed', $this->passkeysPackageCheck()],
        ];

        foreach ($checks as [$label, $result]) {
            $this->line('  '.$result.' '.$label);
        }

        $this->info('');
    }

    /**
     * Display the active layout.
     */
    protected function layoutInfo(): void {
        $layout = config('tyro-login.layout', 'centered');

        $this->info('  Layout: <comment>'.$layout.'</comment>');

        $this->info('  Available layouts:');
        $this->info('  - centered, split-left, split-right, fullscreen, card');
        $this->info('  - youtube-video, animated-birds, aurora-waves, particle-network, tidal');
        $this->info('');
    }

    /**
     * Display registered route information.
     */
    protected function routeInfo(): void {
        $prefix = config('tyro-login.routes.prefix', '');
        $login = config('tyro-login.routes.login', 'login');
        $register = config('tyro-login.routes.register', 'register');

        $loginPath = trim($prefix.'/'.$login, '/') ?: $login;
        $registerPath = trim($prefix.'/'.$register, '/') ?: $register;

        $this->info('  Routes:');
        $this->info('  - Login: <comment>/'.$loginPath.'</comment>');
        $this->info('  - Register: <comment>/'.$registerPath.'</comment>');

        $this->line('');

        if (\Illuminate\Support\Facades\Route::has('tyro-login.login')) {
            $this->info('  ✓ tyro-login routes are registered');
        } else {
            $this->warn('  ✗ tyro-login routes are NOT registered. Check that the service provider is loaded.');
        }
    }

    /**
     * Return an enabled/disabled badge.
     */
    protected function enabled(mixed $value): string {
        return $value ? '<info>✓ enabled</info>' : '<fg=gray>✗ disabled</fg=gray>';
    }

    /**
     * Check whether the config file has been published.
     */
    protected function configPublishedCheck(): string {
        return file_exists(config_path('tyro-login.php'))
            ? '<info>✓ published</info>'
            : '<comment>⚠ not published (using package defaults)</comment>';
    }

    /**
     * Check whether the application key is set.
     */
    protected function appKeyCheck(): string {
        return config('app.key')
            ? '<info>✓ set</info>'
            : '<fg=red>✗ missing — sessions and encryption will not work</fg=red>';
    }

    /**
     * Check the configured cache driver.
     */
    protected function cacheDriverCheck(): string {
        $driver = config('cache.default', 'file');

        try {
            Cache::store($driver)->put('tyro-login:status-check', true, 1);
            Cache::store($driver)->forget('tyro-login:status-check');

            return '<info>✓ '.$driver.' (read/write ok)</info>';
        } catch (\Throwable $e) {
            return '<fg=red>✗ '.$driver.' (write failed: '.$e->getMessage().')</fg=red>';
        }
    }

    /**
     * Check whether the configured user model resolves.
     */
    protected function userModelCheck(): string {
        $model = config('tyro-login.user_model', 'App\\Models\\User');

        return class_exists($model)
            ? '<info>✓ '.$model.'</info>'
            : '<fg=red>✗ '.$model.' not found — check TYRO_LOGIN_USER_MODEL</fg=red>';
    }

    /**
     * Check whether Laravel Socialite is installed.
     */
    protected function socialiteCheck(): string {
        return class_exists(\Laravel\Socialite\SocialiteServiceProvider::class)
            ? '<info>✓ installed</info>'
            : '<comment>⚠ not installed (required only if social login is enabled)</comment>';
    }

    /**
     * Check whether laravel/passkeys is installed.
     */
    protected function passkeysPackageCheck(): string {
        return class_exists(\Laravel\Passkeys\Passkeys::class)
            ? '<info>✓ installed</info>'
            : '<comment>⚠ not installed (required only if passkeys are enabled)</comment>';
    }
}
