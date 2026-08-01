# P04-05 — Outlet Availability and Price Overrides

Status: **Planned**

## Outcome

Tenant admin dapat mengatur availability dan price override per outlet untuk product/variant/modifier tanpa memengaruhi outlet lain.

## Scope

- Perluas availability dari product-level ke sellable unit dan modifier option sesuai ADR.
- Tambahkan outlet-specific price override untuk variant/sellable dan modifier option bila disetujui.
- Pastikan fallback price jelas: outlet override lalu base price.
- Tambahkan admin workflow minimum untuk set availability dan price override.
- Pastikan POS catalog outlet hanya menerima item yang available.

## Out of Scope

- Scheduled pricing.
- Happy hour/daypart menu.
- Bulk import price list.
- Multi-currency.

## Dependencies

- P04-03 selesai.
- P04-04 selesai.

## Acceptance Criteria

- Availability per outlet tidak bocor lintas outlet/tenant.
- Price override null berarti fallback ke base price.
- Override currency harus sama dengan tenant/product currency.
- POS catalog mengembalikan harga final yang sudah resolved.
- Historical order snapshot tidak berubah saat override berubah.

## Verification

- Feature tests untuk availability dan price override.
- Regression tests untuk existing product outlet availability.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika price override berubah menjadi scheduled pricing, promotion engine, atau multi-currency policy.
