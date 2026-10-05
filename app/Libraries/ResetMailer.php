<?php

namespace App\Libraries;

use Config\Email;

/**
 * Sends the "reset your password" e-mail through an SMTP server (for example Gmail).
 *
 * The SMTP settings are read from .env (email.SMTPHost, email.SMTPUser, ...).
 * When they are not set, nothing is sent and the controller shows the link on
 * the screen instead (development mode only).
 */
class ResetMailer
{
    public function isConfigured(): bool
    {
        $config = config(Email::class);

        return $config->protocol === 'smtp' && $config->SMTPHost !== '' && $config->fromEmail !== '';
    }

    /** Returns true when the e-mail was handed to the SMTP server. */
    public function send(array $user, string $link): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        $config = config(Email::class);
        $email  = service('email');

        $email->setFrom($config->fromEmail, $config->fromName !== '' ? $config->fromName : 'PawRecord');
        $email->setTo($user['email']);
        $email->setSubject('Reset your PawRecord password');
        $email->setMailType('html');
        $email->setMessage(view('emails/password_reset', ['name' => $user['full_name'], 'link' => $link]));
        $email->setAltMessage(
            "Hi {$user['full_name']},\n\nOpen this link to choose a new PawRecord password (it works for 1 hour):\n{$link}\n\n"
            . "If you did not ask for this, you can ignore this e-mail. Your password stays the same.\n",
        );

        if (! $email->send(false)) {
            // Only the server's replies are logged, never the e-mail body (it contains the secret link)
            log_message('error', 'Password reset e-mail could not be sent: ' . strip_tags($email->printDebugger([])));

            return false;
        }

        return true;
    }
}
