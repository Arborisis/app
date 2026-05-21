<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ArborisisCategory;
use App\Enums\ModerationStatus;
use App\Enums\NatureSensitivityLevel;
use App\Filament\Resources\ArborisisPointResource\Pages;
use App\Models\ArborisisPoint;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArborisisPointResource extends Resource
{
    protected static ?string $model = ArborisisPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Points Arborisis';

    protected static ?string $modelLabel = 'Point Arborisis';

    protected static ?string $pluralModelLabel = 'Points Arborisis';

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

                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options(
                                collect(ArborisisCategory::cases())
                                    ->mapWithKeys(fn (ArborisisCategory $cat) => [$cat->value => $cat->label()])
                                    ->toArray()
                            )
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Localisation')
                    ->schema([
                        Forms\Components\TextInput::make('latitude')
                            ->label('Latitude exacte')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('longitude')
                            ->label('Longitude exacte')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('approximate_latitude')
                            ->label('Latitude approximative')
                            ->numeric()
                            ->required(),

                        Forms\Components\TextInput::make('approximate_longitude')
                            ->label('Longitude approximative')
                            ->numeric()
                            ->required(),
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
                        Forms\Components\TextInput::make('difficulty_level')
                            ->label('Niveau de difficulté')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->default(1),

                        Forms\Components\TextInput::make('recommended_time')
                            ->label('Moment recommandé')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('audio_environment_type')
                            ->label('Type d\'environnement sonore')
                            ->maxLength(100),

                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Ajouter un tag')
                            ->separator(',')
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('cover_image')
                            ->label('Image de couverture')
                            ->image()
                            ->directory('arborisis/covers')
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

                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn (ArborisisCategory $state): string => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('moderation_status')
                    ->label('Modération')
                    ->badge()
                    ->formatStateUsing(fn (ModerationStatus $state): string => $state->label())
                    ->color(fn (ModerationStatus $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('nature_sensitivity_level')
                    ->label('Sensibilité')
                    ->badge()
                    ->formatStateUsing(fn (NatureSensitivityLevel $state): string => $state->label())
                    ->color(fn (NatureSensitivityLevel $state): string => match ($state) {
                        NatureSensitivityLevel::Normal => 'success',
                        NatureSensitivityLevel::Fragile => 'warning',
                        NatureSensitivityLevel::SensitiveSpecies => 'danger',
                        NatureSensitivityLevel::Private => 'gray',
                        NatureSensitivityLevel::Dangerous => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('moderation_status')
                    ->label('Statut de modération')
                    ->options(
                        collect(ModerationStatus::cases())
                            ->mapWithKeys(fn (ModerationStatus $status) => [$status->value => $status->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(
                        collect(ArborisisCategory::cases())
                            ->mapWithKeys(fn (ArborisisCategory $cat) => [$cat->value => $cat->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('nature_sensitivity_level')
                    ->label('Sensibilité')
                    ->options(
                        collect(NatureSensitivityLevel::cases())
                            ->mapWithKeys(fn (NatureSensitivityLevel $level) => [$level->value => $level->label()])
                            ->toArray()
                    ),

                Tables\Filters\TernaryFilter::make('deleted_at')
                    ->label('Supprimés')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
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
            'index' => Pages\ListArborisisPoints::route('/'),
            'create' => Pages\CreateArborisisPoint::route('/create'),
            'edit' => Pages\EditArborisisPoint::route('/{record}/edit'),
        ];
    }
}
