<?php

namespace HasinHayder\TyroLogin\Helpers;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class MailHelper {
    /**
     * Send a mailable directly or dispatch it to the queue.
     *
     * Behavior is controlled by the tyro-login.emails.queue config option
     * (default false = send synchronously).
     */
    public static function send(string $email, Mailable $mailable): void {
        $mailer = Mail::to($email);

        if (config('tyro-login.emails.queue', false)) {
            $mailer->queue($mailable);

            return;
        }

        $mailer->send($mailable);
    }
}
