# EphemPaste

EphemPaste is a small self-hosted paste service for sharing temporary text.

It runs with PHP 8.3, Nginx, PostgreSQL, and Docker Compose. Pastes get a short random URL, can optionally be password protected, and are automatically removed after their configured lifetime.

```text
https://text.example.com/aB3-_x
```

## Features

- Short random paste URLs
- Optional password protection
- Automatic expiry and cleanup
- Configurable paste lifetime
- 5,000 character paste limit
- PostgreSQL storage
- Responsive Bootstrap interface
- Copy-to-clipboard buttons
- Docker Compose deployment
- Replaceable header image

## Quick Start

Copy the example environment file:

```bash
cp .env.example .env
```

Edit `.env` and set at least the public URL and database password:

```env
APP_NAME=EphemPaste
APP_BASE_URL=https://text.example.com
APP_PORT=17010
APP_ENV=production
APP_DEBUG=false

DB_HOST=database
DB_PORT=5432
DB_NAME=ephempaste
DB_USER=ephempaste
DB_PASSWORD=CHANGE_ME
```

Start the stack:

```bash
docker compose up -d --build
```

Check it:

```bash
docker compose ps
```

Logs:

```bash
docker compose logs -f app
```

## Updating

```bash
git pull
docker compose down
docker compose up -d --build
```

If Docker seems to be hanging onto an old build:

```bash
docker compose down
docker compose build --no-cache
docker compose up -d
```

## Reverse Proxy

EphemPaste listens on port `80` inside the container. `APP_PORT` controls the port exposed on the Docker host.

For a public install, put it behind an HTTPS reverse proxy and set `APP_BASE_URL` to the external address:

```env
APP_BASE_URL=https://text.example.com
```

## Branding

The header image is:

```text
/public/images/header.png
```

Replace that file with your own image to change the branding.

## Notes

The PostgreSQL schema under `sql/` is only applied when a new database volume is created. Changing the initialization SQL later will not modify an existing database.

For a disposable install, you can rebuild the database from scratch with:

```bash
docker compose down -v
docker compose up -d --build
```

That deletes all stored pastes.

More detail about configuration, password storage, cleanup, logging, and security is in [docs/technical.md](docs/technical.md).
