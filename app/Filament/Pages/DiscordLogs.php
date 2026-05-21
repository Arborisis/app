<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DiscordLogs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Logs du bot';
    protected static ?string $title = 'Logs Discord';
    protected static ?string $slug = 'discord-logs';
    protected static string $view = 'filament.pages.discord-logs';
    protected static ?string $navigationGroup = 'Discord';
    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return auth()->user()?->isModerator() ?? false;
    }

    public function getViewData(): array
    {
        $logPath = base_path('../discord-bot/logs/out.log');
        $errorPath = base_path('../discord-bot/logs/err.log');

        $logs = [];
        if (file_exists($logPath)) {
            $logs = array_slice(file($logPath), -100);
        }

        $errors = [];
        if (file_exists($errorPath)) {
            $errors = array_slice(file($errorPath), -100);
        }

        return [
            'logs' => $logs,
            'errors' => $errors,
        ];
    }
}
