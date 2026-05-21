<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscordSetting extends Model
{
    protected $fillable = [
        'guild_id',
        'notification_channel_id',
        'welcome_channel_id',
        'radio_channel_id',
        'moderation_channel_id',
        'bot_enabled',
        'internal_api_token',
    ];

    protected $casts = [
        'bot_enabled' => 'boolean',
    ];

    /**
     * Récupère l'unique enregistrement de configuration (singleton-like).
     */
    public static function getSettings(): self
    {
        $settings = self::first();

        if (! $settings) {
            $settings = self::create([
                'bot_enabled' => false,
            ]);
        }

        return $settings;
    }

    /**
     * Met à jour les paramètres en s'assurant qu'il n'y a qu'une seule ligne.
     */
    public static function updateSettings(array $data): self
    {
        $settings = self::getSettings();
        $settings->update($data);

        return $settings->fresh();
    }
}
