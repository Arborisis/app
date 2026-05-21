<?php

declare(strict_types=1);

use App\Services\Blog\BlogHtmlSanitizer;

it('preserves french utf8 characters while sanitizing blog html', function (): void {
    $html = '<article><p>Une heure exacte où la forêt s’ouvre — déjà écoutée.</p><script>alert("x")</script></article>';

    $clean = app(BlogHtmlSanitizer::class)->sanitize($html);

    expect($clean)
        ->toContain('où la forêt s’ouvre — déjà écoutée')
        ->not->toContain('oÃ¹')
        ->not->toContain('forÃªt')
        ->not->toContain('<script>');
});
