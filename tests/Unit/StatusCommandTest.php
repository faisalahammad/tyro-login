<?php

use HasinHayder\TyroLogin\Console\Commands\StatusCommand;

it('shows the status banner and feature matrix', function () {
    $this->artisan(StatusCommand::class)
        ->expectsOutputToContain('Tyro Login Status')
        ->expectsOutputToContain('Version:')
        ->expectsOutputToContain('Feature')
        ->expectsOutputToContain('Registration')
        ->expectsOutputToContain('Lockout protection')
        ->expectsOutputToContain('Health checks:')
        ->expectsOutputToContain('Layout:')
        ->expectsOutputToContain('Routes:')
        ->assertExitCode(0);
});
