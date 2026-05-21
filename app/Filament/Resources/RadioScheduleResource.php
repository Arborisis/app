<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\RadioScheduleRepeat;
use App\Filament\Resources\RadioScheduleResource\Pages;
use App\Models\RadioSchedule;
use App\Models\Sound;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RadioScheduleResource extends Resource
{
    protected static ?string $model = RadioSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Radio';

    protected static ?string $navigationLabel = 'Plannings';

    protected static ?string $modelLabel = 'Planning radio';

    protected static ?string $pluralModelLabel = 'Plannings radio';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Programmation')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),

                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Début')
                            ->seconds(false),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('Fin')
                            ->seconds(false)
                            ->after('starts_at'),

                        Forms\Components\Select::make('repeat')
                            ->label('Répétition')
                            ->options(
                                collect(RadioScheduleRepeat::cases())
                                    ->mapWithKeys(fn (RadioScheduleRepeat $repeat) => [$repeat->value => $repeat->label()])
                                    ->toArray()
                            )
                            ->default(RadioScheduleRepeat::None->value)
                            ->required(),

                        Forms\Components\TextInput::make('priority')
                            ->label('Priorité')
                            ->helperText('Plus la valeur est haute, plus ce planning passe avant les autres.')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Sélection des sons')
                    ->schema([
                        Forms\Components\Select::make('sounds')
                            ->label('Sons à programmer')
                            ->relationship(
                                'sounds',
                                'title',
                                fn (Builder $query) => $query->public()
                                    ->whereHas('soundFile', fn (Builder $q) => $q
                                        ->whereNotNull('radio_path')
                                        ->orWhere('mime_type', 'audio/mpeg'))
                            )
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn (Sound $record): string => "{$record->title} — {$record->user?->name}")
                            ->helperText('Ces sons deviennent le bloc prioritaire de ce créneau.'),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes internes')
                            ->rows(3),
                    ])
                    ->columns(1),
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

                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Début')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('repeat')
                    ->label('Répétition')
                    ->badge()
                    ->formatStateUsing(fn (RadioScheduleRepeat $state): string => $state->label()),

                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorité')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sounds_count')
                    ->label('Sons')
                    ->counts('sounds')
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
            ])
            ->defaultSort('starts_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRadioSchedules::route('/'),
            'create' => Pages\CreateRadioSchedule::route('/create'),
            'edit' => Pages\EditRadioSchedule::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
