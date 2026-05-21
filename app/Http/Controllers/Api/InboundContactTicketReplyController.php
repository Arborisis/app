<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\InboundContactTicketReplyRequest;
use App\Services\Contact\ContactTicketService;
use Illuminate\Http\JsonResponse;

class InboundContactTicketReplyController extends Controller
{
    public function __invoke(
        InboundContactTicketReplyRequest $request,
        ContactTicketService $contactTicketService
    ): JsonResponse {
        $ticket = $contactTicketService->receiveInboundReply($request->validated());

        if ($ticket === null) {
            return response()->json(['status' => 'ignored'], 202);
        }

        return response()->json([
            'status' => 'accepted',
            'ticket_number' => $ticket->ticket_number,
        ]);
    }
}
