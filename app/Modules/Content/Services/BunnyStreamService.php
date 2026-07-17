<?php

namespace App\Modules\Content\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;

class BunnyStreamService
{
    public function embedUrl(string $videoId, ?int $ttlSeconds = null): string
    {
        $this->assertConfigured();

        $videoId = $this->assertValidVideoId($videoId);

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

        $videoId = $this->assertValidVideoId($videoId);
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

    public function canUpload(): bool
    {
        return $this->isConfigured() && filled(config('bunny.stream_api_key'));
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

    public function assertUploadConfigured(): void
    {
        if ($this->canUpload()) {
            return;
        }

        throw new RuntimeException('رفع الفيديو يتطلب BUNNY_STREAM_API_KEY مع إعدادات المكتبة.');
    }

    /**
     * إنشاء فيديو فارغ في Bunny Stream ثم رفع الملف إليه.
     */
    public function createAndUpload(string $title, UploadedFile|string $fileOrPath): string
    {
        $this->assertUploadConfigured();

        $path = $fileOrPath instanceof UploadedFile
            ? $fileOrPath->getRealPath()
            : $fileOrPath;

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            throw new InvalidArgumentException('ملف الفيديو غير موجود.');
        }

        $videoId = $this->createVideo($title);
        $this->uploadVideoFile($videoId, $path);

        return $videoId;
    }

    public function createVideo(string $title): string
    {
        $this->assertUploadConfigured();

        $libraryId = (string) config('bunny.library_id');
        $base = rtrim((string) config('bunny.api_base_url'), '/');

        try {
            $response = Http::withHeaders($this->apiHeaders())
                ->acceptJson()
                ->post("{$base}/library/{$libraryId}/videos", [
                    'title' => $title !== '' ? $title : 'Lesson video',
                ])
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('فشل إنشاء فيديو Bunny: '.$e->getMessage(), previous: $e);
        }

        $guid = (string) ($response->json('guid') ?? '');
        if ($guid === '') {
            throw new RuntimeException('Bunny لم يُرجع معرّف فيديو.');
        }

        return $guid;
    }

    public function uploadVideoFile(string $videoId, string $absolutePath): void
    {
        $this->assertUploadConfigured();
        $videoId = $this->assertValidVideoId($videoId);

        if (! is_file($absolutePath)) {
            throw new InvalidArgumentException('ملف الفيديو غير موجود.');
        }

        $libraryId = (string) config('bunny.library_id');
        $base = rtrim((string) config('bunny.api_base_url'), '/');
        $contents = file_get_contents($absolutePath);

        if ($contents === false) {
            throw new RuntimeException('تعذر قراءة ملف الفيديو.');
        }

        try {
            Http::withHeaders($this->apiHeaders() + [
                'Content-Type' => 'application/octet-stream',
            ])
                ->withBody($contents, 'application/octet-stream')
                ->put("{$base}/library/{$libraryId}/videos/{$videoId}")
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('فشل رفع الفيديو إلى Bunny: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * @return array<string, string>
     */
    private function apiHeaders(): array
    {
        return [
            'AccessKey' => (string) config('bunny.stream_api_key'),
        ];
    }

    private function assertValidVideoId(string $videoId): string
    {
        $videoId = trim($videoId);
        if ($videoId === '' || preg_match('/[^a-zA-Z0-9\-_]/', $videoId)) {
            throw new InvalidArgumentException('Bunny video id غير صالح.');
        }

        return $videoId;
    }

    private function resolveTtl(?int $ttlSeconds): int
    {
        $requested = $ttlSeconds ?? (int) config('bunny.token_ttl_seconds', 3600);
        $max = (int) config('bunny.max_token_ttl_seconds', 7200);

        return max(60, min($requested, $max));
    }
}
