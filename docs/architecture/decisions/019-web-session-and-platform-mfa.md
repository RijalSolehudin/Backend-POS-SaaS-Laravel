# ADR-019: Web Session and Platform MFA Policy

- Status: Accepted
- Date: 2026-07-24

## Context

Tenant Web Admin membutuhkan session yang nyaman untuk pekerjaan back-office, sedangkan Platform Admin memiliki privilege lintas tenant dan membutuhkan session serta recovery policy yang lebih ketat. Keduanya telah dipisahkan secara identity dan route boundary.

## Shared Decisions

- Session disimpan pada database untuk MVP.
- Tenant Admin dan Platform Admin menggunakan cookie/session namespace terpisah.
- Cookie menggunakan `Secure`, `HttpOnly`, dan `SameSite=Lax`, serta hanya dikirim melalui HTTPS.
- Session ID diregenerasi setelah login dan privilege/security change.
- Logout menggunakan state-changing request, menginvalidasi server-side session, dan menghapus cookie area terkait.
- Remember-me tidak digunakan pada MVP.
- Idle dan absolute timeout ditegakkan server-side.
- Passive background polling tidak memperpanjang idle timeout.
- User/tenant/platform status tetap diverifikasi agar disabled identity tidak menunggu timeout.

## Tenant Web Admin Policy

- Idle timeout: 30 menit.
- Absolute timeout: 8 jam.
- MFA optional pada MVP dan dianjurkan untuk Tenant Owner.
- Lebih dari satu active session diperbolehkan.
- User dapat melihat dan mencabut active session bila capability tersedia.
- Password reset menggunakan generic response dan single-use email link dengan expiry 30 menit.
- Password reset berhasil mencabut seluruh Tenant Web Admin session dan Sanctum token user.

## Platform Admin Policy

- Idle timeout: 15 menit.
- Absolute timeout: 4 jam.
- TOTP MFA wajib.
- MFA setup harus dikonfirmasi sebelum account dianggap siap digunakan.
- Recovery codes dibuat, ditampilkan secara aman, single-use, dan dapat diregenerasi dengan membatalkan set sebelumnya.
- Maksimal dua active sessions.
- Sensitive platform action membutuhkan recent password/MFA confirmation yang berlaku 10 menit.
- Platform login failure memiliki rate limit, escalating cooldown, audit, dan security alert policy.
- Email reset link saja tidak cukup menyelesaikan recovery; MFA/recovery code tetap diperlukan.
- Jika password dan MFA sama-sama hilang, recovery dilakukan melalui controlled emergency CLI.
- Emergency recovery mencabut seluruh platform sessions, recovery codes, dan credential recovery state sebelumnya.

## Sensitive Platform Actions

Recent confirmation diperlukan setidaknya untuk:

- Provision atau menonaktifkan tenant.
- Membuat/menonaktifkan Platform Administrator.
- Reset initial owner credential.
- Emergency/security recovery.
- Mengubah platform security configuration.

## Logout and Revocation

- Logout Platform Admin tidak otomatis logout Tenant Admin dan sebaliknya.
- Password/security reset dapat mencabut seluruh session untuk identity terkait.
- Disabling user, platform user, tenant, atau membership menghentikan effective access pada request berikutnya.
- Logout, timeout, reset, dan forced revocation menghasilkan security/audit event yang relevan.

## Login and Recovery Safety

- Login dan reset request menggunakan generic response agar tidak mengungkap keberadaan account.
- Login throttling di-scope menggunakan normalized identifier dan network signal yang sesuai.
- Permanent hard lock berdasarkan input penyerang dihindari; cooldown dan alert digunakan.
- Security questions tidak digunakan.
- Secret, password, TOTP secret, recovery code, dan reset token tidak dicatat pada log.

## Consequences

- Platform Admin memiliki friction lebih tinggi yang sebanding dengan privilege lintas tenant.
- Custom middleware/policy diperlukan untuk absolute timeout dan recent-confirmation window.
- Separate cookies memungkinkan kedua area digunakan pada browser yang sama tanpa mencampur logout/session state.
- Database session memprioritaskan simplicity untuk user count rendah; perpindahan ke Redis membutuhkan operational review.

