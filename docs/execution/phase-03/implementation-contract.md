# Phase 03 Implementation Contract

Dokumen ini mengunci detail teknis Phase 03 Operational Safety agar work package P03-01 sampai P03-08 dapat dipahami tanpa membuat ulang keputusan approval, refund, audit, recovery, atau operational baseline.

## Module Ownership

- Operational safety Phase 03 memperluas module `Sales` dan menggunakan authority dari `Tenancy`/`Identity` untuk approval actor.
- Audit finansial berada di `sales_audit_events`.
- Approval finansial berada di `sales_sensitive_action_approvals`.
- Cash movement berada di `sales_cash_movements`.
- Refund manual berada di `sales_refunds`.

## Tables

Gunakan table berikut sebagai tambahan Phase 03:

- `sales_sensitive_action_approvals`
- `sales_refunds`
- `sales_cash_movements`
- `sales_audit_events`

Jangan mengubah receipt, payment, atau order historis untuk merepresentasikan refund. Refund adalah record tambahan.

## Sensitive Action Approval

Approval default adalah supervisor approval dengan actor berbeda dari performer.

Sensitive actions Phase 03:

- void completed order;
- full manual refund;
- cash out above threshold;
- close shift with discrepancy above tolerance;
- device revocation from tenant admin.

Approval wajib:

- tenant/outlet scoped;
- target type dan target id scoped;
- memiliki request fingerprint;
- single-use;
- punya expiry;
- mencatat requester/performer, approver, reason, status, dan timestamp.

Approver minimum adalah Tenant Owner atau Outlet Manager aktif pada tenant/outlet terkait.

## Refund And Reversal

- Phase 03 hanya full manual refund.
- Refund selalu mereferensikan original payment dan original order.
- Refund amount/currency harus cocok dengan original payment untuk full refund.
- Refund tidak mengubah original payment dan receipt snapshot.
- Refund membuat record `sales_refunds` dan audit event.
- Duplicate refund ditolak.
- Payment gateway settlement tidak masuk Phase 03.

## Cash Movement And Shift Discrepancy

- Cash in/out shift-scoped.
- Reason wajib untuk semua cash movement.
- Cash out di atas threshold membutuhkan approval.
- Default cash discrepancy tolerance adalah `0` minor unit.
- Close shift menyimpan expected cash, counted cash, variance, dan reason bila variance tidak nol.
- Close shift dengan discrepancy di atas tolerance membutuhkan approval.

## Audit And Redaction

- `sales_audit_events` append-only secara application policy.
- Audit event wajib menyimpan actor, tenant, outlet bila applicable, target, outcome, reason bila applicable, correlation ID, dan metadata.
- Metadata audit wajib direduksi dari password, token, recovery code, full card data, dan secret/request body mentah.
- Financial audit retention minimum 2 tahun.
- Operational security event retention minimum 1 tahun.

## Concurrency And Recovery

- Mutasi finansial wajib database transaction.
- Gunakan row lock pada order, payment, shift, approval, cash movement aggregate, dan idempotency record yang sedang dimutasi.
- Refund/reversal, cash movement, close shift, void order, dan approval submission wajib idempotent.
- Recovery command minimum: `sales:recovery-check`.
- Recovery check read-only dan melaporkan state ambigu, missing approval consumed, dan missing idempotency resource.

## Operational Baseline

- Scheduler mencakup Sanctum token pruning, platform/session maintenance, dan Sales audit/idempotency maintenance.
- Queue pilot default adalah database queue.
- Cache default harus mendukung atomic lock bila dipakai oleh job/command kritis.
- Backup/restore rehearsal dicatat dalam runbook sebelum pilot.
- Monitoring minimum: failed jobs, scheduler freshness, database connectivity, queue depth, exception rate, storage pressure, dan recovery check.

## Testing Baseline

Setiap work package Phase 03 wajib memiliki:

- feature tests approval happy/failure path;
- idempotency replay/conflict;
- cross-tenant/outlet/user rejection;
- audit metadata redaction;
- recovery command tests bila menambah state kritis;
- `composer quality`.

Jalankan `npm run build` bila mengubah frontend asset.

## Stop Rule

Berhenti jika implementasi membutuhkan offline sync, payment gateway settlement, inventory deduction, accounting export, custom approval workflow, multi-currency, external observability platform berbayar, atau partial refund.
