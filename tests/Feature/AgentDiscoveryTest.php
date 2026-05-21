<?php

declare(strict_types=1);

it('adds agent discovery link headers to the homepage', function () {
    $response = $this->get('/');

    $response->assertOk();

    $linkHeaders = implode(', ', $response->headers->all('Link'));

    expect($linkHeaders)
        ->toContain('</.well-known/api-catalog>; rel="api-catalog"')
        ->toContain('</openapi.json>; rel="service-desc"')
        ->toContain('</docs/api>; rel="service-doc"')
        ->toContain('</api/health>; rel="status"')
        ->toContain('</.well-known/oauth-protected-resource>; rel="oauth-protected-resource"')
        ->toContain('</.well-known/mcp/server-card.json>; rel="mcp-server-card"')
        ->toContain('</.well-known/agent-skills/index.json>; rel="agent-skills"');
});

it('publishes an api catalog linkset', function () {
    $response = $this->get('/.well-known/api-catalog');

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/linkset+json');

    expect($response->json('linkset.0.anchor'))->toBe(url('/api'));
    expect($response->json('linkset.0.service-desc.0.href'))->toBe(url('/openapi.json'));
    expect($response->json('linkset.0.service-doc.0.href'))->toBe(url('/docs/api'));
    expect($response->json('linkset.0.status.0.href'))->toBe(url('/api/health'));
});

it('publishes oauth protected resource metadata', function () {
    $response = $this->get('/.well-known/oauth-protected-resource');

    $response
        ->assertOk()
        ->assertJsonPath('resource', url('/api'))
        ->assertJsonPath('authorization_servers.0', url('/'));

    expect($response->json('scopes_supported'))->toContain('sounds:read');
});

it('publishes oauth authorization server metadata without issuing tokens', function () {
    $this->get('/.well-known/oauth-authorization-server')
        ->assertOk()
        ->assertJsonPath('issuer', url('/'))
        ->assertJsonPath('authorization_endpoint', url('/login'))
        ->assertJsonPath('token_endpoint', url('/oauth/token'));

    $this->post('/oauth/token')
        ->assertStatus(501)
        ->assertJsonPath('error', 'unsupported_grant_type');
});

it('publishes an agent skills index', function () {
    $response = $this->get('/.well-known/agent-skills/index.json');

    $response
        ->assertOk()
        ->assertJsonStructure([
            '$schema',
            'skills' => [
                [
                    'name',
                    'type',
                    'description',
                    'url',
                    'sha256',
                ],
            ],
        ]);

    expect($response->json('skills.0.sha256'))->toMatch('/^[a-f0-9]{64}$/');
});

it('publishes an mcp server card', function () {
    $response = $this->get('/.well-known/mcp/server-card.json');

    $response
        ->assertOk()
        ->assertJsonPath('transport.type', 'webmcp');

    expect($response->json('capabilities.tools'))->toContain('search_public_sounds');
});
