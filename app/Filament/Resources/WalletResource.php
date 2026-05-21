<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Wallets';

    protected static ?string $modelLabel = 'Wallet';

    protected static ?string $pluralModelLabel = 'Wallets';

    protected static ?string $navigationGroup = 'Économie';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Utilisateur')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('balance')
                    ->label('Solde ECHO')
                    ->required()
                    ->numeric()
                    ->suffix(' ECHO')
                    ->step(0.01)
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label('Solde ECHO')
                    ->suffix(' ECHO')
                    ->sortable()
                    ->color(fn (string|float|int $state): string => (float) $state > 0 ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
