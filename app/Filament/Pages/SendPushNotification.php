<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\Push\PushNotificationService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SendPushNotification extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Envoyer une notification';
    protected static ?string $title = 'Envoyer une notification push';
    protected static ?string $slug = 'send-push-notification';
    protected static string $view = 'filament.pages.send-push-notification';

    public ?string $titleField = null;
    public ?string $bodyField = null;
    public ?string $urlField = '/';

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('titleField')
                    ->label('Titre')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Nouvelle mise à jour Arborisis'),
                Textarea::make('bodyField')
                    ->label('Message')
                    ->required()
                    ->rows(3)
                    ->maxLength(500)
                    ->placeholder('Découvrez les dernières nouveautés...'),
                TextInput::make('urlField')
                    ->label('URL de redirection')
                    ->url()
                    ->maxLength(500)
                    ->default('/'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Envoyer la notification')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Confirmer l\'envoi')
                ->modalDescription('Cette notification sera envoyée à tous les utilisateurs abonnés.')
                ->modalSubmitActionLabel('Envoyer')
                ->action(function (PushNotificationService $service) {
                    $result = $service->sendToAll(
                        $this->titleField,
                        $this->bodyField,
                        $this->urlField ?: '/'
                    );

                    Notification::make()
                        ->title('Notification envoyée')
                        ->body("{$result['success']} succès, {$result['failures']} échecs, {$result['removed']} désabonnements.")
                        ->success()
                        ->send();
                }),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
