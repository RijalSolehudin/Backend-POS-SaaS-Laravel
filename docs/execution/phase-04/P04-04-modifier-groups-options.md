# P04-04 — Modifier Groups and Options

Status: **Ready**

## Outcome

Catalog mendukung modifier group dan option agar POS dapat menjual item dengan add-on atau pilihan wajib tanpa pricing ambigu.

## Scope

- Tambahkan modifier group dengan required/optional, min/max selection, dan display order.
- Tambahkan modifier option dengan price delta, currency, status, dan display order.
- Hubungkan modifier group ke product/variant sesuai ADR.
- Pastikan POS catalog menampilkan rules modifier secara deterministik.
- Tambahkan validasi agar min/max dan required rules konsisten.

## Out of Scope

- Nested modifier.
- Conditional modifier.
- Modifier yang mengurangi stock.
- Kitchen routing.
- Promotion/discount.

## Dependencies

- P04-01 selesai.
- P04-03 selesai.

## Acceptance Criteria

- Required modifier wajib dipilih pada order flow.
- Optional modifier mengikuti min/max.
- Price delta dihitung dalam minor unit dan currency tenant.
- Inactive modifier group/option tidak muncul di POS.
- Tenant/outlet isolation diuji.

## Verification

- Feature tests untuk modifier group/option configuration.
- Feature tests untuk POS catalog modifier rules.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika modifier membutuhkan nested options, dynamic pricing, inventory ingredient deduction, atau kitchen instructions.
