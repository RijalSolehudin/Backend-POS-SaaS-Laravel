# Phase 03 Pilot Readiness

Dokumen ini adalah gate terakhir sebelum outlet pilot memakai sistem untuk transaksi riil. Checklist ini belum menggantikan sign-off product owner, tetapi memastikan evidence teknis Phase 03 sudah lengkap.

## Demo Path

Gunakan data tenant, outlet, device, cashier, supervisor, dan catalog yang aktif.

| Step | Scenario | Expected result |
|---:|---|---|
| 1 | POS login | token aktif untuk device/outlet terdaftar |
| 2 | Catalog read | produk outlet muncul konsisten |
| 3 | Open shift | shift `open`, opening cash tercatat |
| 4 | Create draft order | order number unik, retry key sama mengembalikan order sama |
| 5 | Add/update/remove item | total order berubah konsisten |
| 6 | Complete payment | order `completed`, payment `recorded`, receipt snapshot immutable |
| 7 | Request approval for void | approval `pending` lalu `approved` oleh supervisor berbeda |
| 8 | Void completed order | order `voided`, approval `consumed`, audit tercatat |
| 9 | Complete second order | order baru selesai untuk refund scenario |
| 10 | Request approval for refund | approval cocok dengan order/payment/reason |
| 11 | Full refund | refund `recorded`, original payment dan receipt tidak berubah |
| 12 | Cash in | cash movement tercatat tanpa approval |
| 13 | Cash out above threshold | approval required, lalu consumed setelah movement tercatat |
| 14 | Close shift with variance | counted cash, expected cash, variance, dan reason tersimpan |
| 15 | Daily sales | gross sales, refunds, net sales, cash movement, dan variance dapat dijelaskan |

## Reconciliation Checklist

Pilot dinyatakan siap bila operator dapat menjelaskan:

- gross sales dari completed non-voided orders;
- refunds dari manual full refund records;
- net sales = gross sales minus refunds;
- expected cash = opening cash + cash payments - cash refunds + cash in - cash out;
- counted cash dari close shift;
- variance = counted cash minus expected cash;
- daily sales cocok dengan shift summary untuk tanggal bisnis yang sama.

## Sensitive Action Checklist

| Action | Control | Pass condition |
|---|---|---|
| Void completed order | supervisor approval | approval valid, performer cocok, consumed satu kali |
| Full refund | supervisor approval | amount/currency cocok original payment, refund tidak duplicate |
| Cash out above threshold | supervisor approval | movement tercatat setelah approval consumed |
| Shift discrepancy | reason wajib | audit event `shift.discrepancy.recorded` tersedia |
| Device revocation | tenant admin control | token lama ditolak dan audit tenancy tersedia |

## Auditability Checklist

Setiap aksi sensitif harus memiliki:

- actor atau performer;
- approver bila approval required;
- tenant dan outlet;
- target type dan target ID;
- reason;
- outcome;
- timestamp;
- correlation ID atau `X-Request-ID`;
- audit metadata tanpa secret.

## Failure And Recovery Checklist

| Scenario | Expected result |
|---|---|
| Retry idempotency key sama dan payload sama | resource sama dikembalikan, tidak ada duplicate mutation |
| Retry idempotency key sama dan payload berbeda | error `IDEMPOTENCY_CONFLICT` |
| Approval expired | mutation ditolak |
| Approval consumed dipakai ulang | error `APPROVAL_ALREADY_CONSUMED` |
| Closed shift menerima order/cash movement baru | mutation ditolak |
| Recovery check setelah demo | `php artisan sales:recovery-check` exit code `0` |

## Operational Readiness Checklist

| Area | Evidence |
|---|---|
| Scheduler | `php artisan schedule:list` menampilkan Sanctum prune, platform prune, dan Sales audit prune |
| Queue | database queue worker runbook tersedia |
| Cache | `CACHE_STORE=database` untuk atomic locks |
| Backup/restore | rehearsal documented dengan expected output dan rollback note |
| Monitoring | checklist manual tersedia untuk DB, scheduler, failed jobs, queue depth, logs, storage, dan Sales recovery |
| Load baseline | skenario POS critical path dan pass/fail threshold tersedia |

## Evidence Links

- [P03-02 Sensitive Action Approval](../execution/phase-03/P03-02-sensitive-action-approval.md)
- [P03-03 Refund and Payment Reversal](../execution/phase-03/P03-03-refund-payment-reversal.md)
- [P03-04 Cash In/Out and Shift Discrepancy](../execution/phase-03/P03-04-cash-in-out-shift-discrepancy.md)
- [P03-05 Audit Trail Hardening](../execution/phase-03/P03-05-audit-trail-hardening.md)
- [P03-06 Concurrency and Recovery Hardening](../execution/phase-03/P03-06-concurrency-recovery-hardening.md)
- [P03-07 Operational Baseline](../execution/phase-03/P03-07-operational-baseline.md)
- [POS Device and API Operations](pos-device-and-api-operations.md)
- [Sales Retry and Recovery](sales-retry-and-recovery.md)
- [Operational Baseline](operational-baseline.md)

## Product Owner Sign-Off

Sebelum transaksi riil, product owner perlu menjalankan demo path dan menandai:

- cashier flow diterima;
- supervisor approval flow diterima;
- reconciliation output dipahami operator;
- recovery procedure dipahami developer/operator;
- known limitation Phase 03 diterima: belum ada offline sync, payment gateway settlement, inventory deduction, dan accounting export.
