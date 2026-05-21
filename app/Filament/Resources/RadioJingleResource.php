<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\RadioJinglePlacement;
use App\Filament\Resources\RadioJingleResource\Pages;
use App\Models\RadioJingle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RadioJingleResource extends Resource
{
    protected static ?string $model = RadioJingle::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';

    protected static ?string $navigationGroup = 'Radio';

    protected static ?string $navigationLabel = 'Jingles';

    protected static ?string $modelLabel = 'Jingle';

    protected static ?string $pluralModelLabel = 'Jingles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Jingle')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),

                        Forms\Components\FileUpload::make('path')
                            ->label('Fichier audio')
                            ->disk(config('filesystems.audio_disk', 'audio'))
                            ->directory('radio/jingles')
                            ->acceptedFileTypes(['audio/mpeg'])
                            ->helperText('MP3 requis pour rester compatible avec le flux radio.')
                            ->maxSize(51200)
                            ->required(),

                        Forms\Components\Hidden::make('disk')
                            ->default(config('filesystems.audio_disk', 'audio')),

                        Forms\Components\TextInput::make('duration')
                            ->label('Durée')
                            ->numeric()
                            ->suffix('s')
                            ->minValue(0),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Diffusion')
                    ->schema([
                        Forms\Components\Select::make('placement')
                            ->label('Placement')
                            ->options(
                                collect(RadioJinglePlacement::cases())
                                    ->mapWithKeys(fn (RadioJinglePlacement $placement) => [$placement->value => $placement->label()])
                                    ->toArray()
                            )
                            ->default(RadioJinglePlacement::BetweenBlocks->value)
                            ->required(),

                        Forms\Components\TextInput::make('frequency')
                            ->label('Fréquence')
                            ->helperText('Exemple : 3 = un passage tous les 3 sons ou blocs selon le placement.')
                            ->numeric()
                            ->minValue(1)
                            ->default(3)
                            ->required(),

                        Forms\Components\TextInput::make('volume')
                            ->label('Volume')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(1)
                            ->step(0.05)
                            ->default(1)
                            ->required(),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Actif à partir du')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Actif jusqu’au')
                            ->seconds(false)
                            ->after('starts_at'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('placement')
                    ->label('Placement')
                    ->badge()
                    ->formatStateUsing(fn (RadioJinglePlacement $state): string => $state->label()),

                Tables\Columns\TextColumn::make('frequency')
                    ->label('Fréquence')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Durée')
                    ->suffix(' s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Actif'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRadioJingles::route('/'),
            'create' => Pages\CreateRadioJingle::route('/create'),
            'edit' => Pages\EditRadioJingle::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
