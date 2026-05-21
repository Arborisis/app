<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\NewsletterSubscriberResource\Pages;
use App\Models\NewsletterSubscriber;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NewsletterSubscriberResource extends Resource
{
    protected static ?string $model = NewsletterSubscriber::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Abonnés newsletter';
    protected static ?string $modelLabel = 'Abonné';
    protected static ?string $pluralModelLabel = 'Abonnés';
    protected static ?string $navigationGroup = 'Communication';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('source')
                    ->label('Source')
                    ->maxLength(255)
                    ->placeholder('footer, landing...'),
                Forms\Components\DateTimePicker::make('subscribed_at')
                    ->label('Inscrit le')
                    ->required(),
                Forms\Components\DateTimePicker::make('unsubscribed_at')
                    ->label('Désinscrit le'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->label('Statut')
                    ->icon(fn (NewsletterSubscriber $record): string => $record->isActive() ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn (NewsletterSubscriber $record): string => $record->isActive() ? 'success' : 'danger')
                    ->sortable(false),
                Tables\Columns\TextColumn::make('subscribed_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('unsubscribed_at')
                    ->label('Désinscrit le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Actifs')
                    ->query(fn ($query) => $query->active()),
                Tables\Filters\Filter::make('unsubscribed')
                    ->label('Désinscrits')
                    ->query(fn ($query) => $query->unsubscribed()),
                Tables\Filters\SelectFilter::make('source')
                    ->label('Source')
                    ->options(fn () => NewsletterSubscriber::distinct()->pluck('source', 'source')->filter()->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Exporter les actifs')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            $activeEmails = $records->filter->isActive()->pluck('email')->implode("\n");
                            return response()->streamDownload(function () use ($activeEmails) {
                                echo $activeEmails;
                            }, 'abonnes-actifs.txt');
                        }),
                ]),
            ])
            ->defaultSort('subscribed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterSubscribers::route('/'),
            'edit' => Pages\EditNewsletterSubscriber::route('/{record}/edit'),
        ];
    }
}
