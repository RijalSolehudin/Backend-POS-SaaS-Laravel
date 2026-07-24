# ADR-012: API Responses, Errors, and Versioning

- Status: Accepted
- Date: 2026-07-24

## Context

Laravel dan Flutter membutuhkan kontrak response yang konsisten, machine-readable error, serta jalur evolusi ketika aplikasi pada perangkat tidak dapat diperbarui secara serentak.

## Decision

- API menggunakan URI versioning yang dimulai dari `/api/v1`.
- Response sukses menggunakan JSON resource envelope `data`.
- Collection/pagination dapat menambahkan `links` dan `meta`.
- Response error menggunakan RFC 9457 Problem Details dengan media type `application/problem+json`.
- Error memiliki stable extension `code`, `trace_id`, dan `retryable`.
- Validation error dapat menambahkan structured array `errors` dengan `field`, `code`, dan `message`.
- Setiap response memiliki `X-Request-ID`; error body memakai correlation value yang sama.
- Flutter melakukan logic berdasarkan HTTP status dan stable `code`, bukan human-readable message.

## Success Contract

```json
{
  "data": {
    "id": "01...",
    "status": "open"
  }
}
```

- Resource creation menggunakan `201 Created`.
- Operasi sukses tanpa payload menggunakan `204 No Content`.
- Generic `success: true` dan pesan `Success` tidak ditambahkan secara default.

## Error Contract

```json
{
  "type": "https://api.example.com/problems/shift-already-open",
  "title": "Shift already open",
  "status": 409,
  "detail": "This cashier already has an active shift for the outlet.",
  "instance": "/api/v1/outlets/01.../shifts",
  "code": "SHIFT_ALREADY_OPEN",
  "trace_id": "01...",
  "retryable": false
}
```

- `type` menggunakan stable problem-type URI; domain final ditetapkan saat environment naming diputuskan.
- Production tidak mengekspos stack trace, SQL, file path, secret, token, atau raw integration response.
- Resource di luar authorized tenant/outlet context dapat menghasilkan `404`.

## Versioning Rules

Breaking change meliputi:

- Menghapus atau mengganti nama field.
- Mengubah tipe atau semantik field.
- Mengubah optional field menjadi required.
- Mengubah lifecycle/status semantics.
- Mengubah stable error code.
- Menambah enum value bila contract tidak menyediakan unknown fallback.

Additive endpoint, optional field, dan optional metadata umumnya tidak membutuhkan versi baru.

## Client Compatibility Rules

- Flutter mengabaikan field JSON yang tidak dikenal.
- Flutter memiliki fallback untuk enum yang tidak dikenal.
- Flutter tidak bergantung pada urutan JSON field.
- Flutter tidak mem-parsing `title`, `detail`, atau `message` untuk business logic.

## Deprecation Policy

- Versi lama dipertahankan minimal 90 hari setelah versi pengganti tersedia.
- Setidaknya satu stable Flutter release pengganti harus tersedia sebelum sunset normal.
- Deprecation/sunset metadata disampaikan pada response versi lama dan usage dimonitor.
- Security emergency dapat memperpendek masa transisi melalui keputusan eksplisit.

## Consequences

- Response memiliki sedikit nesting tambahan tetapi tetap selaras dengan Laravel API Resources.
- Error mapping perlu dikelola secara konsisten pada application/presentation boundary.
- V1 dan versi pengganti mungkin perlu berjalan bersamaan selama migration window.
- OpenAPI contract dan contract tests diperlukan sebelum endpoint dianggap selesai.

