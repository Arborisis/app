<?php

declare(strict_types=1);

return [
    'ai_enabled' => env('BLOG_AI_ENABLED', true),
    'ai_model' => env('BLOG_AI_MODEL', 'anthropic/claude-opus-4.7'),
    'daily_at' => env('BLOG_DAILY_AT', '07:00'),
    'max_related_sounds' => 3,
    'max_related_creators' => 2,
    'fallback_topic' => 'L\'écoute comme art de vivre',
];
