# P05-07 — Inventory Reconciliation and Recovery

Status: **Ready**

## Outcome

Developer/operator memiliki alat untuk mendeteksi balance inventory yang tidak konsisten dengan ledger dan tahu tindakan pemulihannya.

## Scope

- Tambahkan command recovery/check untuk membandingkan balance read model dengan ledger.
- Tambahkan report discrepancy per tenant/outlet/item.
- Tambahkan runbook recovery manual.
- Tambahkan observability minimum untuk failed inventory mutation.
- Pastikan command aman dibaca dan tidak menulis kecuali explicit repair policy disetujui.

## Out of Scope

- Auto-repair tanpa approval.
- Accounting reconciliation.
- Supplier reconciliation.
- Inventory audit mobile app.

## Dependencies

- P05-03 selesai.
- P05-04 selesai.
- P05-05 selesai.
- P05-06 selesai bila transfer masuk reconciliation.

## Acceptance Criteria

- Recovery check exit code `0` saat ledger dan balance konsisten.
- Recovery check exit code non-zero saat discrepancy ditemukan.
- Output menunjukkan tenant/outlet/item dan selisih yang bisa ditindak.
- Command tidak membuka data lintas tenant tanpa parameter/policy yang jelas.
- Runbook menjelaskan langkah investigasi.

## Implementation Contract

- Ikuti [Phase 05 Implementation Contract](implementation-contract.md).
- Buat command `inventory:recovery-check`.
- Command default wajib read-only.
- Command menerima optional `--tenant=`, `--outlet=`, dan `--item=` filter.
- Check membandingkan `inventory_balances.quantity` dengan sum ledger quantity per tenant/outlet/item.
- Check membandingkan `inventory_balances.total_cost_minor` dengan replay cost memakai formula moving average dari implementation contract.
- Check melaporkan transfer `dispatched` yang belum `received` sebagai in transit, bukan discrepancy.
- Exit code `0` jika tidak ada discrepancy.
- Exit code `1` jika ada discrepancy yang harus ditindak.
- Jangan implement auto-repair pada Phase 05.

## Verification

- Feature/console tests recovery check.
- Failure scenario tests.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika recovery membutuhkan automated repair, accounting adjustment, atau data migration destructive yang belum disetujui.
