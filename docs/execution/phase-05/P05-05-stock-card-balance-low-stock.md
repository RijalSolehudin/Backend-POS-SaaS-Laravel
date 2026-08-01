# P05-05 — Stock Card, Balance, and Low Stock

Status: **Done**

## Outcome

User dan developer dapat membaca stock card, current balance, minimum valuation, dan low-stock state untuk setiap item outlet.

## Scope

- Tambahkan endpoint/view stock card per item/outlet.
- Tambahkan current balance read model.
- Tambahkan low-stock threshold per item/outlet.
- Tambahkan valuation minimum sesuai costing method ADR.
- Tambahkan filter tanggal dan source movement.
- Pastikan stock card dapat menjelaskan balance dari ledger.

## Out of Scope

- Forecasting.
- Purchase recommendation.
- Accounting export.
- Report dashboard kompleks.

## Dependencies

- P05-03 selesai.
- P05-04 selesai bila adjustment/waste perlu muncul di stock card.

## Acceptance Criteria

- Stock card menampilkan opening, in, out, adjustment, waste, dan transfer movement bila sudah tersedia.
- Current balance cocok dengan total movement ledger.
- Low-stock state muncul berdasarkan threshold outlet.
- Valuation minimum konsisten dengan costing method ADR.
- Cross-tenant/outlet data tidak bocor.

## Implementation Contract

- Ikuti [Phase 05 Implementation Contract](implementation-contract.md).
- Buat action/query `GetStockCard`, `GetInventoryBalance`, dan `ListLowStockItems`.
- Stock card membaca `inventory_stock_movements`, bukan menghitung dari audit event.
- Current balance membaca `inventory_balances`.
- Low-stock memakai `inventory_item_outlet_settings.low_stock_threshold_quantity`.
- Response stock card wajib menampilkan `balance_quantity_after` dan `balance_total_cost_minor_after` dari movement.
- Jika P05-06 belum selesai, transfer movement boleh belum muncul di test P05-05; P05-06 wajib menambah coverage transfer pada stock card/regression.
- `in_transit_quantity` ditampilkan setelah P05-06 tersedia.

## Verification

- Feature tests stock card.
- Balance reconciliation tests.
- Low-stock tests.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika valuation membutuhkan FIFO layer detail, landed cost, atau accounting journal yang belum disetujui.
