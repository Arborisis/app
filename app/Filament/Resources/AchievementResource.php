<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\AchievementCategory;
use App\Filament\Resources\AchievementResource\Pages;
use App\Models\Achievement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationLabel = 'Achievements';
    protected static ?string $modelLabel = 'Achievement';
    protected static ?string $pluralModelLabel = 'Achievements';
    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                Forms\Components\TextInput::make('icon')->nullable(),
                Forms\Components\Select::make('category')
                    ->options(collect(AchievementCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->toArray())
                    ->required(),
                Forms\Components\TextInput::make('points')->numeric()->default(0),
                Forms\Components\TextInput::make('condition_type')->required(),
                Forms\Components\KeyValue::make('condition_payload'),
                Forms\Components\Toggle::make('is_hidden')->default(false),
                Forms\Components\Toggle::make('is_active')->default(true),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category')->badge()->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('points')->suffix(' pts'),
                Tables\Columns\IconColumn::make('is_hidden')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(collect(AchievementCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->toArray()),
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
            'index' => Pages\ListAchievements::route('/'),
            'create' => Pages\CreateAchievement::route('/create'),
            'edit' => Pages\EditAchievement::route('/{record}/edit'),
        ];
    }
}
