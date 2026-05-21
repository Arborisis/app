<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscriberResource\Pages;

use App\Filament\Resources\NewsletterSubscriberResource;
use App\Models\NewsletterSubscriber;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListNewsletterSubscribers extends ListRecords
{
    protected static string $resource = NewsletterSubscriberResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous')
                ->badge(NewsletterSubscriber::count()),
            'active' => Tab::make('Actifs')
                ->badge(NewsletterSubscriber::active()->count())
                ->modifyQueryUsing(fn ($query) => $query->active()),
            'unsubscribed' => Tab::make('Désinscrits')
                ->badge(NewsletterSubscriber::unsubscribed()->count())
                ->modifyQueryUsing(fn ($query) => $query->unsubscribed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
