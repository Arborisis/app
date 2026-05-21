<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Internal;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class WikiOAuthController
{
    private const CODE_TTL = 300; // 5 minutes
    private const TOKEN_TTL = 3600; // 1 hour

    public function authorize(Request $request): RedirectResponse
    {
        $redirectUri = $request->input('redirect_uri');
        $state = $request->input('state');

        $parsed = parse_url($redirectUri);
        if (($parsed['host'] ?? '') !== 'wiki.arborisis.com' || ! str_starts_with($parsed['path'] ?? '', '/login/') || ! str_ends_with($parsed['path'] ?? '', '/callback')) {
            Log::warning('Wiki OAuth invalid redirect_uri', ['uri' => $redirectUri]);
            abort(400, 'Invalid redirect_uri');
        }

        if (! auth()->check()) {
            return redirect()->route('login', ['redirect' => $request->fullUrl()]);
        }

        /** @var User $user */
        $user = auth()->user();

        if (! in_array($user->role, [UserRole::Admin, UserRole::Moderator, UserRole::Creator], true)) {
            abort(403, 'Access denied. Wiki access is restricted to team members.');
        }

        $code = Str::random(64);
        Cache::put("wiki:oauth:code:{$code}", [
            'user_id' => $user->id,
            'redirect_uri' => $redirectUri,
        ], self::CODE_TTL);

        return redirect("{$redirectUri}?code={$code}&state={$state}");
    }

    public function token(Request $request): JsonResponse
    {
        $clientId = null;
        $clientSecret = null;

        $authHeader = $request->header('Authorization', '');
        if (str_starts_with($authHeader, 'Basic ')) {
            $credentials = base64_decode(substr($authHeader, 6));
            [$clientId, $clientSecret] = explode(':', $credentials, 2) + [null, null];
        } else {
            $clientId = $request->input('client_id');
            $clientSecret = $request->input('client_secret');
        }

        if ($clientId !== config('wiki.oauth_client_id') || $clientSecret !== config('wiki.oauth_client_secret')) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        if ($request->input('grant_type') !== 'authorization_code') {
            return response()->json(['error' => 'unsupported_grant_type'], 400);
        }

        $code = $request->input('code');
        $data = Cache::get("wiki:oauth:code:{$code}");

        if (! $data) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        Cache::forget("wiki:oauth:code:{$code}");

        /** @var User|null $user */
        $user = User::find($data['user_id']);
        if (! $user) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $accessToken = Str::random(128);
        Cache::put("wiki:oauth:token:{$accessToken}", $user->id, self::TOKEN_TTL);

        return response()->json([
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_TTL,
            'refresh_token' => Str::random(64),
        ]);
    }

    public function user(Request $request): JsonResponse
    {
        $authHeader = $request->header('Authorization', '');
        if (! str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        $token = substr($authHeader, 7);

        $userId = Cache::get("wiki:oauth:token:{$token}");
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                return $this->userResponse($user);
            }
        }

        $payload = $this->decodeJwt($token);
        if ($payload) {
            $user = User::find($payload['sub'] ?? null);
            if ($user) {
                return $this->userResponse($user);
            }
        }

        return response()->json(['error' => 'invalid_token'], 401);
    }

    private function userResponse(User $user): JsonResponse
    {
        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'displayName' => $user->name,
            'picture' => $user->avatar_url,
            'groups' => [$user->role->value],
        ]);
    }

    private function generateJwt(User $user): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $time = time();
        $payload = json_encode([
            'sub' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role->value,
            'iat' => $time,
            'exp' => $time + self::TOKEN_TTL,
            'aud' => 'urn:wiki.js',
            'iss' => config('app.url'),
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", config('wiki.oauth_secret'), true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    private function decodeJwt(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (! $payload || ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        $signature = hash_hmac('sha256', "{$parts[0]}.{$parts[1]}", config('wiki.oauth_secret'), true);
        $expectedSig = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

        if (! hash_equals($expectedSig, $parts[2])) {
            return null;
        }

        return $payload;
    }

    private function base64UrlDecode(string $data): string
    {
        $base64 = str_replace(['-', '_'], ['+', '/'], $data);
        $padding = 4 - (strlen($base64) % 4);
        if ($padding !== 4) {
            $base64 .= str_repeat('=', $padding);
        }

        return base64_decode($base64);
    }
}
