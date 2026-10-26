# Smart Trade Platform — Identity and Access Management

The **Identity and Access Management (IAM)** service is the authentication and account-management component of **Smart Trade Platform (STP)**. It is a PHP application built with Laravel and Laravel Passport, with routes and domain services for tenants, users, roles, groups, scopes and authentication.

**Project lead:** NKALLA EHAWE Didier Junior

**Status:** prototype under development. The repository requires configuration, integration testing and security review before production use.

## Why this project exists

STP aims to help wholesalers and vendors keep track of goods supplied on credit, repayments and returns. The longer-term product vision includes a trading history that participants may use when engaging with financial institutions. Lending decisions remain with those institutions.

The IAM service supports this vision by providing an identity and access layer for STP clients, including the [Angular web client](https://github.com/angedid/angular-client). This repository is not the full STP platform: trading history, credit scoring and the other product capabilities described in the executive summary should not be assumed to be available here.

## Current scope

The repository contains code for:

- Laravel Passport OAuth authentication and an `auth:api` guard.
- Tenant provisioning, listing, details, activation, deactivation and logo handling.
- User and seller registration, user invitations, password-reset requests, user activation, photos and logout.
- Roles, groups and scopes, including role-to-user, role-to-group and role-to-scope associations.
- Super-administrator pages and a first-run setup flow.
- License, payment and order-related components, with RabbitMQ messaging integration.

These descriptions reflect routes, controllers and services found in the code. They do not establish that every flow works end to end or that all permissions and production controls are complete.

## Technology stack

| Component | Repository configuration |
| --- | --- |
| Runtime | PHP `^8.2`; OpenSSL extension required |
| Framework | Laravel `^12.0` |
| OAuth | Laravel Passport `^13.0` |
| Data access | Eloquent and Laravel migrations; database configured through environment variables |
| Messaging | `php-amqplib/php-amqplib` and Laravel jobs; RabbitMQ used by messaging code |
| Frontend assets | Vite, Vue, Bootstrap and Sass; see `package.json` |
| Tests | PHPUnit `^11.5.3` and Laravel's test runner |

`composer.lock` and `package-lock.json` record the dependency versions for the repository. Composer is required for PHP dependencies; Node.js and npm are required to build the application’s frontend assets.

## Prerequisites

- PHP 8.2 or later within the version constraints of the installed dependencies, with OpenSSL, a PDO driver for the selected database and the extensions required by Composer.
- Composer and access to the Composer package registry.
- A database supported by the configured Laravel connection.
- Node.js and npm compatible with the locked Vite and frontend dependencies, if building the web assets.
- A development mail transport, queue worker, SFTP service or RabbitMQ broker only when testing features that depend on those services.

These are configuration requirements, not a guarantee that every supported database or integration has been tested. Use a dedicated development database and synthetic data.

## Local setup

The following is a starting procedure for a new local development environment. It has **not been executed as part of the repository review**; verify the migration and integration requirements for your environment before relying on it.

```bash
git clone https://github.com/angedid/asso-iam.git
cd asso-iam
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env` for the development environment, especially `APP_URL`, `DB_CONNECTION` and any database, mail, queue or filesystem settings required for the flows you intend to exercise. Never commit `.env`, real credentials, application keys, Passport keys or access tokens.

Create an empty development database for the chosen connection, then apply the repository migrations:

```bash
php artisan migrate
```

The repository contains application and Passport migrations. Review migration compatibility with the selected database and resolve any failures before continuing. The provided `DatabaseSeeder` uses a generic factory record; it has not been validated against this application's user schema. Do not assume that seeding creates a usable application account.

Generate Passport signing keys for a fresh local installation and create a public client for the Authorization Code flow with PKCE:

```bash
php artisan passport:keys
php artisan passport:client --public
```

Follow the client-creation prompts and register the exact redirect URI used by your Angular client. For the Angular reference configuration, this is `http://localhost:4200/oauth/callback`. Copy the resulting public client ID into the Angular development configuration. Do not place a client secret in browser code. Passport's documented setup is available in the [Laravel 12 Passport documentation](https://laravel.com/docs/12.x/passport).

If the Blade/Vite web pages are needed, install and build their assets:

```bash
npm ci
npm run build
```

Start the development server on the port expected by the Angular client:

```bash
php artisan serve --host=127.0.0.1 --port=9001
```

The base URL is `http://127.0.0.1:9001`. The Angular reference uses `http://localhost:9001`; use a consistent hostname in both applications and configure the exact redirect URI and allowed browser origin accordingly.

> The `/` route currently invokes a first-run setup routine that reads an `init.json` file from the runtime working directory and deletes it after creating an initial super administrator. The repository also tracks `public/init.json` and `public/init0.json`. Do not place real credentials in publicly served or tracked files. Review and secure this bootstrap mechanism before use; its working-directory assumptions and complete setup have not been validated here.

## Authentication and Angular integration

Laravel Passport provides the OAuth authorization and token routes; confirm the routes registered by the installed version with:

```bash
php artisan route:list --path=oauth
```

The Angular client reference expects an authorization endpoint at `/oauth/authorize`, a token endpoint at `/oauth/token` and the callback path `/oauth/callback` on the Angular origin. The registered Passport client, redirect URI, allowed origin and Angular client ID must agree.

The code configures access-token, refresh-token and personal-access-token lifetimes in `app/Providers/AppServiceProvider.php`. A refresh-token table and Passport configuration are present, but issuing and rotating refresh tokens for a particular public client must be verified against the running server and client configuration. The presence of these components alone does not prove that the end-to-end refresh flow works.

Many application routes use the `auth:api` middleware. The code also builds tenant-qualified usernames in the form `local_username@tenant_uuid` during the web login flow. Confirm the actual behavior against a development server and test account before relying on it in a client.

For cross-origin browser requests, review `config/cors.php`. The checked-in configuration currently allows all origins, methods and headers. Restrict it appropriately before deploying the service.

## API overview

The route definitions are in `routes/api.php`. The `/api` prefix is provided by Laravel's API routing configuration. The table describes route declarations at the reviewed revision; it is not an assurance that every endpoint or authorization policy has been exercised.

| Method | Path | Purpose | Route middleware |
| --- | --- | --- | --- |
| `GET` | `/api/user` | Retrieve the authenticated user | `auth:api` |
| `POST` | `/api/tenants` | Provision a tenant | No route-level `auth:api` |
| `GET` | `/api/tenants` | List tenants | No route-level `auth:api` |
| `GET` | `/api/tenants/{tenant_id}` | Retrieve tenant details | No route-level `auth:api`; the guard is commented out |
| `POST` | `/api/users-registrations-invitations` | Invite a user | `auth:api` |
| `POST` | `/api/users` | Register a user | `auth:api` |
| `POST` | `/api/sellers` | Register a seller | No route-level `auth:api` |
| `POST` | `/api/logout/{username}` | Process logout and token revocation | `auth:api` |
| `POST`, `GET` | `/api/roles`, `/api/groups` | Manage and list roles or groups | `auth:api` |
| `GET` | `/api/scopes` | List scopes | `auth:api` |
| `POST` | `/api/password-reset-request` | Request a password reset | No route-level `auth:api` |
| `GET` | `/api/tenants/{tenant_id}/valid-licences` | Retrieve valid license details | `auth:api` |
| `POST`, `PUT` | `/api/payments`, `/api/orders`, `/api/orders/{order_id}/paid-order`, `/api/orders/{order_id}/cancelled-order` | Payment and order integration operations | `rabbitmq.client`, aliased in `bootstrap/app.php` to Passport `CheckToken` |

Other routes cover group membership, role assignments, tenant/user activation, logos and user photos. Inspect `routes/api.php`, middleware and service methods for the complete route and authorization behavior. “No route-level `auth:api`” does not rule out global middleware or checks in a controller or service.

Controllers including `GuestController` and `UserController` may return HTTP `200` for application-level failures, with fields such as `success`, `faillure` and `raison`. Clients should inspect the response body according to the specific endpoint instead of assuming that every `200` indicates success. The exact payload and error contracts should be tested and documented before integration.

## Background services and configuration

- **Email and queues:** invitation and password-reset jobs are under `app/Jobs/`. Configure `MAIL_*` and `QUEUE_CONNECTION` as needed. For an asynchronous queue, start an appropriate worker, for example `php artisan queue:work`, after configuring its backend.
- **RabbitMQ:** message-publishing code and listener scripts are under `app/Jobs/` and `app/Infrastructure/Messaging/`. Some code reads a relative `global-var-config.ini`; review the process working directory and configuration before running workers or listeners. The checked-in `.env.example` is not a complete inventory of all integration settings.
- **Files:** user photos and tenant logos use Laravel filesystem storage. The repository also defines an SFTP disk and uses it in tenant and seller-related services. Configure these integrations only for the features being tested.
- **License and payment integrations:** routes, models and services are present. External payment and messaging behavior requires separately configured services and has not been verified end to end.

Use isolated services and synthetic credentials in development. Review existing tracked configuration files before sharing or deployment; do not assume that a `.gitignore` entry removes a file already tracked by Git.

## Tests and checks

```bash
php artisan route:list
php artisan test
```

The repository also defines `composer test`, which clears the configuration cache and invokes the Laravel test runner. Review `phpunit.xml` and make sure the test environment uses a disposable database. The existing `tests/Unit/ExampleTest.php` and `tests/Feature/ExampleTest.php` are starter examples; they do not establish coverage for IAM, tenant isolation, OAuth, role enforcement or token revocation.

**Review status:** the source and route declarations were inspected at commit `656b0e935a8b1d8cb13791c2e9e0469777a10f49` on `feature/payments-licenses-features-added`. PHP and Composer were unavailable in the review environment, so dependency installation, migrations, route listing, tests, web pages and OAuth flows were not run. These checks remain to be performed in a configured environment.

## Repository structure

| Path | Purpose |
| --- | --- |
| `routes/api.php`, `routes/web.php` | API and web route declarations |
| `app/Http/Controllers/` | HTTP controllers for authentication, tenants, users and access-management resources |
| `app/Http/Middleware/` | Authentication-related middleware and request handling |
| `app/Domain/` | Domain models and domain services |
| `app/spi/` | Application service implementations |
| `app/Models/` | Laravel user model |
| `app/Jobs/`, `app/Infrastructure/Messaging/` | Queued jobs, message publishing and listener scripts |
| `config/` | Laravel, authentication, Passport, filesystem, queue, mail and CORS settings |
| `database/migrations/`, `database/seeders/` | Schema migrations and starter seeder |
| `resources/` | Views and frontend assets |
| `tests/` | PHPUnit/Laravel starter tests |

## Security and development limitations

This repository is a prototype, and the following code paths require attention before deployment:

- `public/init.json` and `public/init0.json` are tracked under the public web directory. The setup flow also reads an `init.json` containing bootstrap-account information. Check these files and Git history for exposed credentials, remove sensitive bootstrap data from public access and rotate any credentials that were real. Do not repeat their contents in tickets or logs.
- `UserVerifierMiddleware` logs complete login requests, which can include passwords. `CatchAccessTokenMiddleware` dispatches successful token-response content to a messaging job. Review these flows and remove sensitive credential and token exposure.
- Several routes, including tenant retrieval, do not declare `auth:api`; review route access, tenant isolation and authorization checks. CORS currently permits all origins.
- The supplied test suite consists of starter examples. Add integration and security tests before assuming that authentication, token revocation, user roles or tenant access work correctly.
- HTTP `200` can represent an application-level failure for some controller responses. Document and test the contract used by each client.

## Contributing

Describe the user need, the intended behavior and verification steps. Keep changes focused, use synthetic fixtures and include tests for authentication, authorization and tenant-boundary changes. Record commands and actual results, including integrations that could not be checked locally.

Do not commit real personal data, tokens, passwords, private keys or configuration files containing secrets. Preserve notices and licenses belonging to third-party packages and assets.

## License

`composer.json` declares `MIT`, but no root `LICENSE` file was present in the reviewed repository. Confirm the rights held in the project's original code and add the intended license text before describing the repository as licensed. Dependencies and third-party content retain their own licenses.

## Product background

The STP vision presented here is based on “Smart Trade Platform — Executive Summary,” version 1.0, August 2026, by Didier Junior NKALLA EHAWE. Planned product capabilities are distinguished above from the code currently present in this repository.
