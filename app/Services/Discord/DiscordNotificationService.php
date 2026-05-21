<?php

declare(strict_types=1);

namespace App\Services\Discord;

use App\Events\DiscordNotification;
use App\Models\DiscordSetting;
use App\Models\EchoDonation;
use App\Models\Report;
use App\Models\Sound;
use App\Models\User;

class DiscordNotificationService
{
    public function notifySoundPublished(Sound $sound): void
    {
        $settings = DiscordSetting::getSettings();

        if (! $settings->bot_enabled || empty($settings->notification_channel_id)) {
            return;
        }

        event(new DiscordNotification(
            channelId: $settings->notification_channel_id,
            embed: [
                'title' => "🌿 Nouvel enregistrement : {$sound->title}",
                'description' => $sound->description ?? 'Aucune description',
                'url' => route('sounds.show', $sound->slug, absolute: true),
                'color' => 0x2D5A3D,
                'fields' => [
                    ['name' => 'Créateur', 'value' => $sound->user->name, 'inline' => true],
                    ['name' => 'Durée', 'value' => $sound->duration ? sprintf('%d:%02d', intdiv($sound->duration, 60), $sound->duration % 60) : 'N/A', 'inline' => true],
                    ['name' => 'Licence', 'value' => $sound->license?->value ?? 'N/A', 'inline' => true],
                ],
                'image' => $sound->cover_image ? asset("storage/{$sound->cover_image}") : null,
                'timestamp' => now()->toIso8601String(),
            ]
        ));
    }

    public function notifyNewUser(User $user): void
    {
        $settings = DiscordSetting::getSettings();

        if (! $settings->bot_enabled || empty($settings->welcome_channel_id)) {
            return;
        }

        event(new DiscordNotification(
            channelId: $settings->welcome_channel_id,
            content: "👋 Bienvenue à **{$user->name}** sur Arborisis !",
            embed: [
                'title' => 'Nouveau membre',
                'description' => "Rejoignez-le sur son profil : " . route('profile.show', $user->slug, absolute: true),
                'color' => 0x4ADE80,
                'timestamp' => now()->toIso8601String(),
            ]
        ));
    }

    public function notifyDonation(EchoDonation $donation): void
    {
        $settings = DiscordSetting::getSettings();

        if (! $settings->bot_enabled || empty($settings->notification_channel_id)) {
            return;
        }

        event(new DiscordNotification(
            channelId: $settings->notification_channel_id,
            embed: [
                'title' => '💰 Nouveau don ECHO',
                'description' => "**{$donation->donor->name}** a donné **{$donation->amount} ECHO** à **{$donation->recipient->name}**.",
                'color' => 0xF59E0B,
                'fields' => [
                    ['name' => 'Message', 'value' => $donation->message ?: 'Aucun message', 'inline' => false],
                    ['name' => 'Part créateur', 'value' => "{$donation->creator_share} ECHO", 'inline' => true],
                    ['name' => 'Part plateforme', 'value' => "{$donation->platform_share} ECHO", 'inline' => true],
                    ['name' => 'Fonds communautaire', 'value' => "{$donation->community_share} ECHO", 'inline' => true],
                ],
                'timestamp' => now()->toIso8601String(),
            ]
        ));
    }

    public function notifyReportSubmitted(Report $report): void
    {
        $settings = DiscordSetting::getSettings();

        if (! $settings->bot_enabled || empty($settings->moderation_channel_id)) {
            return;
        }

        $reportableTitle = match ($report->reportable_type) {
            'App\Models\Sound' => $report->reportable->title ?? 'Son supprimé',
            'App\Models\Comment' => 'Commentaire #' . $report->reportable->id,
            default => 'Contenu',
        };

        event(new DiscordNotification(
            channelId: $settings->moderation_channel_id,
            content: '🚨 Nouveau signalement',
            embed: [
                'title' => "Signalement : {$report->reason}",
                'description' => $report->description ?? 'Pas de description',
                'color' => 0xEF4444,
                'fields' => [
                    ['name' => 'Type', 'value' => class_basename($report->reportable_type), 'inline' => true],
                    ['name' => 'Contenu', 'value' => $reportableTitle, 'inline' => true],
                    ['name' => 'Signalé par', 'value' => $report->reporter->name, 'inline' => true],
                ],
                'timestamp' => now()->toIso8601String(),
            ]
        ));
    }

    public function notifySoundAnalyzed(Sound $sound): void
    {
        $link = $sound->user?->discordAccount;

        if (! $link) {
            return;
        }

        event(new DiscordNotification(
            discordId: $link->discord_id,
            embed: [
                'title' => '🔬 Analyse audio terminée',
                'description' => "L'analyse de votre son **{$sound->title}** est terminée.",
                'url' => route('sounds.show', $sound->slug, absolute: true),
                'color' => 0x3B82F6,
                'timestamp' => now()->toIso8601String(),
            ]
        ));
    }
}
