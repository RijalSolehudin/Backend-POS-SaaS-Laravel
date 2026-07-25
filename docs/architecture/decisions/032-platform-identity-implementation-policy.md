# ADR-032: Platform Identity Implementation Policy

- Status: Accepted
- Date: 2026-07-24

## Context

ADR-017–ADR-019 menetapkan pemisahan Platform Administrator, mandatory TOTP, database session, timeout, recent confirmation, dan emergency CLI. P01-02 memerlukan keputusan lebih rinci agar authentication flow, credential storage, recovery, audit, dan operational ownership dapat diimplementasikan tanpa ambiguity.

Product owner menyetujui keputusan awal satu per satu, kemudian mendelegasikan sisa keputusan P01-02 kepada rekomendasi teknis pada 2026-07-24. Delegasi hanya berlaku untuk P01-02.

## Identity and Lifecycle

- Login identifier menggunakan email saja, dinormalisasi lowercase dan unik.
- Email menjadi identifier dan kanal notifikasi, bukan satu-satunya recovery authority.
- Status akun eksplisit:
  - `pending_mfa_setup`;
  - `active`;
  - `suspended`.
- Soft delete tidak digunakan. Identity dipertahankan untuk integritas audit.
- Platform user tidak mempunyai tenant membership atau tenant role.

## First Bootstrap

- Command `platform:bootstrap` hanya dapat dijalankan secara interaktif.
- Name, email, operator identity, reason, dan optional ticket/reference diminta melalui prompt.
- Password dan confirmation menggunakan hidden prompt; password tidak diterima sebagai CLI argument.
- Command hanya berhasil ketika belum ada record `platform_users`.
- Distributed application lock dan transaction mencegah concurrent bootstrap.
- Tidak tersedia opsi `--force`.
- Account hasil bootstrap berstatus `pending_mfa_setup`.

## Password Policy

- Panjang 12–128 karakter.
- Spasi, Unicode, paste, autofill, dan password manager diperbolehkan.
- Tidak ada composition rule uppercase/lowercase/angka/simbol.
- Laravel `Password::uncompromised()` wajib pada bootstrap dan perubahan/reset password.
- Pemeriksaan compromised password bersifat fail closed.
- Tidak ada periodic password expiration; perubahan diwajibkan ketika ada indikasi kompromi.
- Hash mengikuti Laravel hashing configuration dan direhash ketika diperlukan.

## TOTP Enrollment and Verification

- Enrollment dilakukan saat login pertama melalui challenge session terbatas.
- Account belum memperoleh full platform session sebelum TOTP dikonfirmasi.
- Modul Platform Identity memiliki use case enrollment/challenge sendiri.
- RFC 6238 diimplementasikan melalui `spomky-labs/otphp`, dibungkus kontrak modul.
- Provisioning menggunakan 6 digit, period 30 detik, dan issuer platform yang dapat dikonfigurasi.
- Satu time-step TOTP hanya dapat digunakan sekali per account untuk mencegah replay.
- Challenge password/MFA berlaku lima menit.
- QR dibuat lokal sebagai SVG menggunakan `bacon/bacon-qr-code`; provisioning URI tidak dikirim ke layanan eksternal.
- Secret manual tersedia sebagai fallback dan response enrollment menggunakan `no-store`.
- TOTP secret disimpan menggunakan Laravel encrypted cast.

## Recovery Codes

- Enrollment dan regeneration menghasilkan 10 kode.
- Setiap kode mempunyai 16 karakter Base32 tanpa karakter ambigu dan ditampilkan dalam empat kelompok.
- Kode disimpan sebagai hash satu arah pada record terpisah.
- Penggunaan dikonsumsi secara atomik dan single-use.
- Recovery code menggantikan TOTP untuk satu login atau confirmation, tetapi tidak otomatis mengganti/menonaktifkan TOTP.
- Regeneration membatalkan seluruh set lama dan set baru hanya ditampilkan satu kali.
- Plain recovery code tidak masuk log, audit, notification, atau session.

## Guard and Session Isolation

