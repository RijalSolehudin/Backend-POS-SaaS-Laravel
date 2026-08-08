# [rencana] P10-07: Server-side Feature Gate and Quota Enforcement

Status: **Planned**
Layer: **Backend**

## Outcome

Fitur dan quota tenant dibatasi server-side berdasarkan entitlement subscription.

## Scope

- Feature gate service.
- Middleware/policy/action guard sesuai keputusan ADR.
- Quota check untuk resource terpilih.
- Limited mode untuk tenant suspended bila disetujui.
- Error code/API response untuk entitlement failure.

## Acceptance Criteria

- Frontend visibility bukan satu-satunya enforcement.
- Tenant tanpa entitlement tidak dapat melakukan mutation fitur gated.
- Error response konsisten dan tidak membocorkan plan tenant lain.
- Critical existing flows tetap aman untuk tenant active.

## Out of Scope

- Complex usage billing per transaction.
- Client-side-only gating.
