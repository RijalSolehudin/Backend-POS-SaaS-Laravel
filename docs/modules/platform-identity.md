# Platform Identity Module

## Owns

- `PlatformUser` identity dan credential.
- Platform authentication guard/provider.
- Platform session, password reset, MFA/recovery policy, dan security events.
- Platform actor representation untuk audit.

## MVP Use Cases

- Bootstrap first Platform Administrator melalui controlled CLI.
- Authenticate Platform Administrator pada `/platform` area.
- Logout dan invalidate platform session sesuai policy.
- Manage active/disabled Platform Administrator secara berwenang.
- Recover/reset platform credential melalui controlled flow.

## Invariants

- Platform identity tidak menggunakan tenant `users` table.
- Platform user tidak memiliki tenant membership atau tenant role.
- Tenant role management tidak dapat memberikan platform privilege.
- Platform user yang membutuhkan Tenant Admin access menggunakan tenant account terpisah.
- Impersonation tidak tersedia pada MVP.
- Platform security event memiliki actor/correlation audit.
- TOTP MFA wajib dan remember-me dilarang.
- Session memiliki idle timeout 15 menit, absolute timeout 4 jam, dan maksimal dua active sessions.
- Sensitive platform action membutuhkan recent confirmation maksimal 10 menit.

## Does Not Own

- Tenant user, role, dan membership; dimiliki Identity/Tenancy.
- Tenant provisioning workflow; dimiliki Tenancy dan dipanggil melalui application boundary.

## Open Decisions

- Detail secure credential input untuk first-account bootstrap CLI.
- Operational ownership untuk emergency recovery.
