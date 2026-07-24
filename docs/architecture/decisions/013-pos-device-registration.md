# ADR-013: POS Device Registration

- Status: Accepted
- Date: 2026-07-24

## Context

Satu terminal POS dapat dipakai beberapa kasir. Sanctum token mewakili credential user pada satu perangkat, tetapi tidak cukup untuk mewakili ownership, outlet placement, dan revocation seluruh akses pada perangkat fisik yang sama.

## Decision

- Device registry disimpan terpisah dari Sanctum personal access token.
- Flutter membuat random installation ULID ketika pertama kali dijalankan.
- Server membuat device ULID tersendiri sebagai primary key device record.
- Installation ID bukan secret dan bukan authentication proof.
- Device dengan client type `pos_terminal` terikat tepat pada satu tenant dan satu outlet.
- Owner/manager dengan permission khusus dapat mendaftarkan device baru; cashier biasa tidak dapat mendaftarkan device.
- Setiap Sanctum token perangkat POS mereferensikan device record.
- Satu instalasi Flutter hanya menyimpan satu active user token pada satu waktu.
- Device revocation mencabut seluruh token terkait tanpa hard-delete device record.
- Hardware identifier seperti IMEI, MAC address, serial number, atau advertising ID tidak digunakan.

## Minimum Metadata

- Device ULID.
- Installation ULID.
- Tenant dan outlet ownership.
- Human-readable device name.
- Client type dan platform.
- App version.
- Registration timestamp dan actor.
- Last-seen timestamp.
- Revocation timestamp, actor, dan reason.

OS version/device model dapat dicatat untuk troubleshooting jika diperlukan, tetapi bukan authorization input.

## Registration Flow

```text
Flutter creates installation ULID
  -> user submits credentials
  -> server resolves registered device
  -> if registered: validate user membership and issue linked token
  -> if unknown and user can register: select authorized outlet and register
  -> if unknown cashier: reject with DEVICE_NOT_REGISTERED
```

## Outlet and Access Rules

- A request pada outlet harus memenuhi user outlet assignment dan device outlet binding.
- Active outlet preference di Flutter tidak dapat melewati device binding.
- Reassignment outlet membutuhkan permission khusus, actor/reason audit, revocation seluruh token device, dan login ulang.
- Perpindahan ke tenant berbeda dilakukan melalui revocation dan registration baru, bukan reassignment biasa.

## Revocation Rules

- Device yang dicabut menghasilkan stable problem code `DEVICE_REVOKED` pada request berikutnya.
- Seluruh token terkait device dicabut.
- Flutter menghapus local token setelah menerima revocation response.
- Device record dipertahankan untuk audit dan dapat digunakan kembali hanya melalui authorized registration flow.

## Operational Rules

- Client dapat mengirim installation ID, app version, dan platform sebagai header metadata.
- Header dicocokkan dengan token/device registry dan tidak dipercaya sendiri sebagai authorization.
- `last_seen_at` tidak diperbarui pada setiap request; update dibatasi atau menggunakan heartbeat agar tidak menambah write load berlebihan.
- Tidak ada idle auto-lock sesuai ADR-008.

## Consequences

- Kredensial cashier pada perangkat asing tidak cukup untuk mendaftarkan terminal POS baru.
- Satu device dapat dikenali lintas pergantian cashier.
- Onboarding dan reassignment terminal membutuhkan alur administratif.
- Manager client multi-outlet di masa depan memerlukan client-type policy/ADR tersendiri dan tidak otomatis mengikuti binding `pos_terminal`.

