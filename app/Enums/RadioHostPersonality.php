<?php

declare(strict_types=1);

namespace App\Enums;

enum RadioHostPersonality: string
{
    case Auto = 'auto';
    case SolenePoete = 'solene_poete';
    case MarcNaturaliste = 'marc_naturaliste';
    case LeaJournaliste = 'lea_journaliste';
    case ArtoDocumentariste = 'arto_documentariste';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Sélection automatique',
            self::SolenePoete => 'Solène — la poète',
            self::MarcNaturaliste => 'Marc — le naturaliste',
            self::LeaJournaliste => 'Léa — la journaliste',
            self::ArtoDocumentariste => 'Arto — le documentariste',
        };
    }

    /**
     * @return array<int, self>
     */
    public static function selectable(): array
    {
        return [
            self::SolenePoete,
            self::MarcNaturaliste,
            self::LeaJournaliste,
            self::ArtoDocumentariste,
        ];
    }
}
