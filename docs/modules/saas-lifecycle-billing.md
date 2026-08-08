# [rencana] SaaS Lifecycle and Billing Module

Status: **Proposed**

Dokumen ini mendefinisikan candidate module untuk subscription lifecycle, onboarding, billing SaaS, dan entitlement. Module ini belum diimplementasikan.

## Owns

- Plan/package catalog.
- Feature entitlement dan quota.
- Subscription lifecycle tenant.
- Billing invoice untuk tenant.
- Billing payment attempt dan provider reference.
- Trial, grace period, suspension, cancellation, dan reactivation policy.
- Public or assisted onboarding intake.
- Platform billing audit trail.

## Does Not Own

- POS sales order payment milik Sales/Payments/PaymentsGateway.
- Customer QR order payment milik OrderingChannel dan PaymentsGateway.
- Tenant, outlet, membership, dan device registry tetap milik Tenancy.
- Platform administrator identity tetap milik PlatformIdentity.
- Marketing copy dan pricing page layout milik frontend/public surface.

## Candidate Statuses

| Aggregate | Status Candidate |
|---|---|
| Onboarding intake | `draft`, `submitted`, `approved`, `rejected`, `expired` |
| Subscription | `trialing`, `active`, `past_due`, `grace`, `suspended`, `cancelled`, `expired` |
| Invoice | `draft`, `issued`, `paid`, `voided`, `overdue`, `uncollectible` |
| Billing payment | `pending`, `requires_action`, `paid`, `failed`, `expired`, `cancelled`, `refunded` |
| Entitlement | `active`, `disabled` |

Status final harus dikunci dalam implementation contract sebelum coding.

## Invariants

- Tenant hanya mendapat akses fitur berdasarkan entitlement server-side.
- Suspension tidak menghapus tenant, outlet, user, sales, inventory, atau audit data.
- Billing webhook harus idempotent berdasarkan provider dan event id.
- Billing payment tidak boleh menyimpan card data.
- Invoice tidak di-hard-delete setelah diterbitkan.
- Plan change harus menghasilkan audit dan effective date yang jelas.
- Trial/grace expiration harus dapat diproses ulang tanpa menghasilkan efek ganda.
- Platform override harus memiliki actor, reason, timestamp, dan old/new state.

## Integration Points

| Module | Integration |
|---|---|
| Tenancy | Tenant dibuat/diaktifkan/dibatasi berdasarkan lifecycle yang disetujui. |
| PlatformIdentity | Platform Admin melakukan subscription operations dan override. |
| Identity | Tenant owner onboarding credential mengikuti provisioning policy. |
| PaymentsGateway | Dapat berbagi abstraction provider, tetapi billing payment perlu boundary sendiri. |
| Reporting | Subscription, invoice, MRR/ARR, dan churn report minimum. |
| Web Admin | Platform billing console, tenant subscription display, entitlement-aware navigation. |

## Open Decisions

- Module name final: `SaaSBilling`, `Subscription`, atau `Commercial`.
- Apakah billing provider sama dengan payment gateway order restoran.
- Apakah tenant dibuat sebelum atau setelah billing payment pertama.
- Apakah unpaid tenant masih bisa login dengan limited mode.
- Apakah feature gating dilakukan per route, per action, atau policy service.
- Apakah quota dihitung hard limit atau soft warning.
