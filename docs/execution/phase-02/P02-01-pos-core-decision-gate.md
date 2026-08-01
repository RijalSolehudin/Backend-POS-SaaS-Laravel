# P02-01 — POS Core Decision Gate

Status: **Done**

## Outcome

Keputusan arsitektur wajib Phase 02 disetujui, dicatat, dan diterjemahkan menjadi acceptance criteria implementasi POS Core.

## Scope

- Order lifecycle dan transition.
- Shift lifecycle.
- Calculation order untuk subtotal/total.
- Payment method dan lifecycle MVP.
- Idempotency contract.
- Business order numbering.
- Transaction boundary Sales/Payments.
- Receipt minimum requirements.

## Out of Scope

- Implementasi database/model/order/payment.
- Payment gateway.
- Inventory deduction.
- Tax/discount/service charge jika tidak disetujui untuk Phase 02.

## Dependencies

- Phase 01 Foundation selesai.
- [ADR-037 POS Core MVP Policy](../../architecture/decisions/037-pos-core-mvp-policy.md) disetujui product owner.

## Implementation Checklist

- [x] Review ADR-037 dengan product owner.
- [x] Ubah ADR-037 menjadi `Accepted` atau revisi sesuai keputusan.
- [x] Update Phase 02 roadmap status menjadi `Ready`.
- [x] Update work package P02-02 sampai P02-09 bila keputusan berubah.
- [x] Catat acceptance scenario dan failure path minimum.

## Verification and Evidence

- ADR-037 berstatus `Accepted`.
- Phase 02 execution plan disetujui.
- Tidak ada open decision yang menghalangi P02-02.
