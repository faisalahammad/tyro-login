<?php

namespace HasinHayder\TyroLogin\Console\Commands;

use HasinHayder\TyroLogin\Events\ForceLogout;
use Illuminate\Console\Command;

class LogoutCommand extends Command {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tyro-login:logout
                            {identifier : User ID or email address to force logout}';

    /**
     * The console command description.
     */
    protected $description = 'Force a user logout on their next request';

    /**
     * Execute the console command.
     */
    public function handle(): int {
        if (! config('tyro-login.force_logout.enabled', true)) {
            $this->error('Force logout is currently disabled in the configuration.');
            $this->info('To enable, set TYRO_LOGIN_FORCE_LOGOUT_ENABLED=true in your .env file.');

            return self::FAILURE;
        }

        $identifier = $this->argument('identifier');
        $userModel = config('tyro-login.user_model', 'App\\Models\\User');

        if (! class_exists($userModel)) {
            $this->error("User model '{$userModel}' not found.");

            return self::FAILURE;
        }

        $user = is_numeric($identifier)
            ? $userModel::find((int) $identifier)
            : $userModel::where('email', $identifier)->first();

        if (! $user) {
            $this->error("✗ User not found: {$identifier}");

            return self::FAILURE;
        }

        event(new ForceLogout((int) $user->getKey()));

        $this->info("✓ User '{$user->email}' (ID: {$user->getKey()}) will be logged out on their next request.");

        return self::SUCCESS;
    }
}
