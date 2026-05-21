<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\EnvironmentResource\Pages;
use App\Models\Environment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EnvironmentResource extends Resource
{
    protected static ?string $model = Environment::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-europe-africa';

    protected static ?string $navigationLabel = 'Environnements';

    protected static ?string $modelLabel = 'Environnement';

    protected static ?string $pluralModelLabel = 'Environnements';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\ColorPicker::make('color')
                    ->label('Couleur')
                    ->default('#2d5a3d'),

                Forms\Components\TextInput::make('icon')
                    ->label('Icône (Heroicon)')
                    ->placeholder('heroicon-o-tree')
                    ->maxLength(255),

                Forms\Components\TextInput::make('order')
                    ->label('Ordre')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')
                    ->label('Couleur'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),

                Tables\Columns\TextColumn::make('sounds_count')
                    ->label('Sons')
                    ->counts('sounds')
                    ->sortable(),

                Tables\Columns\TextColumn::make('order')
                    ->label('Ordre')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order', 'asc');
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
            'index' => Pages\ListEnvironments::route('/'),
            'create' => Pages\CreateEnvironment::route('/create'),
            'edit' => Pages\EditEnvironment::route('/{record}/edit'),
        ];
    }
}
