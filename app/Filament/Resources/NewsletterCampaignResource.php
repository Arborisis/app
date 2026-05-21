<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\NewsletterCampaignStatus;
use App\Filament\Resources\NewsletterCampaignResource\Pages;
use App\Models\NewsletterCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class NewsletterCampaignResource extends Resource
{
    protected static ?string $model = NewsletterCampaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope-open';
    protected static ?string $navigationLabel = 'Campagnes newsletter';
    protected static ?string $modelLabel = 'Campagne';
    protected static ?string $pluralModelLabel = 'Campagnes';
    protected static ?string $navigationGroup = 'Communication';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('subject')
                    ->label('Sujet')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Nouveautés de la semaine...'),
                Forms\Components\RichEditor::make('content_html')
                    ->label('Contenu')
                    ->required()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'underline',
                        'strike',
                        'link',
                        'orderedList',
                        'unorderedList',
                        'heading',
                        'blockquote',
                    ])
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('content_text')
                    ->label('Version texte (optionnel)')
                    ->rows(4)
                    ->placeholder('Version texte brut de la newsletter...')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(
                        collect(NewsletterCampaignStatus::cases())
                            ->mapWithKeys(fn (NewsletterCampaignStatus $status) => [$status->value => $status->label()])
                            ->toArray()
                    )
                    ->disabled()
                    ->default(NewsletterCampaignStatus::Draft->value),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject')
                    ->label('Sujet')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (NewsletterCampaignStatus $state): string => $state->label())
                    ->color(fn (NewsletterCampaignStatus $state): string => $state->color())
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipients_count')
                    ->label('Destinataires')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Envoyée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('sender.name')
                    ->label('Envoyée par')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(
                        collect(NewsletterCampaignStatus::cases())
                            ->mapWithKeys(fn (NewsletterCampaignStatus $status) => [$status->value => $status->label()])
                            ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Aperçu')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Aperçu de la campagne')
                    ->modalContent(fn (NewsletterCampaign $record): HtmlString => new HtmlString(
                        '<div style="background:#0B1220;padding:24px;border-radius:12px;max-height:500px;overflow:auto;">'
                        . $record->content_html
                        . '</div>'
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),
                Tables\Actions\Action::make('send')
                    ->label('Envoyer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer l\'envoi')
                    ->modalDescription(fn (NewsletterCampaign $record): string =>
                        "Envoyer \"{$record->subject}\" à tous les abonnés actifs ?"
                    )
                    ->modalSubmitActionLabel('Envoyer')
                    ->visible(fn (NewsletterCampaign $record): bool => $record->isDraft())
                    ->action(function (NewsletterCampaign $record) {
                        $service = app(\App\Services\Newsletter\NewsletterMailService::class);
                        $service->sendCampaign($record);

                        Notification::make()
                            ->title('Campagne envoyée')
                            ->body("{$record->recipients_count} e-mails mis en file d'attente.")
                            ->success()
                            ->send();
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNewsletterCampaigns::route('/'),
            'create' => Pages\CreateNewsletterCampaign::route('/create'),
            'edit' => Pages\EditNewsletterCampaign::route('/{record}/edit'),
        ];
    }
}
