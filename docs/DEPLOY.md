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

## CI / CD

### CI (tests)

Push/PR runs PHPUnit via GitHub Actions (`.github/workflows/tests.yml`).

Vite assets are built in CI so Blade `@vite` views do not fail with a missing `public/build/manifest.json`.

### CD (deploy)

`.github/workflows/deploy.yml` deploys after tests pass:

| Trigger | Environment | Branch on server |
|---------|-------------|------------------|
| Push to `develop` | `staging` | `develop` |
| Push to `main` / `master` | `production` | same branch |
| Manual (`workflow_dispatch`) | chosen in UI | `develop` or `main` |

Flow: **Dev → Staging → Prod** (tests must pass before SSH deploy).

#### 1) Create GitHub Environments

Repo → **Settings → Environments** → create:

- `staging`
- `production` (optional: required reviewers)

#### 2) Add secrets per environment

| Secret | Example |
|--------|---------|
| `SSH_HOST` | `192.0.2.10` or `staging.example.com` |
| `SSH_USER` | `deploy` |
| `SSH_PRIVATE_KEY` | Full private key (PEM) for the deploy user |
| `SSH_PORT` | `22` (optional; defaults to 22) |
| `DEPLOY_PATH` | `/var/www/education-platform` |
| `APP_URL` | `https://staging.example.com` |

Use **different** values for `staging` vs `production`.

#### 3) One-time server prep

On the VPS (as the deploy user):

```bash
# Clone once
sudo mkdir -p /var/www/education-platform
sudo chown -R deploy:deploy /var/www/education-platform
git clone git@github.com:YOUR_ORG/education-platform.git /var/www/education-platform
cd /var/www/education-platform
git checkout develop   # or main for production

# App env (never commit)
cp .env.example .env
nano .env              # DB, Redis, Mailgun, Bunny, APP_KEY, etc.

composer install --no-dev
npm ci && npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
```

Ensure:

- PHP 8.2+, Composer, Node 20+, MySQL, Redis are installed
- Deploy user can `git pull` (deploy key or HTTPS token)
- Web server points at `public/`
- Supervisor runs `queue:work` + scheduler (see Queue worker above)

#### 4) Deploy key (GitHub → server)

If the repo is private, add a read-only deploy key on the server and to the GitHub repo, **or** use a machine user SSH key that can clone/pull.

#### 5) Verify

1. Push to `develop` → Actions → **Deploy** → staging SSH steps succeed
2. `GET /up` returns 200
3. After staging is healthy, merge to `main` for production

## Soft launch checklist

- [ ] Migrations applied
- [ ] `php artisan config:cache` + `route:cache`
- [ ] Queue + scheduler running
- [ ] Admin user seeded / created
- [ ] Mailgun domain verified
- [ ] Bunny token auth verified with one video lesson
- [ ] `/up` returns 200
- [ ] Backup job verified
