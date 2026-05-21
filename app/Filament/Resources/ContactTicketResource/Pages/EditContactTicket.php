<?php

declare(strict_types=1);

namespace App\Filament\Resources\ContactTicketResource\Pages;

use App\Enums\ContactTicketStatus;
use App\Filament\Resources\ContactTicketResource;
use App\Services\Helpdesk\HelpdeskService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditContactTicket extends EditRecord
{
    protected static string $resource = ContactTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = app(HelpdeskService::class);

        if (! empty($data['new_reply'])) {
            $service->addTeamReply(
                $record,
                $data['new_reply'],
                auth()->user(),
                $data['reply_is_internal'] ?? false
            );

            $data['replied_at'] = now();

            if (! $data['reply_is_internal'] && $data['status'] === ContactTicketStatus::New->value) {
                $data['status'] = ContactTicketStatus::InProgress->value;
            }
        }

        if ($data['status'] === ContactTicketStatus::Resolved->value && $record->resolved_at === null) {
            $data['resolved_at'] = now();
        }

        if ($data['status'] !== ContactTicketStatus::Resolved->value) {
            $data['resolved_at'] = null;
        }

        unset($data['new_reply'], $data['reply_is_internal']);

        $record->update($data);

        return $record;
    }
}
