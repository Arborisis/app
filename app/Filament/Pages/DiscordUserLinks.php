<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\UserDiscordAccount;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\DeleteAction;

class DiscordUserLinks extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationLabel = 'Liens utilisateurs';
    protected static ?string $title = 'Liens Discord / Arborisis';
    protected static ?string $slug = 'discord-user-links';
    protected static string $view = 'filament.pages.discord-user-links';
    protected static ?string $navigationGroup = 'Discord';
    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->isModerator() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(UserDiscordAccount::query()->with('user'))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('discord_username')
                    ->label('Pseudo Discord')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('discord_id')
                    ->label('Discord ID')
                    ->copyable()
                    ->sortable(),

                TextColumn::make('linked_at')
                    ->label('Lié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                DeleteAction::make()
                    ->label('Supprimer le lien'),
            ])
            ->defaultSort('linked_at', 'desc');
    }
}
