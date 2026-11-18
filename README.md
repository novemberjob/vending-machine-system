# VMS — Vending Machine System

A PHP 8.2+ application with a session-authenticated web UI and a JWT-protected REST API.
Products, users and transactions are stored in MySQL and accessed with PDO.

## Features

- Product CRUD (Admin only), seeded with Coke `3.990`, Pepsi `6.885`, Water `0.500`
- Atomic, transactional purchasing with price snapshots in the transaction log
- Pagination and sortable columns in the product list
- Session authentication with Argon2id password hashing, `Admin` / `User` roles and RBAC attributes
- CSRF-protected forms, server-side and client-side validation
- PHP 8 attribute routing, including SEO-friendly purchase URLs (`/products/1-coke/buy`)
- REST API under `/api/v1` secured with HS256 JWTs
- PHPUnit tests using DI and mocks (no database required)

## Requirements

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `openssl`, `dom`, `xml` and `zip`
- MySQL 8+ or MariaDB 10+

## Docker

The Docker setup uses three services, each with its own Dockerfile under `docker/`:

All builds use the repository root as the Docker build context, so the root `.dockerignore`
applies to all three images. Nginx is exposed on port `8000`. On first start the PHP-FPM
entrypoint (`docker/app/entrypoint.sh`) waits for MySQL, imports both migrations, seeds the
admin user, and then starts PHP-FPM.

```bash
# optional: override defaults before starting
# set JWT_SECRET, DB_PASS, DB_PORT, ADMIN_EMAIL, ADMIN_PASSWORD, APP_URL in your shell or a .env file

docker compose up -d --build
# open http://localhost:8000/products

docker compose logs -f app     # PHP-FPM / migrations / seeding
docker compose logs -f web     # Nginx access and error logs
docker compose down            # add -v to also remove the database volume
```

`DB_PORT` defaults to `3306`. It is used for the MySQL server port, the host port
mapping, the entrypoint's `mysql`/`mysqladmin` calls, and the app's database connection.
The PDO connection string is built from `DB_HOST`, `DB_PORT`, and `DB_NAME`;
no separate connection-string variable is needed.

The `app` container reads configuration from environment variables (they take precedence
over `.env`), so no local `.env` or MySQL installation is required for Docker usage.
Apache is no longer part of the setup.

## Setup

Open <http://localhost:8000/products>. Log in with `admin@example.com` / `admin12345`
to manage products, or register a normal `User` account to purchase products.
Only `User` accounts can purchase; `Admin` accounts can manage products.

## Routing

| Method | Path | Purpose | Access |
|---|---|---|---|
| GET | `/products` | List, paginate, sort | Public |
| GET | `/products/create` | Create form | Admin |
| POST | `/products` | Store product | Admin |
| GET | `/products/{id}` | Product detail | Public |
| GET | `/products/{id}/edit` | Edit form | Admin |
| POST | `/products/{id}` | Update product | Admin |
| POST | `/products/{id}/delete` | Delete product | Admin |
| GET / POST | `/products/{id}-{slug}/buy` | Purchase product | User |

Web routes are in `config/routes.php`; controller actions use `#[Route(...)]` attributes.
`#[RequiresRole('Admin')]` guards management actions.

## REST API

Obtain a token, then send it as `Authorization: Bearer <token>`:

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"admin12345"}'

curl http://localhost:8000/api/v1/products?page=1&sort=name&dir=asc \
  -H 'Authorization: Bearer <token>'

curl -X POST http://localhost:8000/api/v1/products/1/purchase \
  -H 'Authorization: Bearer <token>' \
  -H 'Content-Type: application/json' \
  -d '{"quantity":2}'
```

| Method | Path | Access |
|---|---|---|
| POST | `/api/v1/auth/login` | Public |
| GET | `/api/v1/products` | JWT |
| GET | `/api/v1/products/{id}` | JWT |
| POST | `/api/v1/products` | JWT + Admin |
| PUT / PATCH | `/api/v1/products/{id}` | JWT + Admin |
| DELETE | `/api/v1/products/{id}` | JWT + Admin |
| POST | `/api/v1/products/{id}/purchase` | JWT + User |

Responses use `{"data": ..., "meta": ..., "errors": ...}` with HTTP codes
`200 / 201 / 400 / 401 / 403 / 404 / 409 / 422`.

## Tests

```bash
vendor/bin/phpunit
# on this machine (mbstring shipped but not enabled by default):
php -d extension=mbstring vendor/bin/phpunit
```

Tests stub the PDO repositories, transaction manager, session and view through the
container-friendly constructor injection, so no MySQL server is needed.
Result: **31 tests, 112 assertions, all passing**.
