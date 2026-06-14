# Yndx Review

Скелет: **Laravel API + Vue 3 SPA** с входом через Sanctum.

## Стек

- Laravel 13 + Sanctum (cookie-based SPA auth)
- Vue 3, Composition API, Vue Router, Bootstrap 5
- SQLite

## Локальная разработка (Docker)

```shell
docker compose up -d --build
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

- SPA: http://localhost:5173
- API: http://localhost:8000/api

## Демо-пользователь

| Email | Пароль |
|-------|--------|
| demo@example.com | password |

## Как происходит парсинг

