# P04-08 — Catalog Expansion Readiness

Status: **Planned**

## Outcome

Phase 04 siap dinyatakan selesai berdasarkan evidence POS catalog, order snapshot, admin operations, dan regression test Phase 02/03.

## Scope

- End-to-end readiness matrix untuk product sederhana, variant, modifier required/optional, outlet price override, dan inactive availability.
- Reconciliation checklist untuk order total, receipt total, shift summary, daily sales, refund, dan void setelah catalog berubah.
- Historical immutability checklist untuk order completed sebelum/ setelah catalog update.
- Admin checklist untuk create/update/deactivate category/product/variant/modifier.
- Final status update untuk roadmap dan execution docs.

## Out of Scope

- New Phase 05 inventory capability.
- Customer-facing release notes.
- External catalog import integration.
- Promotion/tax/service calculation beyond ADR-approved baseline.

## Dependencies

- P04-02 selesai.
- P04-03 selesai.
- P04-04 selesai.
- P04-05 selesai.
- P04-06 selesai.
- P04-07 selesai.

## Acceptance Criteria

- POS catalog dapat menampilkan valid product/variant/modifier per outlet.
- Order item snapshot menyimpan semua pilihan yang memengaruhi harga.
- Perubahan catalog tidak mengubah transaksi historis.
- Sales regression Phase 02/03 tetap lulus.
- Catalog expanded tidak bocor lintas tenant/outlet.
- Phase 04 roadmap status diperbarui sesuai evidence.

## Verification

- Catalog suite lulus.
- Sales suite lulus.
- `composer quality` lulus.
- `npm run build` lulus.
- Manual/demo checklist documented untuk product owner acceptance.

## Architecture Stop Rule

Berhenti jika readiness membutuhkan inventory deduction, recipe costing, kitchen routing, payment gateway, or accounting/tax compliance.
