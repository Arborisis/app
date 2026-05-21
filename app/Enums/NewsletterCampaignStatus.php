<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsletterCampaignStatus: string
{
    case Draft = 'draft';
    case Sending = 'sending';
    case Sent = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Sending => 'Envoi en cours',
            self::Sent => 'Envoyée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sending => 'warning',
            self::Sent => 'success',
        };
    }
}
