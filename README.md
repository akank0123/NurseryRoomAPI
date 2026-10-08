# Nursery Room Check-ins API

A small Laravel 13 API for managing child check-ins and current room occupancy
in a multi-tenant nursery system.

Each request is scoped to the tenant provided through the `X-Tenant-ID` request
header.

## Run it

You can run the project with Docker (recommended, nothing else to install) or
directly with PHP.

### With Docker

Requires only Docker with Docker Compose. MongoDB and PHP run in containers,
so no local database or configuration is needed.

```sh
docker compose up -d --build
docker compose logs app        # shows the demo IDs created on start-up
```

The API will be available at `http://127.0.0.1:8000`. The demo data is reset
every time the app container starts.

To stop and remove everything:

```sh
docker compose down -v
```

### Without Docker

Requires:

- PHP 8.4+ with the MongoDB PHP extension (`pecl install mongodb`, then enable
  `extension=mongodb` in your `php.ini`)
- Composer 2
- A MongoDB server you can connect to (local, remote or MongoDB Atlas)

```sh
composer install
cp .env.example .env
```

Set the database values in `.env` to match your MongoDB server:

```
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1        # your MongoDB host
DB_PORT=27017            # your MongoDB port
DB_DATABASE=nursery      # any database name; it is created automatically
DB_USERNAME=             # leave empty if your server has no authentication
DB_PASSWORD=
```

If you have a full connection string instead (for example from MongoDB Atlas),
set `DB_URI` and it will be used instead of `DB_HOST`/`DB_PORT`:

```
DB_URI=mongodb+srv://<user>:<password>@<cluster>/?retryWrites=true
```

Then run:

```sh
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

The migration creates the required indexes, and the seeder creates demo data
and prints the relevant IDs. The API will be available at
`http://127.0.0.1:8000` (use `php artisan serve --port=8080` if port 8000 is
taken).

To reset the demo data:

```sh
php artisan migrate:fresh --seed
```

## API Endpoints

All endpoints require the `X-Tenant-ID` header.

**Check a child into a room**

```
POST /rooms/{room}/check-ins
```

Request:

```json
{
    "child_id": "..."
}
```

**Check a child out**

```
POST /check-ins/{id}/check-out
```

**Get current room occupants**

```
GET /rooms/{room}/occupants
```

The occupants endpoint returns only children who are currently checked into
that room.

## Tenant Isolation

Tenant isolation is applied to all room, child and check-in queries using the
tenant from the `X-Tenant-ID` header.

A tenant cannot read, check out, or otherwise modify another tenant's rooms,
children or check-ins.

Tenant isolation is specifically tested in `tests/Feature/TenantIsolationTest.php`.

I also verified that removing the tenant scope causes these isolation tests to
fail.

## Run the tests

```sh
php artisan test
```

With Docker:

```sh
docker compose exec app php artisan test
```

The tests use a separate database (`nursery_test`) on the same MongoDB server,
so they never change your demo data.

The test suite covers the main check-in/check-out behaviour, validation, room
occupants, duplicate check-ins, and tenant isolation.

## Decision: Child Checked Into a Second Room

I chose to reject the second check-in with `409 Conflict`.

The response includes the room where the child is currently checked in.

I chose this because the current occupants list can be used for safety purposes
such as room ratios and fire roll calls. Automatically moving a child could hide
an incorrect check-in or an earlier missed check-out.

Returning a 409 makes the issue explicit and allows staff to first check the
child out of the existing room and then check them into the new room.

A unique index on open check-ins also protects against two simultaneous
check-ins for the same child.

## Assumptions

- Authentication is handled elsewhere, as specified in the brief.
- The `X-Tenant-ID` header identifies the current tenant.
- A missing or invalid tenant header returns `400`.
- An unknown tenant returns `404`.
- A room or check-in belonging to another tenant returns `404`, rather than
  exposing that it exists.
- Rooms and children already exist and are created by the seeder.
- Nurseries are not modelled separately because none of the required endpoints
  need them.

## What I Would Do With More Time

- Take the tenant from the authenticated user instead of relying directly on
  the request header.
- Add staff roles and permissions.
- Add an explicit child transfer/move endpoint.
- Add room capacity/ratio checks.
- Add an audit trail showing who checked a child in or out.
- Add more API documentation, for example using OpenAPI/Swagger.

## Left Out on Purpose

The following were intentionally not implemented because they are outside the
scope of the exercise:

- Authentication and staff accounts.
- Create/update/delete endpoints for tenants, rooms, and children.
- Nursery management.
- Pagination for room occupants.
- Frontend/UI, since I selected Track A.

## Use of AI Tools

I used Claude Code as an AI coding assistant to help with project scaffolding,
implementation, writing tests, and end-to-end checks against a running server.

I reviewed the generated code and made the final implementation decisions
myself.

### Where I Changed or Rejected Its Output

- It suggested using SQLite and installing the SQLite driver. I rejected that
  and chose MongoDB instead based on my project setup.
- I changed the database configuration to use the standard Laravel `DB_*`
  environment variables with username/password authentication instead of the
  `MONGODB_*` variables it initially suggested.
- Its initial README was longer than necessary, so I simplified it to focus on
  the requirements in the brief.
- I manually tested all API endpoints using Postman, including check-in,
  check-out, occupant listing, validation, duplicate check-in, and
  tenant-isolation scenarios.
- I reviewed the generated tests and verified that the tenant-isolation tests
  actually fail when the tenant scope is removed.
