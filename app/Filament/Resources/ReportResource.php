<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?string $navigationLabel = 'Signalements';

    protected static ?string $modelLabel = 'Signalement';

    protected static ?string $pluralModelLabel = 'Signalements';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Détails du signalement')
                    ->schema([
                        Forms\Components\Select::make('reporter_id')
                            ->label('Signalé par')
                            ->relationship('reporter', 'name')
                            ->disabled()
                            ->required(),

                        Forms\Components\TextInput::make('reportable_type')
                            ->label('Type de contenu')
                            ->disabled(),

                        Forms\Components\TextInput::make('reportable_id')
                            ->label('ID du contenu')
                            ->disabled()
                            ->numeric(),

                        Forms\Components\Select::make('reason')
                            ->label('Motif')
                            ->options(
                                collect(ReportReason::cases())
                                    ->mapWithKeys(fn (ReportReason $reason) => [$reason->value => $reason->label()])
                                    ->toArray()
                            )
                            ->disabled()
                            ->required(),

                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(4)
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Résolution')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(
                                collect(ReportStatus::cases())
                                    ->mapWithKeys(fn (ReportStatus $status) => [$status->value => $status->label()])
                                    ->toArray()
                            )
                            ->default(ReportStatus::Pending->value)
                            ->required()
                            ->live(),

                        Forms\Components\Placeholder::make('resolver_name')
                            ->label('Résolu par')
                            ->content(fn (Report $record): ?string => $record->resolver?->name),

                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Résolu le')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reporter.name')
                    ->label('Signalé par')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reportable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'App\\Models\\Sound' => 'Son',
                        'App\\Models\\Comment' => 'Commentaire',
                        'App\\Models\\User' => 'Utilisateur',
                        default => $state,
                    })
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reportable_id')
                    ->label('ID')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('reason')
                    ->label('Motif')
                    ->badge()
                    ->formatStateUsing(fn (ReportReason $state): string => $state->label())
                    ->color(fn (ReportReason $state): string => match ($state) {
                        ReportReason::Spam => 'gray',
                        ReportReason::Harassment => 'danger',
                        ReportReason::InappropriateContent => 'warning',
                        ReportReason::Copyright => 'primary',
                        ReportReason::Misinformation => 'info',
                        ReportReason::Other => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (ReportStatus $state): string => $state->label())
                    ->color(fn (ReportStatus $state): string => match ($state) {
                        ReportStatus::Pending => 'warning',
                        ReportStatus::Reviewing => 'primary',
                        ReportStatus::Resolved => 'success',
                        ReportStatus::Dismissed => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('resolver.name')
                    ->label('Résolu par')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Signalé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(
                        collect(ReportStatus::cases())
                            ->mapWithKeys(fn (ReportStatus $status) => [$status->value => $status->label()])
                            ->toArray()
                    )
                    ->default(ReportStatus::Pending->value),

                Tables\Filters\SelectFilter::make('reason')
                    ->label('Motif')
                    ->options(
                        collect(ReportReason::cases())
                            ->mapWithKeys(fn (ReportReason $reason) => [$reason->value => $reason->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('reportable_type')
                    ->label('Type de contenu')
                    ->options([
                        'App\\Models\\Sound' => 'Son',
                        'App\\Models\\Comment' => 'Commentaire',
                        'App\\Models\\User' => 'Utilisateur',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('review')
                    ->label('Examiner')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->visible(fn (Report $record): bool => $record->status === ReportStatus::Pending)
                    ->action(function (Report $record): void {
                        $record->update([
                            'status' => ReportStatus::Reviewing,
                        ]);

                        Notification::make()
                            ->title('Signalement en cours d\'examen')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('resolve')
                    ->label('Résoudre')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Report $record): bool => in_array($record->status, [ReportStatus::Pending, ReportStatus::Reviewing], true))
                    ->requiresConfirmation()
                    ->modalHeading('Résoudre le signalement')
                    ->modalDescription('Confirmez-vous que ce signalement est traité et résolu ?')
                    ->action(function (Report $record): void {
                        $record->update([
                            'status' => ReportStatus::Resolved,
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Signalement résolu')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('dismiss')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('gray')
                    ->visible(fn (Report $record): bool => in_array($record->status, [ReportStatus::Pending, ReportStatus::Reviewing], true))
                    ->requiresConfirmation()
                    ->modalHeading('Rejeter le signalement')
                    ->action(function (Report $record): void {
                        $record->update([
                            'status' => ReportStatus::Dismissed,
                            'resolved_by' => auth()->id(),
                            'resolved_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Signalement rejeté')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListReports::route('/'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
