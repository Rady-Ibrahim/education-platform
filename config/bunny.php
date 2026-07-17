<?php

return [
    /*
    | Bunny Stream — Prod checklist:
    | 1) Enable Token Authentication on the Stream library
    | 2) Set BUNNY_LIBRARY_ID + BUNNY_TOKEN_AUTH_KEY (never commit secrets)
    | 3) Optionally set BUNNY_CDN_HOSTNAME for direct signed CDN playback
    | 4) Keep BUNNY_TOKEN_TTL short (900–3600s); max capped by max_token_ttl_seconds
    | 5) Set BUNNY_REQUIRE_CONFIG=true (or APP_ENV=production) so unsigned playback fails closed
    */
    'library_id' => env('BUNNY_LIBRARY_ID'),
    'cdn_hostname' => env('BUNNY_CDN_HOSTNAME'),
    'token_auth_key' => env('BUNNY_TOKEN_AUTH_KEY'),
    'embed_base_url' => env('BUNNY_EMBED_BASE_URL', 'https://iframe.mediadelivery.net/embed'),
    'token_ttl_seconds' => (int) env('BUNNY_TOKEN_TTL', 3600),
    'max_token_ttl_seconds' => (int) env('BUNNY_MAX_TOKEN_TTL', 7200),
    'require_config' => (bool) env('BUNNY_REQUIRE_CONFIG', false),
];
