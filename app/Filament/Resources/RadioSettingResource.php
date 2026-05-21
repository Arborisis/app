<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RadioSettingResource\Pages;
use App\Models\RadioSetting;
use App\Services\Radio\RadioStreamService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RadioSettingResource extends Resource
{
    protected static ?string $model = RadioSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Radio';

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $modelLabel = 'Paramètre radio';

    protected static ?string $pluralModelLabel = 'Paramètres radio';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Station')
                    ->schema([
                        Forms\Components\TextInput::make('station_name')
                            ->label('Nom de la radio')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('tagline')
                            ->label('Phrase courte')
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_enabled')
                            ->label('Radio active')
                            ->default(true),

                        Forms\Components\Toggle::make('shuffle_enabled')
                            ->label('Lecture aléatoire')
                            ->default(true),

                        Forms\Components\Toggle::make('loop_enabled')
                            ->label('Boucle continue')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Flux')
                    ->schema([
                        Forms\Components\TextInput::make('gap_ms')
                            ->label('Silence entre deux sons')
                            ->numeric()
                            ->suffix('ms')
                            ->minValue(0)
                            ->default(500)
                            ->required(),

                        Forms\Components\TextInput::make('icy_metaint')
                            ->label('Intervalle métadonnées ICY')
                            ->numeric()
                            ->suffix('octets')
                            ->minValue(1024)
                            ->default(8192)
                            ->required(),

                        Forms\Components\TextInput::make('history_limit')
                            ->label('Historique')
                            ->numeric()
                            ->minValue(1)
                            ->default(20)
                            ->required(),

                        Forms\Components\TextInput::make('listener_ttl_seconds')
                            ->label('Expiration auditeur')
                            ->numeric()
                            ->suffix('s')
                            ->minValue(30)
                            ->default(120)
                            ->required(),

                        Forms\Components\TextInput::make('max_listeners_display')
                            ->label('Plafond affiché')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Optionnel. Laisser vide pour afficher le nombre réel calculé.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_enabled')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('station_name')
                    ->label('Station')
                    ->searchable(),

                Tables\Columns\IconColumn::make('shuffle_enabled')
                    ->label('Aléatoire')
                    ->boolean(),

                Tables\Columns\IconColumn::make('loop_enabled')
                    ->label('Boucle')
                    ->boolean(),

                Tables\Columns\TextColumn::make('gap_ms')
                    ->label('Gap')
                    ->suffix(' ms'),

                Tables\Columns\TextColumn::make('listener_ttl_seconds')
                    ->label('TTL auditeur')
                    ->suffix(' s'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('reset_listeners')
                    ->label('Réinitialiser les auditeurs')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (): void {
                        app(RadioStreamService::class)->resetListenerCount();

                        Notification::make()
                            ->title('Compteur auditeurs remis à zéro')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageRadioSettings::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
