# ADR-003: API-First with Flutter Client

- Status: Superseded by ADR-014
- Date: 2026-07-21

## Decision

Backend menyediakan API sebagai application boundary dan Flutter menjadi client utama MVP. Blade bukan client utama dalam roadmap saat ini.

Keputusan ini digantikan ADR-014 setelah Web Admin ditetapkan sebagai presentation client resmi untuk seluruh konfigurasi dan back-office.

## Consequences

- Kontrak API, authentication, versioning, dan error format perlu diputuskan sebelum implementasi endpoint.
- Business logic berada pada use case backend, bukan diduplikasi di controller atau Flutter.
- Offline penuh ditunda, tetapi identifier dan idempotency harus tidak menutup jalan menuju offline sync.
