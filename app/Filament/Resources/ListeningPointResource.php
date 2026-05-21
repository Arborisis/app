<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Filament\Resources\ListeningPointResource\Pages;
use App\Models\ListeningPoint;
use App\Services\Audit\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ListeningPointResource extends Resource
{
    protected static ?string $model = ListeningPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Points d\'écoute';

    protected static ?string $modelLabel = 'Point d\'écoute';

    protected static ?string $pluralModelLabel = 'Points d\'écoute';

    protected static ?string $navigationGroup = 'Scientifique';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Localisation')
                    ->schema([
                        Forms\Components\TextInput::make('exact_latitude')
                            ->label('Latitude exacte')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('exact_longitude')
                            ->label('Longitude exacte')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('public_latitude')
                            ->label('Latitude publique')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('public_longitude')
                            ->label('Longitude publique')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('public_accuracy_meters')
                            ->label('Précision publique (m)')
                            ->numeric()
                            ->default(1000)
                            ->required(),

                        Forms\Components\Select::make('nature_sensitivity_level')
                            ->label('Niveau de sensibilité')
                            ->options([
                                'normal' => 'Normal',
                                'fragile' => 'Fragile',
                                'protected' => 'Protégé',
                            ])
                            ->default('normal')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Métadonnées')
                    ->schema([
                        Forms\Components\Select::make('environment_id')
                            ->label('Environnement')
                            ->relationship('environment', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('habitat_type')
                            ->label('Type d\'habitat')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('country_code')
                            ->label('Code pays')
                            ->maxLength(2),

                        Forms\Components\TextInput::make('admin_level_1')
                            ->label('Région / Département')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Modération')
                    ->schema([
                        Forms\Components\Select::make('moderation_status')
                            ->label('Statut de modération')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'rejected' => 'Rejeté',
                                'hidden' => 'Masqué',
                            ])
                            ->default('pending')
                            ->required(),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approuvé le'),

                        Forms\Components\Select::make('approved_by')
                            ->label('Approuvé par')
                            ->relationship('approvedBy', 'name')
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Statistiques dénormalisées')
                    ->schema([
                        Forms\Components\TextInput::make('recordings_count')
                            ->label('Nombre d\'enregistrements')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\TextInput::make('species_detected_count')
                            ->label('Espèces détectées')
                            ->numeric()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('first_recorded_at')
                            ->label('Premier enregistrement')
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('last_recorded_at')
                            ->label('Dernier enregistrement')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('habitat_type')
                    ->label('Habitat')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'forest' => 'success',
                        'wetland' => 'info',
                        'river' => 'primary',
                        'meadow' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('recordings_count')
                    ->label('Enreg.')
                    ->sortable(),

                Tables\Columns\TextColumn::make('species_detected_count')
                    ->label('Espèces')
                    ->sortable(),

                Tables\Columns\TextColumn::make('moderation_status')
                    ->label('Modération')
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => match ($state) {
                        ModerationStatus::Pending => 'En attente',
                        ModerationStatus::Approved => 'Approuvé',
                        ModerationStatus::Rejected => 'Rejeté',
                        ModerationStatus::Hidden => 'Masqué',
                        default => $state->value,
                    })
                    ->color(fn (ModerationStatus $state): string => match ($state) {
                        ModerationStatus::Pending => 'warning',
                        ModerationStatus::Approved => 'success',
                        ModerationStatus::Rejected => 'danger',
                        ModerationStatus::Hidden => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Créateur')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('moderation_status')
                    ->label('Modération')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        'hidden' => 'Masqué',
                    ]),

                Tables\Filters\SelectFilter::make('habitat_type')
                    ->label('Habitat')
                    ->options([
                        'forest' => 'Forêt',
                        'wetland' => 'Zone humide',
                        'river' => 'Rivière',
                        'meadow' => 'Prairie',
                        'ocean' => 'Océan',
                        'mountain' => 'Montagne',
                        'urban_nature' => 'Nature urbaine',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ListeningPoint $record): bool => $record->moderation_status === ModerationStatus::Pending)
                    ->action(function (ListeningPoint $record, AuditLogService $audit) {
                        $record->update([
                            'moderation_status' => ModerationStatus::Approved,
                            'approved_at' => now(),
                            'approved_by' => auth()->id(),
                        ]);
                        $audit->logApproved($record);
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (ListeningPoint $record): bool => $record->moderation_status === ModerationStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (ListeningPoint $record, AuditLogService $audit) {
                        $record->update([
                            'moderation_status' => ModerationStatus::Rejected,
                            'approved_at' => null,
                            'approved_by' => null,
                        ]);
                        $audit->logRejected($record);
                    }),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListeningPoints::route('/'),
            'create' => Pages\CreateListeningPoint::route('/create'),
            'edit' => Pages\EditListeningPoint::route('/{record}/edit'),
        ];
    }
}
