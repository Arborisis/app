<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\MedalCategory;
use App\Enums\MedalRarity;
use App\Filament\Resources\MedalResource\Pages;
use App\Models\Medal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MedalResource extends Resource
{
    protected static ?string $model = Medal::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationLabel = 'Médailles';
    protected static ?string $modelLabel = 'Médaille';
    protected static ?string $pluralModelLabel = 'Médailles';
    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('icon')->nullable(),
                Forms\Components\Select::make('rarity')
                    ->options(collect(MedalRarity::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->toArray())
                    ->required(),
                Forms\Components\Select::make('category')
                    ->options(collect(MedalCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->toArray())
                    ->required(),
                Forms\Components\TextInput::make('unlock_condition_type')->required(),
                Forms\Components\KeyValue::make('unlock_condition_value'),
                Forms\Components\Toggle::make('is_secret')->default(false),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('rarity')->badge()->formatStateUsing(fn ($state) => $state->label())->color(fn ($state) => $state->color()),
                Tables\Columns\TextColumn::make('category')->badge()->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\IconColumn::make('is_secret')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rarity')
                    ->options(collect(MedalRarity::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMedals::route('/'),
            'create' => Pages\CreateMedal::route('/create'),
            'edit' => Pages\EditMedal::route('/{record}/edit'),
        ];
    }
}
