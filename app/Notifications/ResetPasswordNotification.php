<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends BaseResetPassword
{
    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $resetUrl = $this->resetUrl($notifiable);
        $expires = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->subject('Réinitialisez votre mot de passe — Arborisis')
            ->view('mail.auth.reset-password', [
                'url' => $resetUrl,
                'user' => $notifiable,
                'expires' => $expires,
            ]);
    }
}
