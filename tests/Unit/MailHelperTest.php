<?php

use HasinHayder\TyroLogin\Helpers\MailHelper;
use HasinHayder\TyroLogin\Mail\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;

it('sends emails synchronously by default', function () {
    Mail::fake();

    MailHelper::send('user@example.com', new VerifyEmailMail(
        verificationUrl: 'https://example.com/verify',
        userName: 'Test User',
        expiresInMinutes: 60
    ));

    Mail::assertSent(VerifyEmailMail::class, 1);
    Mail::assertQueued(VerifyEmailMail::class, 0);
});

it('queues emails when the queue option is enabled', function () {
    config()->set('tyro-login.emails.queue', true);

    Mail::fake();

    MailHelper::send('user@example.com', new VerifyEmailMail(
        verificationUrl: 'https://example.com/verify',
        userName: 'Test User',
        expiresInMinutes: 60
    ));

    Mail::assertQueued(VerifyEmailMail::class, 1);
    Mail::assertSent(VerifyEmailMail::class, 0);
});
