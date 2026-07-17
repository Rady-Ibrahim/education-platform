<?php

namespace App\Modules\Content\Services;

use InvalidArgumentException;
use RuntimeException;

class BunnyStreamService
{
    public function embedUrl(string $videoId, ?int $ttlSeconds = null): string
    {
        $this->assertConfigured();

        $videoId = trim($videoId);
        if ($videoId === '' || preg_match('/[^a-zA-Z0-9\-_]/', $videoId)) {
            throw new InvalidArgumentException('Bunny video id غير صالح.');
        }

        $libraryId = (string) config('bunny.library_id');
        $base = rtrim((string) config('bunny.embed_base_url'), '/');
        $ttl = $this->resolveTtl($ttlSeconds);
        $expires = now()->timestamp + $ttl;
        $token = $this->makeToken($videoId, $expires);

        $query = http_build_query([
            'token' => $token,
            'expires' => $expires,
            'autoplay' => 'false',
        ]);

        return "{$base}/{$libraryId}/{$videoId}?{$query}";
    }

    /**
     * رابط تشغيل مباشر عبر CDN مع Token Auth (للمحتوى المحمي خارج الـ iframe عند الحاجة).
     */
    public function signedCdnUrl(string $videoId, string $pathSuffix = 'playlist.m3u8', ?int $ttlSeconds = null): string
    {
        $this->assertConfigured();

        $hostname = config('bunny.cdn_hostname');
        if (! filled($hostname)) {
            throw new RuntimeException('BUNNY_CDN_HOSTNAME غير مضبوط.');
        }

        $videoId = trim($videoId);
        $ttl = $this->resolveTtl($ttlSeconds);
        $expires = now()->timestamp + $ttl;
        $token = $this->makeToken($videoId, $expires);
        $host = rtrim((string) $hostname, '/');

        return "https://{$host}/{$videoId}/{$pathSuffix}?token={$token}&expires={$expires}";
    }

    public function makeToken(string $videoId, int $expires): string
    {
        $securityKey = (string) config('bunny.token_auth_key');

        if ($securityKey === '') {
            throw new RuntimeException('BUNNY_TOKEN_AUTH_KEY غير مضبوط.');
        }

        return hash('sha256', $securityKey.$videoId.$expires);
    }

    public function isConfigured(): bool
    {
        return filled(config('bunny.library_id')) && filled(config('bunny.token_auth_key'));
    }

    public function assertConfigured(): void
    {
        if ($this->isConfigured()) {
            return;
        }

        if (app()->environment('production') || config('bunny.require_config')) {
            throw new RuntimeException('إعدادات Bunny Stream إلزامية في بيئة الإنتاج.');
        }

        throw new RuntimeException('إعدادات Bunny غير مكتملة (library_id / token_auth_key).');
    }

    private function resolveTtl(?int $ttlSeconds): int
    {
        $requested = $ttlSeconds ?? (int) config('bunny.token_ttl_seconds', 3600);
        $max = (int) config('bunny.max_token_ttl_seconds', 7200);

        return max(60, min($requested, $max));
    }
}
