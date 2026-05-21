<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Filament\Resources\EchoTransactionResource\Pages;
use App\Models\EchoTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EchoTransactionResource extends Resource
{
    protected static ?string $model = EchoTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Transactions ECHO';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions ECHO';

    protected static ?string $navigationGroup = 'Économie';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(
                                collect(TransactionType::cases())
                                    ->mapWithKeys(fn (TransactionType $type) => [$type->value => $type->label()])
                                    ->toArray()
                            )
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(
                                collect(TransactionStatus::cases())
                                    ->mapWithKeys(fn (TransactionStatus $status) => [$status->value => $status->label()])
                                    ->toArray()
                            )
                            ->default(TransactionStatus::Pending->value)
                            ->required(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Montant (€)')
                            ->numeric()
                            ->prefix('€')
                            ->step(0.01),

                        Forms\Components\TextInput::make('echo_amount')
                            ->label('Montant ECHO')
                            ->numeric()
                            ->prefix('ECHO')
                            ->step(0.01),

                        Forms\Components\TextInput::make('currency')
                            ->label('Devise')
                            ->default('EUR')
                            ->maxLength(3),

                        Forms\Components\TextInput::make('stripe_payment_intent_id')
                            ->label('Stripe Payment Intent')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('stripe_checkout_session_id')
                            ->label('Stripe Checkout Session')
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('Terminée le'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Métadonnées')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Métadonnées'),
                    ]),
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

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (TransactionType $state): string => $state->label())
                    ->color(fn (TransactionType $state): string => match ($state) {
                        TransactionType::Purchase => 'primary',
                        TransactionType::Donation => 'success',
                        TransactionType::Tip => 'info',
                        TransactionType::Withdrawal => 'warning',
                        TransactionType::Refund => 'danger',
                        TransactionType::Commission => 'gray',
                        TransactionType::CommunityFund => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (TransactionStatus $state): string => $state->label())
                    ->color(fn (TransactionStatus $state): string => match ($state) {
                        TransactionStatus::Completed => 'success',
                        TransactionStatus::Pending => 'warning',
                        TransactionStatus::Failed => 'danger',
                        TransactionStatus::Cancelled => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('echo_amount')
                    ->label('ECHO')
                    ->suffix(' ECHO')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Terminée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(
                        collect(TransactionType::cases())
                            ->mapWithKeys(fn (TransactionType $type) => [$type->value => $type->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(
                        collect(TransactionStatus::cases())
                            ->mapWithKeys(fn (TransactionStatus $status) => [$status->value => $status->label()])
                            ->toArray()
                    ),
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
            'index' => Pages\ListEchoTransactions::route('/'),
            'create' => Pages\CreateEchoTransaction::route('/create'),
            'edit' => Pages\EditEchoTransaction::route('/{record}/edit'),
        ];
    }
}
