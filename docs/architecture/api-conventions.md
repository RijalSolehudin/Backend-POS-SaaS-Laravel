# API Conventions

Status: **Accepted baseline; endpoint details remain incremental**

## Accepted Direction

- REST API adalah presentation boundary untuk Flutter POS dan calon external client, bukan satu-satunya presentation aplikasi.
- API mengeksekusi application use cases; API bukan lokasi business logic.
- Flutter menggunakan Laravel Sanctum API token untuk autentikasi.
- Outlet-scoped endpoint menggunakan `{outlet}` secara eksplisit pada URL.
- Tenant diturunkan dari outlet dan diverifikasi server-side.
- Tenant-wide endpoint menggunakan `{tenant}` secara eksplisit pada URL.
- API menggunakan URI versioning dengan prefix `/api/v1`.
- Response sukses menggunakan resource envelope `data`.
- Response error menggunakan RFC 9457 Problem Details.

Web Admin memiliki presentation contract terpisah dan memanggil application use cases secara langsung; Web Admin tidak diwajibkan memanggil REST API Laravel yang sama.

## Authentication

- Token diterbitkan untuk kombinasi user dan device/session yang tervalidasi.
- Token disimpan oleh Flutter menggunakan secure storage platform.
- Plain-text token hanya diberikan saat penerbitan dan tidak dicatat pada log.
- Logout mencabut token aktif; pengguna berwenang dapat mencabut token perangkat lain.
- Satu user dapat memiliki token berbeda untuk setiap device installation.
- Token berlaku maksimal 30 hari sejak diterbitkan.
- Login ulang pada kombinasi user-device yang sama mengganti token sebelumnya.
- MVP tidak menggunakan refresh token; token kedaluwarsa membutuhkan login ulang.
- Aplikasi POS tidak melakukan auto-lock berdasarkan idle time.
- Ability/token scope dapat digunakan sebagai pembatas tambahan, tetapi tidak menggantikan policy dan tenant/outlet authorization.
- Token tidak menyimpan tenant context yang dipercaya tanpa validasi membership server-side.
- Re-authentication untuk aksi sensitif masih berstatus `Open`.

## POS Device Context

- Device registry terpisah dari Sanctum token.
- Setiap instalasi Flutter memiliki random installation ULID yang bukan secret/authentication proof.
- Server memiliki device ULID tersendiri dan token mereferensikan device terdaftar.
- Device `pos_terminal` terikat tepat pada satu tenant dan outlet.
- User access pada outlet harus memenuhi user assignment sekaligus device outlet binding.
- Device/client header hanya membantu resolusi dan observability; server mencocokkannya dengan token serta device registry.
- Hardware identifier seperti IMEI dan MAC address tidak digunakan.

## Success Responses

- JSON adalah representation utama dan Laravel API Resources mengontrol serialization.
- Single resource dan collection dibungkus dalam `data`.
- `links` digunakan untuk navigation/pagination link.
- `meta` digunakan untuk metadata response, bukan domain data.
- `201 Created` digunakan ketika resource dibuat.
- `204 No Content` digunakan untuk operasi sukses tanpa payload yang berguna.
- Field generik `success` dan pesan `Success` tidak ditambahkan pada semua response.
- Pagination berbasis cursor digunakan untuk dataset besar bila sesuai kebutuhan endpoint.

## Error Responses

- Seluruh API error menggunakan media type `application/problem+json` dan shape RFC 9457.
- Member standar: `type`, `title`, `status`, `detail`, dan `instance` bila relevan.
- Extension stabil: `code`, `trace_id`, dan `retryable`.
- Validation problem dapat menambahkan array `errors` berisi `field`, `code`, dan human-readable `message`.
- Flutter menggunakan `code` untuk branching/localization dan tidak mem-parsing `title`, `detail`, atau `message`.
- Production response tidak mengekspos stack trace, SQL, path server, secret, token, atau raw gateway detail.
- Setiap response memiliki `X-Request-ID`; error body menggunakan nilai korelasi yang sama sebagai `trace_id`.

## HTTP Status Baseline

| Condition | Status |
|---|---:|
| Success | `200` |
| Resource created | `201` |
| Success without body | `204` |
| Malformed request | `400` |
| Missing/invalid/expired authentication | `401` |
| Authenticated but forbidden | `403` |
| Resource not found in authorized context | `404` |
| Lifecycle, concurrency, or idempotency conflict | `409` |
| Field validation failure | `422` |
| Rate limit | `429` |
| Unexpected server error | `500` |
| Temporary dependency failure | `503` |

Resource di luar tenant/outlet context dapat menghasilkan `404` agar keberadaannya tidak terungkap.
- Idempotency key untuk create order, payment, refund, dan operasi sinkronisasi.

## Security Requirements

- Semua protected endpoint memerlukan authentication dan authorization.
- Tenant/outlet context divalidasi pada server sebelum use case dijalankan.
- `tenant_id`/`outlet_id` pada body atau custom header tidak menjadi sumber otorisasi.
- Active outlet yang disimpan Flutter hanya preferensi UI.
- Child resource lookup dan route binding harus di-scope pada immutable request context.
- Rate limiting dibedakan untuk login, PIN, public QR, dan authenticated API.
- Secret payment gateway tidak pernah dikirim ke client.
- Sensitive mutation memiliki audit trail.
- Device revocation mencabut seluruh token terkait dan device record tetap dipertahankan untuk audit.

## Compatibility

- Perubahan breaking membutuhkan API version baru.
- Client tidak boleh bergantung pada label UI sebagai state identifier.
- Enum contract dan lifecycle state didokumentasikan.
- Flutter mengabaikan field yang tidak dikenal dan memiliki unknown-enum fallback.
- Penambahan field/endpoint optional umumnya additive dan tidak memerlukan versi baru.
- Penghapusan/rename field, perubahan tipe/semantik, optional menjadi required, serta perubahan stable error code dianggap breaking.

## Versioning and Deprecation

- Versi berada pada URL, dimulai dari `/api/v1`.
- Header/media-type versioning tidak digunakan sebagai mekanisme utama.
- Ketika versi baru tersedia, versi lama dipertahankan minimal 90 hari dan minimal sampai satu stable Flutter release pengganti tersedia.
- Response versi deprecated menyampaikan deprecation/sunset metadata yang disetujui dan penggunaan versi lama dimonitor sebelum dihentikan.
- Security emergency dapat memperpendek masa transisi melalui keputusan eksplisit.

## Numeric Representation

- Monetary amount dikirim sebagai integer dengan nama berakhiran `_minor` dan currency code eksplisit pada konteks terkait.
- Untuk IDR, `150000` berarti Rp150.000.
- Quantity, unit cost, conversion factor, dan decimal presisi lain dikirim sebagai decimal string, bukan floating-point JSON number.
- Client tidak boleh menggunakan `float`/`double` untuk business calculation.
- Percentage rate menggunakan basis points pada kontrak yang telah menetapkan presisi tersebut.

## Decisions Required Before Implementation

- Detail re-authentication untuk aksi sensitif.
- Conflict resolution untuk offline synchronization.
