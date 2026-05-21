<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Profile\ProfileAvatarService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CreatorController extends Controller
{
    public function __construct(
        private readonly ProfileAvatarService $profileAvatarService,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $creators = User::query()
            ->withCount(['sounds as public_sounds_count' => fn ($q) => $q->public()])
            ->with('profile')
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhereHas('profile', fn ($q) => $q->where('bio', 'ilike', "%{$search}%"));
            }))
            ->orderBy('public_sounds_count', 'desc')
            ->paginate(48)
            ->through(function (User $creator): User {
                if ($creator->profile) {
                    $creator->profile->setAttribute(
                        'avatarUrl',
                        $this->profileAvatarService->url($creator->profile->avatar),
                    );
                }

                return $creator;
            })
            ->withQueryString();

        return Inertia::render('Creators/Index', [
            'creators' => $creators,
            'filters' => ['search' => $search],
        ]);
    }
}