- Guard `platform` menggunakan provider `platform_users`.
- Platform menggunakan cookie `pos_platform_session` dan tabel `platform_sessions`, terpisah dari tenant `web` session.
- Platform session payload dienkripsi.
- Cookie bersifat `Secure` pada production, `HttpOnly`, `SameSite=Lax`, dan path `/platform`.
- Idle timeout 15 menit dan absolute timeout 4 jam ditegakkan server-side.
- Route yang kelak menjadi passive polling harus ditandai `platform_passive`; route tersebut tidak memperpanjang activity timestamp.
- Maksimal dua active session.
- Slot session direservasi menggunakan lock atomik per Platform User sebelum response login disimpan agar concurrent MFA completion tidak melewati batas.
- Login ketiga baru dapat dilanjutkan setelah user memilih satu session lama untuk dicabut. MFA challenge tidak memberi platform access sebelum pemilihan selesai.
- Remember-me tidak tersedia.
- Logout dan revocation hanya memengaruhi cookie/session Platform.

## Sensitive Confirmation

- Sensitive confirmation membutuhkan password dan current TOTP atau unused recovery code.
- Confirmation disimpan pada session Platform terkait dan berlaku 10 menit.
- Login dua faktor yang baru berhasil langsung memenuhi confirmation window.
- Confirmation dari session lain tidak berlaku.
- Middleware `platform.confirmed` menjadi enforcement point untuk sensitive Platform routes.

## Rate Limiting

- Password failure di-scope berdasarkan normalized email + IP, ditambah aggregate IP limit.
- Lima kegagalan dalam 15 menit memulai cooldown.
- Repeated cooldown meningkat 1 menit, 5 menit, lalu maksimal 15 menit.
- MFA mempunyai counter terpisah; lima kode gagal membatalkan challenge.
- Tidak ada permanent account lock dari public input.
- Response autentikasi tetap generik untuk mencegah account enumeration.
- Counter password dibersihkan setelah full password + second-factor authentication berhasil.

## Security Audit and Alert

- Security event disimpan pada tabel append-only `platform_security_audit_events`.
- Event mencatat actor/subject, outcome, correlation/request ID, network/session context terhash, timestamp UTC, reason, dan allowlisted metadata.
- Password, secret, recovery code, cookie, token, dan raw session ID dilarang.
- Retensi awal 12 bulan; deletion hanya melalui operational prune command.
- Audit database ditulis sinkron. Email alert memakai database queue dan `afterCommit`.
- Alert dikirim kepada Platform Admin terkait dan optional configured security mailbox.
- Email failure tidak membatalkan authentication/security mutation yang sah.

## Emergency Recovery

- Hanya operator infrastruktur berwenang dengan production shell sesuai runbook.
- Identitas operator, reason, optional incident reference, OS user, hostname, environment, dan correlation ID dicatat sebagai `cli_operator_claim`.
- Klaim operator CLI bukan authentication aplikasi dan tidak menggantikan infrastructure access control.
- Command `platform:recover-access` hanya interaktif dan meminta konfirmasi eksplisit.
- Recovery:
  - menetapkan password baru melalui hidden prompt;
  - mencabut seluruh Platform session dan active challenge;
  - menghapus TOTP secret dan seluruh recovery code;
  - mengubah status menjadi `pending_mfa_setup`;
  - mewajibkan enrollment TOTP baru pada login berikutnya.
- Emergency recovery tidak menghasilkan link, TOTP secret, atau recovery code pada terminal.

## Consequences

- Authentication flow lebih banyak daripada starter kit standar, tetapi guard dan lifecycle Platform tetap dimiliki modul.
- Session manager khusus diperlukan agar pemisahan cookie/table tidak memutasi konfigurasi session global.
- Database queue worker dan scheduler menjadi kebutuhan deployment.
- Availability layanan Pwned Passwords dibutuhkan saat password ditetapkan, tetapi tidak saat login.
- Full feature verification wajib dijalankan pada MariaDB 11.4 sesuai ADR-027.
