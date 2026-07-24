# ADR-014: Web Admin and Flutter Presentation Responsibilities

- Status: Accepted
- Date: 2026-07-24
- Supersedes: ADR-003

## Context

Konfigurasi tenant, outlet, roles, catalog, inventory, dan back-office membutuhkan antarmuka web terpusat. Flutter ditujukan sebagai display dan aplikasi operasional POS, bukan tempat mengelola seluruh master data. Arsitektur harus mendukung kedua client tanpa menduplikasi business logic.

## Decision

- Sistem memiliki dua presentation client resmi.
- Web Admin menggunakan Laravel Blade + Alpine.js untuk konfigurasi dan back-office.
- Flutter digunakan untuk operasional POS.
- Web Admin menggunakan Laravel session authentication dan CSRF protection.
- Flutter menggunakan Sanctum API bearer token sesuai ADR-007 dan ADR-008.
- Web dan API controller memanggil application use cases/actions yang sama.
- Web controller tidak memanggil API controller atau melakukan HTTP request ke REST API aplikasi yang sama.
- API controller tidak memanggil web controller.
- API versioning `/api/v1` berlaku untuk mobile/public API; internal Web Admin route tidak diberi versi API.

## Responsibility Split

### Web Admin

- Satu Web Admin Laravel memiliki Platform Admin area dan Tenant Admin area dengan auth/authorization boundary terpisah.
- Tenant dan outlet configuration.
- User, role, permission, dan membership management.
- POS device registration, reassignment, dan revocation.
- Product, category, variant, modifier, availability, dan pricing.
- Inventory, recipe, supplier, procurement, dan stock administration.
- Tax, service charge, discount, payment method, kitchen, dan printer configuration.
- Reporting, audit, payment gateway configuration, dan back-office capability lain.

### Flutter POS

- Login pada registered POS device.
- Shift operation.
- Membaca catalog yang sudah dikonfigurasi.
- Order, payment, receipt, dan operational history.
- Table/kitchen operational capability jika kelak masuk scope.
- Local device setting yang memang relevan terhadap operasi POS.

Flutter tidak menjadi tempat master-data administration kecuali ADR baru menetapkan kebutuhan khusus.

## Shared Application Boundary

```text
Web Controller -> Application Action <- API Controller
                         |
                       Domain
```

- Transport validation dan response berbeda boleh dimiliki masing-masing adapter.
- Business invariant, authorization intent, transaction boundary, dan domain event tidak diduplikasi.
- Policy, tenant scope, dan membership rules digunakan secara konsisten oleh kedua adapter.

## Physical Presentation Structure

```text
<Module>/Presentation/Http/
├── Api/V1/
│   ├── Controllers/
│   ├── Requests/
│   └── Resources/
└── Web/
    ├── Controllers/
    ├── Requests/
    └── ViewModels/
```

Lokasi final Blade views tetap keputusan implementasi terpisah selama ownership modul dan dependency rules dipertahankan.

## Web Context

- Web tenant-wide route menggunakan tenant context eksplisit, misalnya `/admin/tenants/{tenant}/...`.
- Outlet-specific back-office route menyertakan outlet context ketika diperlukan.
- Session dapat menyimpan tenant/outlet terakhir sebagai preferensi navigasi, bukan sumber authorization.

## Consequences

- Phase foundation harus menyediakan Web Admin shell dan session authentication selain API/Sanctum foundation.
- Back-office capability tidak perlu dipaksakan menjadi Flutter screen.
- Web dan Flutter dapat berkembang dengan ritme berbeda tanpa memecah domain/application layer.
- Presentation tests berbeda, sedangkan application/domain tests dapat digunakan bersama.
- Strategi frontend Web Admin mengikuti ADR-015.
