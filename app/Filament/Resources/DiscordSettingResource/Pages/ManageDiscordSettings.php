<?php

declare(strict_types=1);

namespace App\Filament\Resources\DiscordSettingResource\Pages;

use App\Filament\Resources\DiscordSettingResource;
use App\Models\DiscordSetting;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManageDiscordSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = DiscordSettingResource::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.discord-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->refreshFormData();
    }

    public function booted(): void
    {
        $this->refreshFormData();
    }

    protected function refreshFormData(): void
    {
        $settings = DiscordSetting::getSettings();
        $this->form->fill($settings->toArray());
    }

    public function form(Form $form): Form
    {
        return DiscordSettingResource::form($form)
            ->statePath('data');
    }

    public function save(): void
    {
        $settings = DiscordSetting::getSettings();
        $settings->update($this->form->getState()['data'] ?? []);

        Notification::make()
            ->title('Paramètres sauvegardés')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Sauvegarder')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->action(fn () => $this->save()),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
