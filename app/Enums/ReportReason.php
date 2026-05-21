<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportReason: string
{
    case Spam = 'spam';
    case Harassment = 'harassment';
    case InappropriateContent = 'inappropriate_content';
    case Copyright = 'copyright';
    case Misinformation = 'misinformation';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spam => 'Spam',
            self::Harassment => 'Harcèlement',
            self::InappropriateContent => 'Contenu inapproprié',
            self::Copyright => 'Violation de droits d\'auteur',
            self::Misinformation => 'Désinformation',
            self::Other => 'Autre',
        };
    }
}
