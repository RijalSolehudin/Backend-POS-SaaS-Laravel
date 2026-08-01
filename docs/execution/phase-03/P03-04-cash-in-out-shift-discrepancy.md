# P03-04 — Cash In/Out and Shift Discrepancy

Status: **Ready**

## Outcome

Cashier dapat mencatat cash movement selama shift dan close shift dapat menjelaskan discrepancy antara expected cash dan counted cash.

## Scope

- Cash in/out record yang scoped ke tenant, outlet, shift, dan actor.
- Reason wajib untuk setiap cash movement.
- Supervisor approval untuk cash out di atas threshold.
- Idempotency untuk cash movement submission.
- Shift summary menghitung opening cash, cash sales, cash refunds, cash in, cash out, expected cash, counted cash, dan variance.
- Audit event untuk cash movement recorded dan close shift discrepancy.

## Out of Scope

- Petty cash category management.
- Cash drawer hardware integration.
- Accounting export.
- Multi-currency drawer.

## Dependencies

- P03-02 selesai.
- P03-03 selesai.
- [ADR-038 Operational Safety MVP Policy](../../architecture/decisions/038-operational-safety-mvp-policy.md) accepted.

## Acceptance Criteria

- Cash movement hanya dapat dibuat pada open shift milik cashier/outlet terkait.
- Cash in tidak membutuhkan supervisor approval.
- Cash out di atas threshold membutuhkan approval valid untuk action `cash_movements.cash_out`.
- Retry dengan idempotency key dan fingerprint sama mengembalikan cash movement pertama.
- Retry dengan key sama tetapi fingerprint berbeda menghasilkan `409`.
- Close shift menyimpan expected cash dan variance snapshot yang memperhitungkan cash movement dan cash refund.
- Semua cash movement dan discrepancy menghasilkan sales audit event non-secret.

## Verification

- Feature tests untuk cash in/out happy path.
- Feature tests untuk cash out approval, missing approval, threshold, closed shift rejection, and idempotency conflict.
- Shift summary tests untuk expected cash dan variance.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika cash movement membutuhkan category custom, drawer hardware, multi-currency, atau approval bertingkat.
