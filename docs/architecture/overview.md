# Architecture Overview

Status: **Accepted**

## Architectural Style

Sistem menggunakan **Laravel Modular Monolith + Application Use Cases/Actions**.

Tujuannya adalah menjaga batas domain tanpa menambah overhead operasional microservices. Seluruh modul berjalan dalam satu aplikasi dan satu database MariaDB, tetapi kepemilikan logic dan data tetap eksplisit.

## Logical Layers

```text
Presentation (HTTP/API, Console, Queue Consumers)
    -> Application (Use Cases, DTOs, Ports)
        -> Domain (Models, Rules, Events, Exceptions)
            <- Infrastructure (Persistence, External Adapters)
```

### Presentation

- Menerima input transport dan menghasilkan response.
- Menangani authentication boundary, request validation, serialization, dan status HTTP.
- Tidak memuat perhitungan harga, lifecycle order, atau aturan bisnis lain.
- Memiliki dua adapter resmi: Web Admin Blade + Alpine.js dan REST API v1 untuk Flutter POS.
- Web menggunakan session authentication + CSRF; Flutter menggunakan Sanctum API token.
- Web controller dan API controller memanggil application use case, bukan saling memanggil melalui HTTP.

### Application

- Satu use case/action mewakili satu intent bisnis, misalnya `OpenShift`, `CreateOrder`, atau `CapturePayment`.
- Mengorkestrasi domain model, authorization, transaction boundary, dan port eksternal.
- Menggunakan DTO eksplisit untuk input kompleks.
- Tidak bergantung pada detail client Flutter.

### Domain

- Menyimpan invariant, state transition, value object, domain event, enum, dan exception bisnis.
- Eloquent model boleh berada di domain secara pragmatis.
- Domain service hanya digunakan jika aturan tidak alami dimiliki satu model/aggregate.

### Infrastructure

- Implementasi persistence khusus, payment gateway, printer, broadcasting, storage, dan integrasi eksternal.
- Repository tidak wajib. Repository dibuat hanya ketika query/persistence perlu abstraksi bermakna atau memiliki lebih dari satu implementasi.

## Physical Structure

Struktur fisik berikut berstatus **Accepted**:

```text
app/
├── Modules/
│   └── Sales/
│       ├── Application/
│       │   ├── Actions/
│       │   ├── Data/
│       │   └── Contracts/
│       ├── Domain/
│       │   ├── Models/
│       │   ├── Enums/
│       │   ├── Events/
│       │   └── Exceptions/
│       ├── Infrastructure/
│       └── Presentation/
│           └── Http/
│               ├── Api/V1/
│               └── Web/
└── Shared/
```

Setiap modul mengikuti struktur tersebut secara proporsional. Folder yang belum dibutuhkan tidak perlu dibuat. `Shared` hanya digunakan untuk konsep lintas modul yang memiliki ownership dan alasan reuse yang jelas.

## Module Communication Rules

- Sebuah modul tidak boleh mengubah model milik modul lain secara langsung.
- Alur synchronous lintas modul masuk melalui application contract/use case yang dipublikasikan modul pemilik.
- Side effect yang tidak harus atomik dapat menggunakan event.
- Event listener dan queued job wajib membawa tenant context eksplisit.
- Pekerjaan yang wajib konsisten dengan payment/order tidak boleh dipindahkan ke queue tanpa desain konsistensi yang disetujui.
- Reporting boleh menggunakan optimized read query dan tidak wajib mengikuti aggregate write model.

## Transaction Rules

- Use case mutasi menentukan transaction boundary.
- External network call tidak ditempatkan sembarang di dalam database transaction.
- Idempotency, locking, retry behavior, dan failure recovery harus didefinisikan pada use case kritis.
- Domain event hanya dipublikasikan setelah state yang menjadi sumber event berhasil dipersistenkan.

## Guardrails

- Tidak ada generic `BusinessService`, `Helper`, atau `Utils` sebagai tempat logic tanpa owner.
- Tidak ada controller yang memanggil banyak model untuk mengimplementasikan workflow bisnis.
- Tidak ada cross-module query tersembunyi melalui relationship tanpa review ownership.
- Tidak membuat interface dan repository hanya untuk memenuhi pola.
- Framework isolation bukan tujuan absolut; maintainability dan testability adalah tujuan utama.

## Presentation Responsibilities

- Web Admin memusatkan konfigurasi tenant, outlet, identity/roles, device, catalog, inventory, procurement, kitchen configuration, reporting, dan back-office lain.
- Flutter memusatkan operasi POS seperti shift, catalog read, order, payment, receipt, dan history operasional.
- API versioning berlaku pada mobile/public API; internal web route tidak menggunakan `/v1`.
- Session dapat menyimpan preferensi navigasi terakhir, tetapi web tenant/outlet authorization tetap berasal dari route context dan server-side membership.
- Blade + Alpine.js adalah default Web Admin; Vue hanya digunakan sebagai isolated complex-UI component berdasarkan ADR-015.
