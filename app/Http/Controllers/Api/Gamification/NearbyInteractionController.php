<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Gamification;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\Gamification\NearbyInteractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NearbyInteractionController extends Controller
{
    public function __construct(
        private readonly NearbyInteractionService $service,
    ) {
    }

    public function greet(Request $request, User $user): JsonResponse
    {
        $initiator = $request->user();

        if ($initiator->id === $user->id) {
            return response()->json(['message' => 'Tu ne peux pas te saluer toi-même.'], 422);
        }

        $initiatorPresence = UserPresence::where('user_id', $initiator->id)->active()->first();
        $recipientPresence = UserPresence::where('user_id', $user->id)->active()->first();

        if (! $initiatorPresence || ! $recipientPresence) {
            return response()->json(['message' => 'Présence non disponible pour la vérification de proximité.'], 422);
        }

        $distance = $this->service->detectProximity($initiatorPresence, $recipientPresence);

        if ($distance === null) {
            return response()->json(['message' => 'Cet utilisateur n\'est pas à proximité.'], 422);
        }

        if (! $this->service->canInteract($initiator, $user)) {
            return response()->json(['message' => 'Tu as déjà interagi avec cet utilisateur récemment.'], 429);
        }

        $interaction = $this->service->sendGreet($initiator, $user, $distance);

        return response()->json([
            'message' => 'Salut envoyé !',
            'interaction' => $interaction,
        ]);
    }

    public function shareTip(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'sound_id' => ['nullable', 'integer', 'exists:sounds,id'],
        ]);

        $initiator = $request->user();
        $recipient = User::findOrFail($validated['recipient_id']);

        if ($initiator->id === $recipient->id) {
            return response()->json(['message' => 'Opération invalide.'], 422);
        }

        if (! $this->service->canInteract($initiator, $recipient)) {
            return response()->json(['message' => 'Cooldown actif. Réessaie plus tard.'], 429);
        }

        $interaction = $this->service->sendShareTip($initiator, $recipient, [
            'note' => $validated['note'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'sound_id' => $validated['sound_id'] ?? null,
        ]);

        return response()->json([
            'message' => 'Tip partagé !',
            'interaction' => $interaction,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->historyForUser($request->user())
        );
    }
}
