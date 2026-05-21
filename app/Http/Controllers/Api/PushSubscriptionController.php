<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'p256dh' => $validated['keys']['p256dh'],
                'auth' => $validated['keys']['auth'],
                'user_agent' => $request->userAgent(),
                'ip_address' => $request->ip(),
                'subscribed_at' => now(),
                'deleted_at' => null,
            ]
        );

        return response()->json(['message' => 'Subscription enregistrée.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'url'],
        ]);

        PushSubscription::where('endpoint', $validated['endpoint'])->delete();

        return response()->json(['message' => 'Subscription supprimée.']);
    }
}
