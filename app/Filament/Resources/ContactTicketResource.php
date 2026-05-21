<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ContactTicketCategory;
use App\Enums\ContactTicketPriority;
use App\Enums\ContactTicketReplySource;
use App\Enums\ContactTicketStatus;
use App\Enums\ContactTicketType;
use App\Enums\UserRole;
use App\Filament\Resources\ContactTicketResource\Pages;
use App\Models\ContactTicket;
use App\Models\User;
use App\Services\Helpdesk\HelpdeskService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class ContactTicketResource extends Resource
{
    protected static ?string $model = ContactTicket::class;
    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Tickets de contact';
    protected static ?string $modelLabel = 'Ticket';
    protected static ?string $pluralModelLabel = 'Tickets';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du ticket')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(
                                collect(ContactTicketType::cases())
                                    ->mapWithKeys(fn (ContactTicketType $type) => [$type->value => $type->label()])
                                    ->toArray()
                            )
                            ->diasabled()
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options(
                                collect(ContactTicketCategory::cases())
                                    ->mapWithKeys(fn (ContactTicketCategory $cat) => [$cat->value => $cat->label()])
                                    ->toArray()
                            )
                            ->required(),
                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options(
                                collect(ContactTicketPriority::cases())
                                    ->mapWithKeys(fn (ContactTicketPriority $p) => [$p->value => $p->label()])
                                    ->toArray()
                            )
                            ->required(),
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigné à')
                            ->options(
                                User::whereIn('role', [UserRole::Moderator, UserRole::Admin])
                                    ->pluck('name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->prependOptionLabel('Non assigné'),
                        Forms\Components\TextInput::make('ticket_number')
                            ->label('Numéro de suivi')
                            ->diasabled(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->diasabled(),
                        Forms\Components\TextInput::make('email')
                            ->label('E-mail')
                            ->diasabled(),
                        Forms\Components\TextInput::make('subject')
                            ->label('Sujet')
                            ->diasabled(),
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->diasabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Statut et résolution')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(
                                collect(ContactTicketStatus::cases())
                                    ->mapWithKeys(fn (ContactTicketStatus $status) => [$status->value => $status->label()])
                                    ->toArray()
                            )
                            ->required(),
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Résolu le')
                            ->native(false)
                            ->diasabled(),
                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Notes internes')
                            ->placeholder('Notes visibles uniquement par l\'équipe...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Historique des réponses')
                    ->visibleOn('edit')
                    ->schema([
                        Forms\Components\Placeholder::make('replies_list')
                            ->label('')
                            ->content(function (ContactTicket $record): HtmlString {
                                $replies = $record->replies->where('is_internal', false);
                                if ($replies->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-500">Aucune réponse pour le moment.</p>');
                                }

                                $html = $replies->map(function ($reply) use ($record) {
                                    $author = e($reply->source === ContactTicketReplySource::Customer ? $record->name : ($reply->user?->name ?? 'Équipe Arborisis'));
                                    $date = $reply->created_at->format('d/m/Y à H:i');
                                    $message = nl2br(e($reply->reply));
                                    $borderColor = $reply->source === ContactTicketReplySource::Customer ? 'border-gray-400' : 'border-primary-600';

                                    return <<<HTML
                                    <div class="mb-4 border-l-4 {$borderColor} pl-4 py-1">
                                        <p class="text-xs font-medium text-gray-500 mb-1">{$author} — {$date}</p>
                                        <div class="text-sm text-gray-900">{$message}</div>
                                    </div>
                                    HTML;
                                })->implode('');

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Notes internes')
                    ->visibleOn('edit')
                    ->schema([
                        Forms\Components\Placeholder::make('internal_replies_list')
                            ->label('')
                            ->content(function (ContactTicket $record): HtmlString {
                                $replies = $record->replies->where('is_internal', true);
                                if ($replies->isEmpty()) {
                                    return new HtmlString('<p class="text-sm text-gray-500">Aucune note interne.</p>');
                                }

                                $html = $replies->map(function ($reply) {
                                    $author = e($reply->user?->name ?? 'Équipe');
                                    $date = $reply->created_at->format('d/m/Y à H:i');
                                    $message = nl2br(e($reply->reply));

                                    return <<<HTML
                                    <div class="mb-4 border-l-4 border-amber-500 pl-4 py-1">
                                        <p class="text-xs font-medium text-gray-500 mb-1">{$author} — {$date}</p>
                                        <div class="text-sm text-gray-900">{$message}</div>
                                    </div>
                                    HTML;
                                })->implode('');

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Répondre au ticket')
                    ->visibleOn('edit')
                    ->schema([
                        Forms\Components\Toggle::make('reply_is_internal')
                            ->label('Note interne (non visible par le client)')
                            ->default(false),
                        Forms\Components\Textarea::make('new_reply')
                            ->label('Nouvelle réponse')
                            ->placeholder('Rédigez votre réponse ici...')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')
                    ->label('N° suivi')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (ContactTicketType $state): string => $state->label())
                    ->color(fn (ContactTicketType $state): string => match ($state) {
                        ContactTicketType::Contact => 'info',
                        ContactTicketType::Privacy => 'warning',
                        ContactTicketType::Support => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->badge()
                    ->formatStateUsing(fn (ContactTicketCategory $state): string => $state->label())
                    ->color(fn (ContactTicketCategory $state): string => $state->color())
                    ->sortable(),
                Tables\Columns\TextColumn::make('priority')
                    ->label('Priorité')
                    ->badge()
                    ->formatStateUsing(fn (ContactTicketPriority $state): string => $state->label())
                    ->color(fn (ContactTicketPriority $state): string => $state->color())
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Sujet')
                    ->searchable()
                    ->limit(40),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigné à')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Non assigné'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ContactTicketStatus $state): string => $state->label())
                    ->color(fn (ContactTicketStatus $state): string => match ($state) {
                        ContactTicketStatus::New => 'gray',
                        ContactTicketStatus::InProgress => 'warning',
                        ContactTicketStatus::Resolved => 'success',
                        ContactTicketStatus::Spam => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Reçu le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(
                        collect(ContactTicketType::cases())
                            ->mapWithKeys(fn (ContactTicketType $type) => [$type->value => $type->label()])
                            ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(
                        collect(ContactTicketCategory::cases())
                            ->mapWithKeys(fn (ContactTicketCategory $cat) => [$cat->value => $cat->label()])
                            ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options(
                        collect(ContactTicketPriority::cases())
                            ->mapWithKeys(fn (ContactTicketPriority $p) => [$p->value => $p->label()])
                            ->toArray()
                    ),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(
                        collect(ContactTicketStatus::cases())
                            ->mapWithKeys(fn (ContactTicketStatus $status) => [$status->value => $status->label()])
                            ->toArray()
                    ),
                Tables\Filters\TernaryFilter::make('deleted_at')
                    ->label('Supprimés')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactTickets::route('/'),
            'edit' => Pages\EditContactTicket::route('/{record}/edit'),
        ];
    }
}
