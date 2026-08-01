# P04-03 — Sellable Variants

Status: **Ready**

## Outcome

Catalog dapat mendefinisikan product dengan varian yang menghasilkan pilihan sellable di POS, misalnya ukuran small/medium/large atau hot/iced.

## Scope

- Tambahkan model variant sesuai ADR P04-01.
- Tambahkan SKU/price/currency/status untuk variant atau sellable unit.
- Pastikan POS catalog menampilkan pilihan variant dengan harga final deterministik.
- Pastikan product sederhana dari Phase 01 tetap kompatibel sebagai sellable default.
- Tambahkan admin workflow minimum untuk create/update/activate/deactivate variant.

## Out of Scope

- Modifier/add-on.
- Stock deduction.
- Recipe ownership.
- Combo/bundle.
- Promotion atau scheduled pricing.

## Dependencies

- P04-01 selesai.
- P04-02 bila variant display perlu category ordering final.

## Acceptance Criteria

- Product dapat memiliki satu atau lebih sellable variants.
- SKU unik per tenant sesuai policy ADR.
- Variant inactive tidak muncul di POS.
- Harga variant tidak bocor lintas tenant/outlet.
- Existing Sales order item snapshot tetap valid untuk product lama.

## Verification

- Feature tests untuk variant CRUD/action.
- Feature tests untuk POS catalog variant visibility.
- Architecture tests tetap lulus.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika variant semantics membutuhkan inventory SKU, recipe costing, atau parent-child product ownership lintas module.
