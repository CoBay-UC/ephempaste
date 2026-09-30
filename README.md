# EphemPaste

EphemPaste is a small, self-hosted temporary text-sharing application built with PHP 8.3, PHP-FPM, Nginx, PostgreSQL, Bootstrap 5.3, JavaScript, and Docker Compose.

A paste receives a short random URL such as:

```text
https://text.example.com/aB3-_x
```

Pastes may optionally be protected by a password and are automatically removed after a configurable retention period.

## Features

- Object-oriented PHP backend.
- Nginx + PHP-FPM runtime.
- PostgreSQL storage with atomic primary-key collision protection.
- Configurable random share-code length; default `6`.
- URL-safe 66-character default code alphabet.
- Maximum paste size of 5,000 characters.
- Optional password protection.
- Per-paste random salt with SHA-256 password hashing.
- Constant-time password-hash comparison.
- Configurable expiry; default `72` hours.
- Dedicated lightweight cleanup service.
- CSRF protection on form submissions.
- HTML output escaping.
- Non-cacheable application responses.
- Basic browser security headers and Content Security Policy.
- Production errors written to Docker logs while browser responses remain generic.
- Bootstrap 5.3 responsive interface with a centered modern paste panel.
- Clipboard buttons for generated URLs and displayed paste content.
- `.dockerignore` protection so `.env` secrets are not baked into the image.

## Configuration Design

EphemPaste deliberately uses one source of truth for each setting.

Database configuration exists only as:

```env
DB_HOST=database
DB_PORT=5432
DB_NAME=ephempaste
DB_USER=ephempaste
DB_PASSWORD=CHANGE_ME
```

The PostgreSQL image itself requires `POSTGRES_DB`, `POSTGRES_USER`, and `POSTGRES_PASSWORD`. Docker Compose derives those automatically from `DB_NAME`, `DB_USER`, and `DB_PASSWORD`. Do **not** add duplicate `POSTGRES_*` settings to `.env`.

## Quick Start

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env`. At minimum, set a strong database password and the correct public URL:

```env
APP_NAME=EphemPaste
APP_BASE_URL=http://example.com
APP_PORT=17010
APP_ENV=production
APP_DEBUG=false

DB_HOST=database
DB_PORT=5432
DB_NAME=ephempaste
DB_USER=ephempaste
DB_PASSWORD=REPLACE_WITH_A_RANDOM_PASSWORD
```

Then build and start the application:

```bash
docker compose up -d --build
```

View service state:

```bash
docker compose ps
```

View application logs:

```bash
docker compose logs -f app
```

## Environment Variables

| Variable | Default/example | Purpose |
| --- | --- | --- |
| `APP_NAME` | `EphemPaste` | Display name |
| `APP_BASE_URL` | `http://localhost:8080` | Public base URL used when generating links |
| `APP_PORT` | `8080` | Host port published to Nginx |
| `APP_ENV` | `production` | Environment label |
| `APP_DEBUG` | `false` | Show exception messages in browser when enabled |
| `PASTE_CODE_LENGTH` | `6` | Random share-code length, 4–32 |
| `PASTE_MAX_LENGTH` | `5000` | Maximum paste size, capped at 5,000 |
| `PASTE_LIFETIME_HOURS` | `72` | Retention period, 1–720 hours |
| `PASTE_CODE_CHARSET` | 66 URL-safe characters | Character pool used for generated codes |
| `CLEANUP_INTERVAL_SECONDS` | `300` | Delay between expiry cleanup passes |
| `DB_HOST` | `database` | PostgreSQL hostname |
| `DB_PORT` | `5432` | PostgreSQL port |
| `DB_NAME` | `ephempaste` | Database name |
| `DB_USER` | `ephempaste` | Database user |
| `DB_PASSWORD` | required | Database password |

Environment values are trimmed by the PHP configuration loader. Invalid booleans and out-of-range numeric values cause a clear startup/request error in Docker logs instead of being silently accepted.


