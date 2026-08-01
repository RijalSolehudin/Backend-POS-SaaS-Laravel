# P06-07 — Goods Receipt and Purchase Return

Status: **Planned**

## Outcome

Barang diterima dari PO dan return pembelian dapat direkonsiliasi ke Inventory ledger.

## Scope

- Buat goods receipt header/line.
- Partial receipt diizinkan.
- Over-receipt ditolak.
- Goods receipt membuat Inventory inbound movement.
- Purchase return membuat outbound movement dengan reason.

## Implementation Contract

- Ikuti [Phase 06 Implementation Contract](implementation-contract.md).
- Received PO total memperbarui status PO menjadi partially_received atau received.
- Return tidak boleh melebihi received quantity tersisa.

## Verification

- Partial/full receipt tests.
- Over-receipt rejection tests.
- Purchase return tests.
- Inventory reconciliation tests.
- `composer quality`.
