# ADR-038: Operational Safety MVP Policy

- Status: Proposed
- Date: 2026-08-01

## Context

Phase 02 menghasilkan alur POS end-to-end: cashier membuka shift, membuat order, mencatat payment, menerbitkan receipt, menutup shift, dan melihat ringkasan harian. Phase 03 perlu menambah kontrol operasional agar sistem layak dipakai pilot outlet untuk transaksi riil.

Roadmap Phase 03 membutuhkan keputusan tentang sensitive action approval, refund/reversal, audit retention, locking/isolation transaksi kritis, serta deployment, backup, monitoring, queue, dan cache baseline.

## Proposed Decision

### Sensitive Action Approval

- Sensitive actions Phase 03:
  - void completed order;
  - refund or reverse payment;
  - close shift with discrepancy above configured tolerance;
  - cash out above configured threshold;
  - device revocation from tenant admin.
- Default approval model adalah supervisor approval dengan actor berbeda dari cashier pelaksana.
- Re-authentication cashier hanya dipakai untuk confirmation step ringan dan tidak menggantikan supervisor approval pada aksi finansial.
- Setiap approval menyimpan approver actor, performer actor, reason, timestamp, outlet, tenant, target resource, dan correlation ID.

### Refund and Reversal

- Refund Phase 03 adalah manual operational record, bukan gateway settlement.
- Refund selalu mereferensikan original payment dan original order.
- Refund amount tidak boleh melebihi refundable amount tersisa.
- Full refund menandai order sebagai financially reversed, tetapi order history, payment, dan receipt snapshot tetap immutable.
- Partial refund boleh dicatat bila product owner menyetujui; jika tidak, Phase 03 hanya full refund.

### Cash Movement and Shift Discrepancy

- Cash in/out dicatat sebagai shift-scoped cash movement.
- Cash movement membutuhkan reason dan actor.
- Shift close menghitung expected cash dari opening float, cash sales, cash in, cash out, dan counted cash.
- Discrepancy disimpan sebagai snapshot dan wajib reason bila tidak nol.
- Discrepancy di atas tolerance membutuhkan supervisor approval.

### Audit and Retention

- Financial audit event bersifat append-only.
- Audit metadata tidak boleh menyimpan password, token, recovery code, full card data, atau request body mentah yang berisi secret.
- Pilot retention minimum:
  - financial audit event: 2 tahun;
  - operational security event: 1 tahun;
  - idempotency record: minimal 24 jam;
  - application logs: mengikuti deployment retention policy.
- Redaction key sensitif mengikuti policy Phase 01 dan diperluas untuk payment/refund metadata.

### Concurrency and Recovery

- Mutasi finansial memakai database transaction.
- Row lock digunakan pada order, payment, shift, sequence, dan cash movement aggregate yang sedang dimutasi.
- Idempotency wajib untuk refund/reversal, cash movement, close shift, void order, dan approval submission.
- Failure setelah payment/refund record tersimpan harus dapat ditemukan lewat reconciliation query dan tidak boleh membuat duplicate mutation saat retry.
- Critical recovery procedure didokumentasikan sebelum pilot.

### Operational Baseline

- Scheduler wajib mencakup token pruning dan audit/idempotency maintenance.
- Queue baseline harus menentukan driver pilot, failed job handling, retry policy, dan worker supervision.
- Cache baseline harus mendukung atomic lock bila digunakan oleh command atau job kritis.
- Backup/restore rehearsal wajib dilakukan pada database testing/staging sebelum pilot.
- Monitoring minimum mencakup failed jobs, scheduler freshness, database connectivity, queue depth, exception rate, dan storage pressure.

## Consequences

- Phase 03 menguatkan kontrol operasional tanpa langsung mengikat sistem ke payment gateway atau accounting system.
- Supervisor approval menambah friction pada aksi tertentu, tetapi memperjelas accountability.
- Manual refund/reversal menjaga MVP tetap sederhana, namun contract harus dibuat additive agar gateway settlement dapat ditambahkan nanti.
- Cash movement memperkaya shift summary dan reconciliation, sehingga perlu test financial totals tambahan.

## Open Questions

- Apakah partial refund masuk Phase 03 atau ditunda?
- Berapa nilai default cash discrepancy tolerance?
- Role mana yang boleh menjadi supervisor approver pada pilot?
- Apakah backup/restore rehearsal cukup lewat runbook manual atau perlu command otomatis sejak Phase 03?
- Queue driver pilot memakai database, Redis, atau sync dengan batasan tertentu?
