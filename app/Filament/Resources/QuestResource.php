<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ObjectiveType;
use App\Enums\QuestType;
use App\Filament\Resources\QuestResource\Pages;
use App\Models\Quest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuestResource extends Resource
{
    protected static ?string $model = Quest::class;
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Quêtes';
    protected static ?string $modelLabel = 'Quête';
    protected static ?string $pluralModelLabel = 'Quêtes';
    protected static ?string $navigationGroup = 'Gamification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations')
                    ->schema([
                        Forms\Components\TextInput::make('title')->required()->maxLength(255),
                        Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->options(collect(QuestType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->toArray())
                            ->default(QuestType::Daily->value)
                            ->required(),
                        Forms\Components\TextInput::make('category')->default('general'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Objectif')
                    ->schema([
                        Forms\Components\Select::make('objective_type')
                            ->options(collect(ObjectiveType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->toArray())
                            ->required(),
                        Forms\Components\TextInput::make('objective_target')->numeric()->default(1)->required(),
                        Forms\Components\KeyValue::make('objective_payload'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Récompenses')
                    ->schema([
                        Forms\Components\TextInput::make('reward_xp')->numeric()->default(0),
                        Forms\Components\Select::make('reward_medal_id')
                            ->relationship('rewardMedal', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('reward_achievement_id')
                            ->relationship('rewardAchievement', 'title')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Planning')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')->nullable(),
                        Forms\Components\DateTimePicker::make('ends_at')->nullable(),
                        Forms\Components\Toggle::make('is_repeatable')->default(false),
                        Forms\Components\Toggle::make('is_active')->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->formatStateUsing(fn ($state) => $state->label()),
                Tables\Columns\TextColumn::make('objective_type')->badge(),
                Tables\Columns\TextColumn::make('reward_xp')->suffix(' XP'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('starts_at')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(QuestType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->toArray()),
                Tables\Filters\TernaryFilter::make('is_active'),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuests::route('/'),
            'create' => Pages\CreateQuest::route('/create'),
            'edit' => Pages\EditQuest::route('/{record}/edit'),
        ];
    }
}
