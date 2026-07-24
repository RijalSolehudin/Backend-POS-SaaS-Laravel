# ADR-007: Laravel Sanctum API Token Authentication

- Status: Accepted
- Date: 2026-07-21

## Context

Flutter adalah client utama MVP dan membutuhkan autentikasi API yang sederhana, dapat dicabut, serta tetap kompatibel dengan tenant/outlet authorization pada backend.

## Decision

Flutter menggunakan Laravel Sanctum API token. Token diterbitkan untuk user setelah credential diverifikasi dan diperlakukan sebagai credential rahasia pada perangkat.

## Rules

- Plain-text token hanya dikembalikan pada saat penerbitan.
- Flutter menyimpan token menggunakan secure storage platform.
- Token tidak ditulis ke application log, analytics, atau error report.
- Logout mencabut token yang digunakan oleh device/session tersebut.
- Revocation harus tersedia ketika perangkat hilang atau akses user dicabut.
- Token ability dapat membatasi capability, tetapi policy dan tenant/outlet membership tetap menjadi otoritas akses utama.
- Setiap request tetap menyelesaikan tenant/outlet context dari membership yang valid.
- Rate limiting dan audit diterapkan pada login, token issuance, serta revocation.

## Consequences

- Implementasi lebih ringan daripada OAuth authorization server penuh.
- Backend harus mengelola lifecycle dan revocation token.
- Flutter harus menjaga token di secure storage dan menghapusnya setelah revocation/logout.
- Sanctum tidak menyelesaikan device trust, offline authorization, atau tenant isolation secara otomatis.

## Follow-Up Decisions

- Token lifecycle mengikuti ADR-008.
- Re-authentication untuk void, refund, dan aksi sensitif.
- Perilaku token saat user, outlet membership, atau tenant dinonaktifkan.

