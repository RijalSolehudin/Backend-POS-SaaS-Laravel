# POS F&B Backend

Backend modular monolith untuk sistem POS F&B. Implementasi berjalan berdasarkan ADR, roadmap, dan work package pada [documentation index](docs/README.md).

## Requirements

- PHP 8.3
- Composer 2
- Node.js 22 atau 24
- Docker-compatible container runtime untuk MariaDB-backed tests

## Setup

```shell
composer install
copy .env.example .env
php artisan key:generate
npm install
```

Sesuaikan credential MariaDB development pada `.env`, lalu jalankan migration ketika work package pemilik schema telah siap.

## Development Quality

```shell
composer quality:static
docker compose up -d mariadb-testing
composer quality
npm run build
```

Convention module, action, database test, dan quality tooling dijelaskan pada [Development Conventions](docs/architecture/development-conventions.md).

## Operational Readiness

Production/staging deployment wajib menjalankan queue worker dan scheduler:

```shell
php artisan schedule:run
```

Ikuti [Deployment Readiness Runbook](docs/runbooks/deployment-readiness.md) untuk HTTPS/session cookie, migration, scheduler, audit, dan release evidence.

## Platform Administrator Bootstrap

Setelah migration serta queue/scheduler deployment siap, buat Platform Administrator pertama melalui prompt terkontrol:

```shell
php artisan platform:bootstrap
```

Kemudian buka `/platform/login` untuk menyelesaikan enrollment TOTP. Emergency recovery menggunakan `php artisan platform:recover-access` dan wajib mengikuti [runbook](docs/runbooks/platform-bootstrap-and-recovery.md).

## Tenant Provisioning

Provisioning normal tersedia pada `/platform/tenants`. Controlled interactive CLI tersedia tanpa menerima password melalui command argument:

```shell
php artisan tenant:provision
```

Ikuti [Tenant Provisioning Runbook](docs/runbooks/tenant-provisioning.md), khususnya aturan idempotency key, secure credential delivery, dan migration baseline untuk database development lama.
