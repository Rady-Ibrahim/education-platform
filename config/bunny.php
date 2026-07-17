<?php

return [
    'library_id' => env('BUNNY_LIBRARY_ID'),
    'cdn_hostname' => env('BUNNY_CDN_HOSTNAME'),
    'token_auth_key' => env('BUNNY_TOKEN_AUTH_KEY'),
    'embed_base_url' => env('BUNNY_EMBED_BASE_URL', 'https://iframe.mediadelivery.net/embed'),
    'token_ttl_seconds' => (int) env('BUNNY_TOKEN_TTL', 3600),
];
