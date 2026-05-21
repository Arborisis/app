<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'stripe' => [
        'public_key' => env('STRIPE_PUBLIC_KEY'),
        'secret_key' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'contact' => [
        'inbound_mail_token' => env('CONTACT_INBOUND_MAIL_TOKEN'),
    ],

    'python' => [
        'path' => env('PYTHON_PATH', 'python3'),
        'timeout' => env('PYTHON_TIMEOUT', 300),
    ],

    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:contact@arborisis.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'discord' => [
        'client_id' => env('DISCORD_CLIENT_ID'),
        'client_secret' => env('DISCORD_CLIENT_SECRET'),
        'redirect' => env('DISCORD_REDIRECT_URL', '/auth/discord/callback'),
        'bot_token' => env('DISCORD_BOT_TOKEN'),
        'guild_id' => env('DISCORD_GUILD_ID'),
        'bot_host' => env('DISCORD_BOT_HOST', '127.0.0.1'),
        'bot_port' => env('DISCORD_BOT_PORT', 3001),
        'internal_api_token' => env('DISCORD_INTERNAL_API_TOKEN'),
    ],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'voice_id' => env('ELEVENLABS_VOICE_ID'),
        'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'),
        'model' => env('ELEVENLABS_MODEL', 'eleven_multilingual_v2'),
        'output_format' => env('ELEVENLABS_OUTPUT_FORMAT', 'mp3_44100_192'),
        'stability' => env('ELEVENLABS_STABILITY', 0.55),
        'similarity_boost' => env('ELEVENLABS_SIMILARITY_BOOST', 0.75),
        'style' => env('ELEVENLABS_STYLE', 0.2),
        'use_speaker_boost' => env('ELEVENLABS_USE_SPEAKER_BOOST', true),
        'timeout' => env('ELEVENLABS_TIMEOUT', 120),
        'sound_model' => env('ELEVENLABS_SOUND_MODEL', 'eleven_text_to_sound_v2'),
        'sound_output_format' => env('ELEVENLABS_SOUND_OUTPUT_FORMAT', 'mp3_44100_192'),
        'sound_prompt_influence' => env('ELEVENLABS_SOUND_PROMPT_INFLUENCE', 0.45),
        'sound_timeout' => env('ELEVENLABS_SOUND_TIMEOUT', 120),
        'music_model' => env('ELEVENLABS_MUSIC_MODEL', 'music_v1'),
        'music_output_format' => env('ELEVENLABS_MUSIC_OUTPUT_FORMAT', 'mp3_44100_192'),
        'music_timeout' => env('ELEVENLABS_MUSIC_TIMEOUT', 300),
    ],

    'r2' => [
        'signing_key' => env('R2_SIGNING_KEY'),
    ],

    'analyzer' => [
        'url' => env('ANALYZER_URL'),
        'urls' => array_filter(array_map('trim', explode(',', env('ANALYZER_URLS', '')))),
        'secret' => env('ANALYZER_SECRET'),
        'internal_api_token' => env('ANALYZER_INTERNAL_API_TOKEN'),
        'max_duration' => env('AUDIO_MAX_DURATION', 600),
        'max_file_size_mb' => env('AUDIO_MAX_FILE_SIZE_MB', 500),
    ],

    'opensearch' => [
        'hosts' => array_filter(array_map('trim', explode(',', env('OPENSEARCH_HOSTS', 'http://localhost:9200')))),
        'username' => env('OPENSEARCH_USERNAME'),
        'password' => env('OPENSEARCH_PASSWORD'),
        'index_prefix' => env('OPENSEARCH_INDEX_PREFIX', 'arborisis'),
        'enabled' => env('OPENSEARCH_ENABLED', true),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'model' => env('OPENROUTER_MODEL', 'anthropic/claude-opus-4.7'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'max_tool_steps' => (int) env('OPENROUTER_MAX_TOOL_STEPS', 10),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 60),
    ],

    'brave_search' => [
        'api_key' => env('BRAVE_SEARCH_API_KEY'),
    ],

    'arborisis_agent' => [
        'worker_url' => env('ARBORISIS_AGENT_WORKER_URL'),
        'token' => env('ARBORISIS_AGENT_TOKEN'),
        'timeout' => env('ARBORISIS_AGENT_TIMEOUT', 45),
        'memory_disk' => env('ARBORISIS_AGENT_MEMORY_DISK', 'local'),
        'memory_path' => env('ARBORISIS_AGENT_MEMORY_PATH', 'agent-memory'),
        'rate_limit_per_minute' => (int) env('ARBORISIS_AGENT_RATE_LIMIT_PER_MINUTE', 6),
        'guest_rate_limit_per_minute' => (int) env('ARBORISIS_AGENT_GUEST_RATE_LIMIT_PER_MINUTE', 3),
        'daily_quota' => (int) env('ARBORISIS_AGENT_DAILY_QUOTA', 60),
        'guest_daily_quota' => (int) env('ARBORISIS_AGENT_GUEST_DAILY_QUOTA', 15),
    ],

    'nominatim' => [
        'enabled' => env('NOMINATIM_ENABLED', true),
        'url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org/search'),
        'timeout' => (int) env('NOMINATIM_TIMEOUT', 8),
        'user_agent' => env('NOMINATIM_USER_AGENT', env('APP_NAME', 'Arborisis').'/1.0'),
        'infer_sound_walk_waypoints' => env('NOMINATIM_INFER_SOUND_WALK_WAYPOINTS', true),
    ],

    'osrm' => [
        'enabled' => env('OSRM_ENABLED', true),
        'base_url' => env('OSRM_BASE_URL', 'https://routing.openstreetmap.de/routed-foot'),
        'profile' => env('OSRM_PROFILE', 'foot'),
        'timeout' => (int) env('OSRM_TIMEOUT', 10),
        'user_agent' => env('OSRM_USER_AGENT', env('APP_NAME', 'Arborisis').'/1.0'),
    ],

    'open_meteo' => [
        'enabled' => env('OPEN_METEO_ENABLED', true),
        'archive_url' => env('OPEN_METEO_ARCHIVE_URL', 'https://archive-api.open-meteo.com/v1/archive'),
        'forecast_url' => env('OPEN_METEO_FORECAST_URL', 'https://api.open-meteo.com/v1/forecast'),
        'timeout' => env('OPEN_METEO_TIMEOUT', 8),
        'current_refresh_minutes' => env('OPEN_METEO_CURRENT_REFRESH_MINUTES', 15),
    ],

];
