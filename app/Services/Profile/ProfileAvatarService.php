<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\User;
use App\Services\Storage\SignedUrlService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProfileAvatarService
{
    public function __construct(
        private readonly SignedUrlService $signedUrlService,
    ) {}

    public function update(User $user, UploadedFile $avatar): string
    {
        $disk = $this->disk();

        if ($user->profile?->avatar) {
            Storage::disk($disk)->delete($user->profile->avatar);
        }

        $path = $avatar->storeAs(
            "avatars/{$user->id}",
            Str::uuid()->toString().'.'.$avatar->extension(),
            $disk,
        );

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Avatar upload failed.');
        }

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['avatar' => $path],
        );

        return $path;
    }

    public function delete(User $user): void
    {
        if (! $user->profile?->avatar) {
            return;
        }

        Storage::disk($this->disk())->delete($user->profile->avatar);
    }

    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = $this->disk();

        return $disk === 'r2'
            ? $this->signedUrlService->url($disk, $path)
            : Storage::disk($disk)->url($path);
    }

    private function disk(): string
    {
        $configuredDisk = config('filesystems.avatar_disk', 'public');

        if ($configuredDisk === 'local') {
            return 'public';
        }

        return (string) $configuredDisk;
    }
}
