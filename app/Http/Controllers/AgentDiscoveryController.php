<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class AgentDiscoveryController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const SKILL_DOCUMENTS = [
        'discover-arborisis-api' => <<<'MARKDOWN'
# Discover Arborisis API

Use the API catalog at `/.well-known/api-catalog` to find public API documentation,
the OpenAPI service description, health status, OAuth metadata, and protected
resource metadata for Arborisis.
MARKDOWN,
        'browse-nature-sounds' => <<<'MARKDOWN'
# Browse Nature Sounds

Use the public sound and map APIs to search nature field recordings, inspect
public metadata, and open human-facing pages for playback and creator context.
Exact GPS coordinates are never exposed publicly.
MARKDOWN,
        'use-arborisis-webmcp' => <<<'MARKDOWN'
# Use Arborisis WebMCP

When the browser exposes `navigator.modelContext`, Arborisis registers safe page
navigation and search tools for agents. These tools do not expose private keys or
exact GPS coordinates.
MARKDOWN,
    ];

    public function apiCatalog(): JsonResponse
    {
        return $this->linkset([
            'linkset' => [
                [
                    'anchor' => url('/api'),
                    'service-desc' => [
                        ['href' => url('/openapi.json'), 'type' => 'application/vnd.oai.openapi+json'],
                    ],
                    'service-doc' => [
                        ['href' => url('/docs/api'), 'type' => 'text/html'],
                    ],
                    'status' => [
                        ['href' => url('/api/health'), 'type' => 'application/json'],
                    ],
                    'oauth-authorization-server' => [
                        ['href' => url('/.well-known/oauth-authorization-server'), 'type' => 'application/json'],
                    ],
                    'oauth-protected-resource' => [
                        ['href' => url('/.well-known/oauth-protected-resource'), 'type' => 'application/json'],
                    ],
                ],
            ],
        ]);
    }

    public function openApi(): JsonResponse
    {
        return response()->json([
            'openapi' => '3.1.0',
            'info' => [
                'title' => config('app.name', 'Arborisis').' Public API',
                'version' => '1.0.0',
                'description' => 'Discovery document for public Arborisis APIs. Exact GPS coordinates are not exposed.',
            ],
            'servers' => [
                ['url' => url('/api')],
            ],
            'paths' => [
                '/health' => [
                    'get' => [
                        'summary' => 'API health status',
                        'responses' => [
                            '200' => ['description' => 'Healthy'],
                            '503' => ['description' => 'Degraded'],
                        ],
                    ],
                ],
                '/map/sounds' => [
                    'get' => [
                        'summary' => 'Public map sounds with approximate coordinates',
                        'responses' => [
                            '200' => ['description' => 'A public sound map payload'],
                        ],
                    ],
                ],
                '/map/sounds/search' => [
                    'get' => [
                        'summary' => 'Search public map sounds',
                        'parameters' => [
                            [
                                'name' => 'q',
                                'in' => 'query',
                                'schema' => ['type' => 'string'],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Search results'],
                        ],
                    ],
                ],
                '/radio/now-playing' => [
                    'get' => [
                        'summary' => 'Current Arborisis radio track',
                        'responses' => [
                            '200' => ['description' => 'Now-playing metadata'],
                        ],
                    ],
                ],
                '/blog' => [
                    'get' => [
                        'summary' => 'Public blog posts',
                        'responses' => [
                            '200' => ['description' => 'Published posts'],
                        ],
                    ],
                ],
                '/sounds/{sound}/analysis' => [
                    'get' => [
                        'summary' => 'Authenticated sound analysis details',
                        'security' => [['sanctum' => []]],
                        'parameters' => [
                            [
                                'name' => 'sound',
                                'in' => 'path',
                                'required' => true,
                                'schema' => ['type' => 'string'],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Analysis data'],
                            '401' => ['description' => 'Authentication required'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'securitySchemes' => [
                    'sanctum' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Laravel Sanctum token',
                    ],
                ],
            ],
        ])->header('Content-Type', 'application/vnd.oai.openapi+json');
    }

    public function apiDocs(): Response
    {
        return response(<<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Arborisis API Documentation</title>
</head>
<body>
    <main>
        <h1>Arborisis API Documentation</h1>
        <p>Agents should start with <a href="/.well-known/api-catalog">the API catalog</a> and <a href="/openapi.json">the OpenAPI description</a>.</p>
        <p>Public endpoints expose nature sounds, approximate map positions, radio metadata, blog posts, and service health. Protected endpoints use the site's authenticated session or Laravel Sanctum tokens where enabled.</p>
    </main>
</body>
</html>
HTML)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function oauthAuthorizationServer(): JsonResponse
    {
        return response()->json([
            'issuer' => url('/'),
            'authorization_endpoint' => url('/login'),
            'token_endpoint' => url('/oauth/token'),
            'jwks_uri' => url('/.well-known/jwks.json'),
            'grant_types_supported' => [],
            'response_types_supported' => [],
            'scopes_supported' => $this->scopes(),
            'service_documentation' => url('/docs/api'),
        ]);
    }

    public function openIdConfiguration(): JsonResponse
    {
        return response()->json([
            'issuer' => url('/'),
            'authorization_endpoint' => url('/login'),
            'token_endpoint' => url('/oauth/token'),
            'jwks_uri' => url('/.well-known/jwks.json'),
            'grant_types_supported' => [],
            'response_types_supported' => [],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => [],
            'scopes_supported' => $this->scopes(),
        ]);
    }

    public function jwks(): JsonResponse
    {
        return response()->json(['keys' => []]);
    }

    public function unsupportedOAuthToken(): JsonResponse
    {
        return response()->json([
            'error' => 'unsupported_grant_type',
            'error_description' => 'Arborisis does not currently issue OAuth access tokens from this endpoint.',
        ], 501);
    }

    public function protectedResource(): JsonResponse
    {
        return response()->json([
            'resource' => url('/api'),
            'authorization_servers' => [
                url('/'),
            ],
            'scopes_supported' => $this->scopes(),
            'bearer_methods_supported' => ['header'],
            'resource_documentation' => url('/docs/api'),
        ]);
    }

    public function mcpServerCard(): JsonResponse
    {
        return response()->json([
            '$schema' => 'https://modelcontextprotocol.io/schemas/server-card.json',
            'serverInfo' => [
                'name' => config('app.name', 'Arborisis'),
                'version' => '1.0.0',
                'description' => 'Browser-discoverable Arborisis tools for nature sound exploration.',
            ],
            'transport' => [
                'type' => 'webmcp',
                'endpoint' => url('/'),
            ],
            'capabilities' => [
                'tools' => [
                    'browse_public_sounds',
                    'search_public_sounds',
                    'open_sound_map',
                    'open_radio',
                ],
            ],
            'links' => [
                ['rel' => 'service-desc', 'href' => url('/openapi.json')],
                ['rel' => 'service-doc', 'href' => url('/docs/api')],
            ],
        ]);
    }

    public function agentSkillsIndex(): JsonResponse
    {
        $skills = collect(self::SKILL_DOCUMENTS)
            ->map(fn (string $document, string $name): array => [
                'name' => $name,
                'type' => 'agent-skill',
                'description' => Str::of($document)->after("\n")->trim()->toString(),
                'url' => url('/.well-known/agent-skills/'.$name.'.md'),
                'sha256' => hash('sha256', $document),
            ])
            ->values()
            ->all();

        return response()->json([
            '$schema' => 'https://agentskills.io/schemas/agent-skills-index/v0.2.0.json',
            'skills' => $skills,
        ]);
    }

    public function agentSkill(string $skill): Response
    {
        abort_unless(array_key_exists($skill, self::SKILL_DOCUMENTS), 404);

        return response(self::SKILL_DOCUMENTS[$skill])
            ->header('Content-Type', 'text/markdown; charset=UTF-8');
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function linkset(array $payload): JsonResponse
    {
        return response()
            ->json($payload)
            ->header('Content-Type', 'application/linkset+json');
    }

    /**
     * @return array<int, string>
     */
    private function scopes(): array
    {
        return [
            'sounds:read',
            'map:read',
            'radio:read',
            'analysis:read',
            'profile:read',
        ];
    }
}
