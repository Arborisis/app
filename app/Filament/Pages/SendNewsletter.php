<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\NewsletterCampaignStatus;
use App\Models\NewsletterCampaign;
use App\Services\Newsletter\NewsletterMailService;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class SendNewsletter extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Envoyer une newsletter';
    protected static ?string $title = 'Envoyer une newsletter';
    protected static ?string $slug = 'send-newsletter';
    protected static string $view = 'filament.pages.send-newsletter';
    protected static ?string $navigationGroup = 'Communication';
    protected static ?int $navigationSort = 3;

    public ?string $subject = null;
    public ?string $content = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('subject')
                    ->label('Sujet')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Nouveautés de la semaine...'),
                RichEditor::make('content')
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
                    ->hint(new HtmlString('Cette campagne sera enregistrée dans l\'historique.')),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Envoyer la newsletter')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmer l\'envoi')
                ->modalDescription('Cette newsletter sera envoyée à tous les abonnés actifs et enregistrée comme campagne.')
                ->modalSubmitActionLabel('Envoyer')
                ->action(function (NewsletterMailService $service) {
                    $campaign = NewsletterCampaign::create([
                        'subject' => $this->subject,
                        'content_html' => $this->content,
                        'content_text' => strip_tags($this->content),
                        'status' => NewsletterCampaignStatus::Draft,
                        'sent_by' => auth()->id(),
                    ]);

                    $service->sendCampaign($campaign);

                    Notification::make()
                        ->title('Newsletter envoyée')
                        ->body("{$campaign->recipients_count} e-mails mis en file d'attente.")
                        ->success()
                        ->send();

                    $this->reset(['subject', 'content']);
                }),
        ];
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
