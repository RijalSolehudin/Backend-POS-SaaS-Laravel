# Phase 05 Implementation Contract

Dokumen ini mengunci detail teknis Phase 05 agar setiap work package dapat diimplementasikan tanpa membuat keputusan bisnis/arsitektur baru.

## Module Structure

Inventory wajib mengikuti struktur module yang sudah dipakai Catalog dan Sales:

- `app/Modules/Inventory/Application/Actions`
- `app/Modules/Inventory/Application/Data`
- `app/Modules/Inventory/Application/Exceptions`
- `app/Modules/Inventory/Application/Services`
- `app/Modules/Inventory/Domain/Enums`
- `app/Modules/Inventory/Domain/Models`
- `app/Modules/Inventory/Infrastructure/Persistence/Migrations`
- `app/Modules/Inventory/Infrastructure/Providers`
- `app/Modules/Inventory/Presentation/Console/Commands`
- `app/Modules/Inventory/Presentation/Http`
- `app/Modules/Inventory/Presentation/Resources/views`

Provider wajib diregistrasikan eksplisit seperti module lain.

## Tables

Gunakan nama tabel berikut:

| Table | Owner work package | Purpose |
|---|---|---|
| `inventory_units` | P05-02 | Unit dasar tenant-scoped |
| `inventory_items` | P05-02 | Barang fisik yang dihitung stoknya |
| `inventory_item_outlet_settings` | P05-02 | Minimum stock dan status item per outlet |
| `inventory_audit_events` | P05-02 | Audit event module Inventory |
| `inventory_balances` | P05-03 | Current balance projection yang dikunci saat mutation |
| `inventory_stock_movements` | P05-03 | Ledger immutable sumber kebenaran audit |
| `inventory_idempotency_records` | P05-03 | Idempotency record module Inventory |
| `inventory_transfers` | P05-06 | Header transfer antar outlet |
| `inventory_transfer_lines` | P05-06 | Item dan quantity transfer |

Gunakan ULID `CHAR(26)` ASCII binary untuk foreign key domain, mengikuti pola module lain.

## Quantity And Money

- Quantity selalu decimal fixed precision `decimal(15, 3)`.
- Request quantity diterima sebagai string decimal, misalnya `"1.000"` atau `"12.500"`.
- Jangan memakai `float` untuk quantity.
- Satu inventory item hanya memiliki satu base unit.
- Money tetap memakai minor unit integer.
- Balance menyimpan `total_cost_minor` integer dan `currency`.
- Average cost adalah nilai turunan dari `total_cost_minor / quantity`, bukan sumber kebenaran terpisah.

## Costing Formula

Gunakan moving average berbasis total cost:

- Inbound: `new_quantity = old_quantity + inbound_quantity`.
- Inbound: `new_total_cost_minor = old_total_cost_minor + inbound_total_cost_minor`.
- Outbound: `outbound_total_cost_minor = round(abs(outbound_quantity) / old_quantity * old_total_cost_minor)`.
- Outbound: `new_total_cost_minor = old_total_cost_minor - outbound_total_cost_minor`.
- Jika `new_quantity` menjadi `0.000`, set `new_total_cost_minor = 0`.
- Outbound tidak boleh berjalan jika `old_quantity < abs(outbound_quantity)`.
- Movement menyimpan `quantity`, `unit_cost_minor`, `total_cost_minor`, `balance_quantity_after`, dan `balance_total_cost_minor_after`.

Opening balance dan inbound movement harus membawa `total_cost_minor`. `unit_cost_minor` boleh disimpan sebagai display/reference, tetapi calculation utama memakai `total_cost_minor`.

## Enums

Gunakan enum berikut kecuali work package eksplisit menambahkan nilai baru:

| Enum | Values |
|---|---|
| `InventoryStatus` | `active`, `inactive` |
| `StockMovementType` | `opening_balance`, `adjustment_increase`, `adjustment_decrease`, `waste`, `transfer_out`, `transfer_in`, `reversal` |
| `TransferStatus` | `draft`, `requested`, `approved`, `dispatched`, `received`, `cancelled` |

## Idempotency

Inventory memiliki tabel idempotency sendiri, bukan memakai `sales_idempotency_records`.

Scope unique idempotency:

- `tenant_id`
- `outlet_id`
- `user_id`
- `action`
- `idempotency_key`

Payload hash wajib memakai JSON canonical dari input yang memengaruhi mutation. Retry dengan hash sama mengembalikan resource yang sama. Retry dengan hash berbeda mengembalikan `IDEMPOTENCY_CONFLICT`.

## Approval Policy

Pakai pola approval Phase 03.

Default config:

- `inventory.approval.adjustment_decrease_quantity_threshold = "0.000"`
- `inventory.approval.waste_quantity_threshold = "0.000"`
- `inventory.approval.transfer_quantity_threshold = "0.000"`

Artinya, secara default semua adjustment decrease, waste, dan transfer dengan quantity positif membutuhkan approval. Threshold lebih besar dapat ditambahkan lewat config, tetapi tenant setting UI tidak masuk Phase 05.

## Transfer In Transit

Saat transfer `dispatched`, stock source outlet berkurang melalui movement `transfer_out`. Selama belum `received`, quantity tersebut dilaporkan sebagai `in_transit_quantity` dari tabel transfer, bukan sebagai balance outlet.

Saat transfer `received`, destination outlet bertambah melalui movement `transfer_in`. Partial receive dan variance tidak masuk Phase 05.

## Opening Balance Policy

Opening balance hanya boleh satu kali per tenant/outlet/item.

Jika opening balance sudah pernah dicatat, koreksi berikutnya wajib memakai adjustment increase/decrease. Validasi ini wajib dijaga oleh application action dan feature test.

## Business Error Codes

Gunakan error code berikut secara konsisten:

| Code | When |
|---|---|
| `INVENTORY_UNIT_NOT_FOUND` | Unit tidak ditemukan dalam tenant |
| `INVENTORY_ITEM_NOT_FOUND` | Item tidak ditemukan dalam tenant |
| `INVENTORY_ITEM_INACTIVE` | Item inactive dipakai untuk mutation baru |
| `INVENTORY_CROSS_TENANT_REFERENCE` | Referensi tenant lain dipakai |
| `INVENTORY_UNIT_MISMATCH` | Movement memakai unit selain base unit item |
| `INVENTORY_CURRENCY_MISMATCH` | Cost/currency tidak cocok tenant |
| `INVENTORY_INSUFFICIENT_STOCK` | Mutation outbound melebihi balance |
| `INVENTORY_OPENING_BALANCE_ALREADY_RECORDED` | Opening balance dicatat ulang untuk item/outlet sama |
| `INVENTORY_IDEMPOTENCY_KEY_REQUIRED` | Mutation tanpa idempotency key |
| `INVENTORY_IDEMPOTENCY_CONFLICT` | Idempotency key dipakai ulang dengan payload berbeda |
| `INVENTORY_APPROVAL_REQUIRED` | Mutation membutuhkan approval |
| `INVENTORY_TRANSFER_INVALID_STATE` | Transfer state transition tidak valid |

## Testing Baseline

Setiap work package implementasi wajib minimal menjalankan:

- test feature spesifik Inventory untuk work package tersebut;
- regression Sales/Catalog critical path bila menyentuh shared auth/tenancy/error handling;
- `composer quality`.

Jalankan `npm run build` bila ada perubahan frontend.
