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
