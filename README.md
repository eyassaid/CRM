# CRM

Laravel API in `backend/`, Vue SPA in `frontend/`. Everything runs in Docker.

```
crm/
├── backend/            Laravel 13 API (Passport auth), served by php-fpm + nginx
├── frontend/           Vue 3 + Vite + Tailwind, vue-router, pinia, axios
├── docker/
│   ├── Dockerfile      multi-stage: backend images + frontend build/prod image
│   ├── nginx/          backend.conf (Laravel), frontend.conf (production SPA)
│   ├── php/php.ini
│   └── scripts/        setup-backend, frontend-dev
└── docker-compose.yml
```

## First run

```sh
docker compose --profile setup run --rm setup   # composer install, .env, key, migrations, Passport client
docker compose up -d --build
```

| Service      | URL                    |
|--------------|------------------------|
| Frontend     | http://localhost:5173  |
| Backend API  | http://localhost:8000/api |
| phpMyAdmin   | http://localhost:8080  |

The frontend container runs `npm install` on its first start, which can take a minute.

## How the two apps talk

The SPA calls `/api/...` on its own origin. In development, Vite proxies those requests to the
backend nginx (`frontend/vite.config.js`); in production, `docker/nginx/frontend.conf` does the
same. The browser never makes a cross-origin request, so no CORS setup is needed.

Auth uses Passport tokens: `POST /api/auth/login` returns `access_token`, which the SPA stores and
sends as `Authorization: Bearer <token>` (`frontend/src/api/http.js`).

## Everyday commands

```sh
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan test
docker compose run --rm --no-deps --entrypoint composer setup require vendor/package
docker compose exec frontend npm install some-package
docker compose logs -f frontend
```

## Production frontend image

```sh
docker build -f docker/Dockerfile --target frontend -t crm-frontend .
```

It serves the built SPA and forwards `/api`, `/oauth` and `/storage` to the `nginx` service.
