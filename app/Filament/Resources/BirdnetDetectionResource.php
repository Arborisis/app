<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BirdnetDetectionResource\Pages;
use App\Models\BirdnetDetection;
use App\Services\Audit\AuditLogService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BirdnetDetectionResource extends Resource
{
    protected static ?string $model = BirdnetDetection::class;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'Détections BirdNET';

    protected static ?string $modelLabel = 'Détection';

    protected static ?string $pluralModelLabel = 'Détections';

    protected static ?string $navigationGroup = 'Scientifique';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('scientific_name')
                    ->label('Nom scientifique')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('common_name')
                    ->label('Nom commun')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('confidence')
                    ->label('Confiance')
                    ->numeric()
                    ->step(0.001)
                    ->minValue(0)
                    ->maxValue(1)
                    ->required(),

                Forms\Components\TextInput::make('start_time')
                    ->label('Début (s)')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('end_time')
                    ->label('Fin (s)')
                    ->numeric()
                    ->required(),

                Forms\Components\TextInput::make('frequency_min')
                    ->label('Fréquence min (Hz)')
                    ->numeric(),

                Forms\Components\TextInput::make('frequency_max')
                    ->label('Fréquence max (Hz)')
                    ->numeric(),

                Forms\Components\Toggle::make('is_validated')
                    ->label('Validée')
                    ->default(false),

                Forms\Components\Select::make('validated_by')
                    ->label('Validée par')
                    ->relationship('validatedBy', 'name')
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sound.title')
                    ->label('Son')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('common_name')
                    ->label('Nom commun')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('scientific_name')
                    ->label('Nom scientifique')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('confidence')
                    ->label('Confiance')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_validated')
                    ->label('Validée')
                    ->boolean(),

                Tables\Columns\TextColumn::make('start_time')
                    ->label('Début')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('end_time')
                    ->label('Fin')
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Détecté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_validated')
                    ->label('Validée'),

                Tables\Filters\Filter::make('high_confidence')
                    ->label('Haute confiance (> 0.7)')
                    ->query(fn ($query) => $query->where('confidence', '>=', 0.7)),
            ])
            ->actions([
                Tables\Actions\Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (BirdnetDetection $record): bool => ! $record->is_validated)
                    ->action(function (BirdnetDetection $record, AuditLogService $audit) {
                        $record->update([
                            'is_validated' => true,
                            'validated_by' => auth()->id(),
                        ]);
                        $audit->logValidation($record);
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBirdnetDetections::route('/'),
            'edit' => Pages\EditBirdnetDetection::route('/{record}/edit'),
        ];
    }
}
