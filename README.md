# PL Validator API (RapidAPI-ready)

Tiny PHP API to validate and normalize:
- **NIP** (10 digits, checksum)
- **REGON** (9 or 14 digits, checksum)
- **IBAN** (MOD 97-10, works for PL and other IBAN countries)

No database, no auth, no UI — perfect for your first RapidAPI publication.

## Endpoints

Base path: `/v1`

- `GET  /v1/health`
- `POST /v1/normalize`
- `POST /v1/validate/nip`
- `POST /v1/validate/regon`
- `POST /v1/validate/iban`

Request body for POST:
```json
{ "value": "..." }
```

## Local run (without Docker)

Requirements: PHP 8.2+ and Composer

```bash
composer install
composer start
```

Server: http://localhost:8080

Example:
```bash
curl -X POST "http://localhost:8080/v1/validate/nip"   -H "Content-Type: application/json"   -d '{"value":"123-456-32-18"}'
```

## Run with Docker

```bash
docker compose up --build
```

Server: http://localhost:8080

## Tests

```bash
composer test
```

**📋 Dokumentacja testów:** Zobacz [TESTING.md](./TESTING.md) dla szczegółowej dokumentacji testów manualnych i automatycznych.

## Documentation

- **[TESTING.md](./TESTING.md)** - Kompletna dokumentacja testów dla QA
- **[RAPIDAPI_DEPLOYMENT.md](./RAPIDAPI_DEPLOYMENT.md)** - Krok po kroku wdrożenie do RapidAPI
- **[TUTORIAL.md](./TUTORIAL.md)** - Tutorial: Middleware i autoryzacja w Slim Framework

## OpenAPI (for RapidAPI)

OpenAPI spec is in `openapi.yaml`.

### How to use it in RapidAPI quickly

**📖 Pełna dokumentacja:** Zobacz [RAPIDAPI_DEPLOYMENT.md](./RAPIDAPI_DEPLOYMENT.md) dla szczegółowego przewodnika.

Szybki start:
1. Create a new API in RapidAPI (My APIs).
2. Import an API / Add endpoints using OpenAPI.
3. Paste content of `openapi.yaml` OR upload the file.
4. Replace `https://YOUR_PUBLIC_HOST` with your deployed base URL.

## Deploy notes (simple)
You need any public HTTPS hosting. Examples:
- Render / Railway / Fly.io
- Any VPS with Nginx reverse proxy + Docker

### Railway deployment
1. Connect your GitHub repository to Railway
2. Railway will auto-detect the Dockerfile in the root directory
3. The app automatically uses the PORT environment variable (Railway sets this)
4. Railway will provide a public URL - use this in your RapidAPI configuration

**Note:** The Dockerfile reads the PORT environment variable automatically. No additional configuration needed!

Once deployed, set the RapidAPI base URL to your public endpoint and you can test directly from RapidAPI console.

## License
MIT
