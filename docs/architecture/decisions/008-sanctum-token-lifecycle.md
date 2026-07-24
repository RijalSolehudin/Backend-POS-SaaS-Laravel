# ADR-008: Sanctum Token Lifecycle for POS Devices

- Status: Accepted
- Date: 2026-07-23

## Context

Terminal POS merupakan perangkat operasional dedicated yang tetap menampilkan aplikasi baik ketika ada maupun tidak ada transaksi. Idle auto-lock akan mengganggu alur kasir. Pada saat yang sama, API credential harus memiliki batas umur dan dapat dicabut per perangkat.

## Decision

- Setiap kombinasi user dan device installation memiliki Sanctum token tersendiri.
- Device installation menggunakan identifier acak yang tidak diperlakukan sebagai authentication secret.
- Token berlaku maksimal 30 hari sejak diterbitkan.
- MVP tidak menggunakan refresh token.
- Token yang kedaluwarsa membutuhkan login ulang untuk memperoleh token baru.
- Login ulang pada kombinasi user-device yang sama mencabut/menggantikan token sebelumnya.
- Logout mencabut current token tanpa mencabut token user pada perangkat lain.
- Pengguna berwenang dapat mencabut token perangkat tertentu atau seluruh token user.
- Aplikasi POS tidak melakukan auto-lock berdasarkan idle time.
- Close shift tidak otomatis mencabut token; logout dan close shift adalah intent yang berbeda.

## Immediate Access Invalidation

Token tidak boleh mempertahankan akses efektif ketika:

- User dinonaktifkan.
- Tenant dinonaktifkan.
- Membership tenant/outlet dicabut.
- Device atau specific token dicabut.
- Token melewati expiration.

Validitas token tidak menggantikan pemeriksaan status user, tenant, membership, permission, dan model policy pada request.

## Operational Controls

- Plain-text token hanya tersedia saat penerbitan dan disimpan Flutter melalui secure storage platform.
- Token tidak dicatat pada application log, analytics, atau error report.
- Daftar device/token aktif dan aksi revocation menjadi capability administratif yang direncanakan.
- Manual logout tersedia untuk pergantian user atau perangkat yang tidak lagi diawasi.
- Audit trail tetap mencatat actor dan shift untuk operasi bisnis sensitif.

## Consequences

- Terminal dapat tetap menampilkan aplikasi tanpa interupsi idle timeout.
- Risiko akses fisik pada terminal aktif diterima sebagai trade-off operasional dan dimitigasi melalui penempatan perangkat, permission, shift attribution, manual logout, revocation, serta audit.
- Tidak adanya refresh token menyederhanakan MVP tetapi mengharuskan login ulang setidaknya setelah token kedaluwarsa.
- Offline synchronization harus menangani token kedaluwarsa dalam ADR terpisah; queued local data tidak boleh dibuang hanya karena autentikasi perlu diperbarui.

## Open Decisions

- Re-authentication atau supervisor approval untuk void, refund, discount override, cash adjustment, dan aksi sensitif lain.
- Device metadata dan registration telah diputuskan melalui ADR-013.
- Respons Flutter ketika user/membership dicabut saat aplikasi sedang aktif.
