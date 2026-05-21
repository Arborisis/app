<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Filament\Resources\SoundWalkResource\Pages;
use App\Models\SoundWalk;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SoundWalkResource extends Resource
{
    protected static ?string $model = SoundWalk::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Balades field recording';

    protected static ?string $modelLabel = 'Balade';

    protected static ?string $pluralModelLabel = 'Balades field recording';

    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('user_id')
                            ->label('Créateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Localisation & Itinéraire')
                    ->schema([
                        Forms\Components\TextInput::make('start_latitude')
                            ->label('Latitude de départ')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('start_longitude')
                            ->label('Longitude de départ')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('approximate_start_latitude')
                            ->label('Latitude approximative')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('approximate_start_longitude')
                            ->label('Longitude approximative')
                            ->numeric()
                            ->required(),

                        Forms\Components\KeyValue::make('route_geometry')
                            ->label('Géométrie de route (waypoints)')
                            ->keyLabel('Propriété')
                            ->valueLabel('Valeur')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Modération & Sensibilité')
                    ->schema([
                        Forms\Components\Select::make('moderation_status')
                            ->label('Statut de modération')
                            ->options(
                                collect(ModerationStatus::cases())
                                    ->mapWithKeys(fn (ModerationStatus $status) => [$status->value => $status->label()])
                                    ->toArray()
                            )
                            ->default(ModerationStatus::Pending->value)
                            ->required(),

                        Forms\Components\Select::make('nature_sensitivity_level')
                            ->label('Niveau de sensibilité')
                            ->options(
                                collect(NatureSensitivityLevel::cases())
                                    ->mapWithKeys(fn (NatureSensitivityLevel $level) => [$level->value => $level->label()])
                                    ->toArray()
                            )
                            ->default(NatureSensitivityLevel::Normal->value)
                            ->required(),

                        Forms\Components\Select::make('visibility_status')
                            ->label('Visibilité')
                            ->options([
                                'public' => 'Public',
                                'unlisted' => 'Non listé',
                                'private' => 'Privé',
                            ])
                            ->default('public')
                            ->required(),

                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approuvé le')
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Détails')
                    ->schema([
                        Forms\Components\TextInput::make('estimated_duration_minutes')
                            ->label('Durée estimée (minutes)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(1440),

                        Forms\Components\TextInput::make('difficulty_level')
                            ->label('Niveau de difficulté')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(1),

                        Forms\Components\TextInput::make('audio_environment_type')
                            ->label("Type d'environnement sonore")
                            ->maxLength(100),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Ajouter un tag')
                            ->separator(',')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Image de couverture')
                            ->image()
                            ->directory('sound-walks/covers')
                            ->disk(config('filesystems.default', 'local'))
                            ->visibility('public')
                            ->maxSize(5120),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Créateur')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('moderation_status')
                    ->label('Modération')
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => $state->label())
                    ->color(fn (ModerationStatus $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('estimated_duration_minutes')
                    ->label('Durée (min)')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('difficulty_level')
                    ->label('Difficulté')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('moderation_status')
                    ->label('Statut')
                    ->options(
                        collect(ModerationStatus::cases())
                            ->mapWithKeys(fn (ModerationStatus $status) => [$status->value => $status->label()])
                            ->toArray()
                    ),

                Tables\Filters\TernaryFilter::make('deleted_at')
                    ->label('Supprimées')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSoundWalks::route('/'),
            'create' => Pages\CreateSoundWalk::route('/create'),
            'edit' => Pages\EditSoundWalk::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
