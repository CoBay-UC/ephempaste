# EphemPaste Technical Notes

This file keeps the implementation details out of the main README while still documenting the parts that matter when running or maintaining the service.

## Configuration

EphemPaste reads its application and database settings from `.env`.

Common settings:

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_NAME` | `EphemPaste` | Name shown in the UI |
| `APP_BASE_URL` | `http://localhost:8080` | Public URL used for generated links |
| `APP_PORT` | `8080` | Host port mapped to Nginx |
| `APP_ENV` | `production` | Environment label |
| `APP_DEBUG` | `false` | Shows exception details in the browser when enabled |
| `PASTE_CODE_LENGTH` | `6` | Generated code length, 4-32 |
| `PASTE_MAX_LENGTH` | `5000` | Maximum paste size |
| `PASTE_LIFETIME_HOURS` | `72` | Paste lifetime, 1-720 hours |
| `PASTE_CODE_CHARSET` | URL-safe characters | Character set used for generated codes |
| `CLEANUP_INTERVAL_SECONDS` | `300` | Delay between cleanup passes |
| `DB_HOST` | `database` | PostgreSQL host |
| `DB_PORT` | `5432` | PostgreSQL port |
| `DB_NAME` | `ephempaste` | Database name |
| `DB_USER` | `ephempaste` | Database user |
| `DB_PASSWORD` | required | Database password |

The application uses the `DB_*` variables above. Docker Compose passes the matching values to the PostgreSQL container, so there is no need to keep a second set of `POSTGRES_*` values in `.env`.

Invalid booleans or out-of-range numeric values should fail clearly instead of being silently accepted.

## Paste Codes

Paste codes are generated with a configurable length and URL-safe character set.

`pastes.code` is the PostgreSQL primary key. The application does not do a separate "does this code already exist?" lookup before inserting.

The flow is simply:

1. Generate a random code.
2. Try the insert.
3. If PostgreSQL returns unique-constraint error `23505`, generate another code and try again.

That keeps collision handling atomic and avoids a check-then-insert race.

## Password Protected Pastes

Password protection is optional.

Each protected paste gets a random 16-byte salt. The stored value is:

```text
SHA-256(salt + password)
```

The salt is stored alongside the hash and verification uses PHP's `hash_equals()`.

Passwords are not stored in plaintext.

This is meant as lightweight protection for temporary pastes, not as a replacement for a secrets manager or encrypted file-sharing system.

## Expiry and Cleanup

Each paste gets an `expires_at` value when it is created.

Expired rows are not returned by the application, even if the cleanup container has not removed them yet.

The cleanup service periodically runs:

```sql
DELETE FROM pastes WHERE expires_at <= NOW();
```

The expiration column is indexed so cleanup does not need to scan the whole table every time.

## Database Initialization

The SQL in `sql/001-schema.sql` is run by the PostgreSQL image only when the database volume is initialized for the first time.

Changing any of the following later does not rebuild an existing database automatically:

- `DB_NAME`
- `DB_USER`
- PostgreSQL initialization settings
- `sql/001-schema.sql`

For a test install where the existing data does not matter:

```bash
docker compose down -v
docker compose up -d --build
```

`docker compose down -v` permanently deletes the PostgreSQL volume and all stored pastes.

For an install with data you want to keep, use a migration instead.

## Logging and Debugging

With:

```env
APP_DEBUG=false
```

unexpected browser errors stay generic while the actual exception is written to container stderr.

View recent application errors with:

```bash
docker compose logs app --tail=100
```

Nginx access and error logs also go to Docker stdout/stderr.

For troubleshooting, `APP_DEBUG=true` can expose exception messages in the browser. It should not be left enabled on a public instance.

## Reverse Proxy and HTTPS

The application container listens on port `80`.

The host-facing port is controlled by `APP_PORT`, for example:

```env
APP_PORT=17010
```

A public install should normally sit behind a reverse proxy handling HTTPS.

Set the public URL accordingly:

```env
APP_BASE_URL=https://text.example.com
```

When `APP_BASE_URL` uses HTTPS, EphemPaste marks its session cookie `Secure` automatically.

## Security Notes

The application includes the usual protections for a small public-facing service:

- CSRF tokens on form submissions
- Prepared SQL statements
- Escaped HTML output
- `HttpOnly` session cookies
- `SameSite=Lax` session cookies
- `Secure` session cookies when HTTPS is configured
- `Cache-Control: no-store`
- Content Security Policy
- `X-Content-Type-Options`
- `X-Frame-Options`
- `Referrer-Policy`
- Database-enforced unique paste codes
- `.env` excluded from Git and the Docker build context

If the service is exposed publicly, rate limiting at the reverse proxy is a good idea, especially for paste creation and repeated requests for nonexistent codes.

## Front-End Files

The application's own CSS and JavaScript are served locally.

Bootstrap 5.3 CSS is currently loaded from jsDelivr. If the service needs to work without outside network access, download Bootstrap into `public/assets/` and update the templates to use the local copy.

The header image lives at:

```text
/public/images/header.png
```

It can be replaced without changing the PHP code.
