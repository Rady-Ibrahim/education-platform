<?php

namespace App\Modules\Content\Services;

class BunnyStreamService
{
    public function embedUrl(string $videoId, ?int $ttlSeconds = null): string
    {
        $libraryId = config('bunny.library_id');
        $base = rtrim((string) config('bunny.embed_base_url'), '/');
        $ttl = $ttlSeconds ?? (int) config('bunny.token_ttl_seconds', 3600);
        $expires = now()->timestamp + $ttl;
        $token = $this->makeToken($videoId, $expires);

        $query = http_build_query([
            'token' => $token,
            'expires' => $expires,
            'autoplay' => 'false',
        ]);

        return "{$base}/{$libraryId}/{$videoId}?{$query}";
    }

    public function makeToken(string $videoId, int $expires): string
    {
        $securityKey = (string) config('bunny.token_auth_key');

        return hash('sha256', $securityKey.$videoId.$expires);
    }

    public function isConfigured(): bool
    {
        return filled(config('bunny.library_id')) && filled(config('bunny.token_auth_key'));
    }
}
