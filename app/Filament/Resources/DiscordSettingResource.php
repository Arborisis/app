<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DiscordSettingResource\Pages;
use App\Models\DiscordSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

class DiscordSettingResource extends Resource
{
    protected static ?string $model = DiscordSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Paramètres Discord';

    protected static ?string $modelLabel = 'Paramètre Discord';

    protected static ?string $pluralModelLabel = 'Paramètres Discord';

    protected static ?string $navigationGroup = 'Discord';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuration du bot')
                    ->schema([
                        Forms\Components\Toggle::make('bot_enabled')
                            ->label('Bot activé')
                            ->default(false)
                            ->helperText('Active ou désactive l\'envoi de notifications Discord.'),

                        Forms\Components\TextInput::make('guild_id')
                            ->label('ID du serveur Discord (Guild ID)')
                            ->placeholder('1234567890123456789')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('notification_channel_id')
                            ->label('ID du salon de notifications')
                            ->placeholder('1234567890123456789')
                            ->maxLength(255)
                            ->helperText('Nouveaux sons, dons, etc.'),

                        Forms\Components\TextInput::make('welcome_channel_id')
                            ->label('ID du salon de bienvenue')
                            ->placeholder('1234567890123456789')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('radio_channel_id')
                            ->label('ID du salon radio')
                            ->placeholder('1234567890123456789')
                            ->maxLength(255)
                            ->helperText('Notifications de la radio (optionnel).'),

                        Forms\Components\TextInput::make('moderation_channel_id')
                            ->label('ID du salon de modération')
                            ->placeholder('1234567890123456789')
                            ->maxLength(255)
                            ->helperText('Signalements et alertes admin.'),

                        Forms\Components\TextInput::make('internal_api_token')
                            ->label('Token API interne')
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->helperText('Token partagé entre Laravel et le bot Node.js. Ne pas partager.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageDiscordSettings::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
