# Deploy Guide — Education Platform

## Environments

| Env | Purpose |
|-----|---------|
| Local | Dev + Feature tests |
| Staging | Mailgun + Bunny + Redis + queue worker |
| Production | Same as Staging + HTTPS + backups + Sentry |

Flow: **Dev → Staging → Prod** (never push untested code to Prod).

## Required env (Staging/Prod)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
# ... DB credentials

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

MAIL_MAILER=mailgun
MAILGUN_DOMAIN=
MAILGUN_SECRET=

BUNNY_LIBRARY_ID=
BUNNY_TOKEN_AUTH_KEY=
BUNNY_CDN_HOSTNAME=
BUNNY_REQUIRE_CONFIG=true
BUNNY_TOKEN_TTL=3600

FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

SENTRY_LARAVEL_DSN=
SENTRY_TRACES_SAMPLE_RATE=0.1

# Payments / Platform billing
PAYMENTS_STUDENT_VODAFONE=false
PLATFORM_TRIAL_DAYS=90
PLATFORM_MONTHLY_FEE=200
PLATFORM_PERIOD_DAYS=30
BUNNY_STREAM_API_KEY=
```

Secrets must never be committed. Use host env / vault.

## Payments checklist (Prod)

1. أدمن يضبط رقم فودافون كاش المنصة من `/admin/platform`
2. كل مدرس يضبط رقم فودافون كاش طلابه من البروفايل / المدفوعات
3. راجع `docs/PAYMENTS.md` (كاش + ولي أمر → مدرس، ومنصة → أدمن)
4. `php artisan storage:link` لإثباتات الدفع إن كان القرص `public`

## Health check

- Built-in: `GET /up` (Laravel health)
- Use this for load balancer / uptime monitors

## Queue worker

Notifications are queued (`ShouldQueue`). Run:

```bash
php artisan queue:work --tries=3 --timeout=90
```

Supervisor example: keep `queue:work` and `schedule:work` (or cron `* * * * * php artisan schedule:run`) always running.

## Backups

1. **Daily** MySQL dump (retain 7–14 days on-server)
2. **Weekly** copy off-site (S3 / another region)
3. Test restore once per quarter (Disaster Recovery)

Example cron:

```bash
0 2 * * * mysqldump -uUSER -pPASS education_platform | gzip > /backups/db-$(date +\%F).sql.gz
```

## Bunny Stream (Prod)

1. Enable Token Authentication on the library
2. Set `BUNNY_REQUIRE_CONFIG=true`
3. Keep TTL short (≤ 3600s)
4. Never expose raw `bunny_video_id` in public HTML without signed embed

## Attachments

Local/public disk is fine for Local. Staging+ should use S3-compatible disk; downloads remain via temporary signed routes.

## Sentry

1. `composer require sentry/sentry-laravel` (if not installed)
2. Set `SENTRY_LARAVEL_DSN`
3. Verify with `php artisan sentry:test` (when package present)

## CI

Push/PR runs PHPUnit via GitHub Actions (`.github/workflows/tests.yml`).

## Soft launch checklist

- [ ] Migrations applied
- [ ] `php artisan config:cache` + `route:cache`
- [ ] Queue + scheduler running
- [ ] Admin user seeded / created
- [ ] Mailgun domain verified
- [ ] Bunny token auth verified with one video lesson
- [ ] `/up` returns 200
- [ ] Backup job verified