## Branding / Header Image

The interface uses a full-width panel header image at:

```text
/public/images/header.png
```

A placeholder banner is included. Replace that file with your own artwork to brand the application without changing any PHP or CSS. The layout automatically scales the image to the panel width and crops it responsively.

## Password Storage

Password protection is intentionally lightweight, as designed for this application.

Each protected paste receives a cryptographically random 16-byte salt. The application stores:

```text
SHA-256(salt + password)
```

The salt is stored alongside the hash. Verification uses PHP's `hash_equals()` for constant-time hash comparison.

Passwords are never stored in plaintext.

## Share-Code Collision Handling

There is no separate "used codes" table and no pre-insert lookup.

`pastes.code` is the PostgreSQL primary key. Creation works as follows:

1. PHP generates a cryptographically random code using `random_int()`.
2. The application attempts the database insert.
3. PostgreSQL atomically accepts the code or returns unique-constraint error `23505`.
4. On a collision, EphemPaste generates another code and retries.

This avoids a check-then-insert race condition and keeps the implementation simple.

## Expiry and Cleanup

A paste receives `expires_at` when inserted. Reads only return rows whose expiration is still in the future.

The `cleanup` Compose service periodically executes:

```sql
DELETE FROM pastes WHERE expires_at <= NOW();
```

The expiration column is indexed for efficient cleanup.

## Logging and Errors

When `APP_DEBUG=false`, unexpected browser errors intentionally show only:

```text
Application Error
The application encountered an unexpected error.
```

The real exception, file, line, and stack trace are written to the container's stderr and can be viewed with:

```bash
docker compose logs app --tail=100
```

Nginx access and error logs are also sent to Docker stdout/stderr.

For troubleshooting only, `APP_DEBUG=true` exposes the exception message to the browser. Do not leave debug mode enabled on a public instance.

## PostgreSQL Initialization and Existing Volumes

The SQL file in `sql/001-schema.sql` is executed by the PostgreSQL image only when its data volume is initialized for the first time.

Changing `DB_NAME`, `DB_USER`, the initialization SQL, or PostgreSQL initialization settings does not retroactively rebuild an existing database volume.

For a disposable/test installation where existing data can be deleted:

```bash
docker compose down -v
docker compose up -d --build
```

**Warning:** `docker compose down -v` permanently deletes the PostgreSQL volume and all stored pastes.

For an installation containing data that must be retained, use a database migration instead of deleting the volume.

## Updating the Application

For normal source/image changes:

```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```

A no-cache rebuild is not required for every update, but is useful when verifying that an older web-server image is not being reused.

## Reverse Proxy / HTTPS

The application listens on container port `80`; only the host-facing port is configurable:

```yaml
ports:
  - "${APP_PORT:-8080}:80"
```

For a public deployment, place EphemPaste behind an HTTPS reverse proxy and set:

```env
APP_BASE_URL=https://text.example.com
```

When `APP_BASE_URL` uses HTTPS, EphemPaste marks its session cookie `Secure` automatically.

## Security Notes

EphemPaste is intentionally small, but the package includes several low-cost protections:

- CSRF tokens.
- `HttpOnly` and `SameSite=Lax` sessions.
- `Secure` session cookies when the configured public URL is HTTPS.
- `Cache-Control: no-store` for temporary text.
- `X-Content-Type-Options`, `X-Frame-Options`, and `Referrer-Policy` headers.
- Restrictive Content Security Policy.
- Prepared SQL statements.
- Escaped HTML output.
- Database-enforced uniqueness.
- `.env` excluded from both Git and Docker build context.

If the service is exposed publicly, rate limiting at the reverse proxy is recommended, particularly for nonexistent share-code requests and paste creation.

## Front-End Dependency

Bootstrap 5.3 CSS is currently loaded from jsDelivr. The application's own JavaScript and CSS are served locally. If the service must work without external network access, vendor Bootstrap CSS into `public/assets/` and update the view templates.
