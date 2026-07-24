# ADR-010: Money Representation and Rounding

- Status: Accepted
- Date: 2026-07-23

## Context

Order, payment, receipt, shift, dan reporting membutuhkan nominal final yang dapat direkonsiliasi secara eksak. Inventory dan recipe juga membutuhkan quantity serta unit cost dengan pecahan yang lebih kecil dari satu rupiah. Satu tipe numerik tidak cocok untuk kedua kebutuhan tersebut.

## Decision

Sistem menggunakan representasi hybrid:

- Monetary amount final disimpan sebagai signed `BIGINT` minor units.
- Untuk IDR, satu minor unit sama dengan satu rupiah.
- Currency code disimpan eksplisit pada aggregate/transaksi terkait.
- Quantity, conversion factor, recipe cost, unit cost, dan intermediate calculation menggunakan fixed-point `DECIMAL`.
- Percentage rate menggunakan integer basis points ketika presisi `0,01%` mencukupi.
- Financial result dibulatkan half-up ke minor unit pada titik pembulatan yang ditetapkan domain.
- Snapshot hasil kalkulasi disimpan pada transaksi dan tidak dihitung ulang dari master data.
- `FLOAT` dan `DOUBLE` dilarang untuk business calculation.

## Naming and API Rules

- Kolom dan field monetary amount menggunakan suffix `_minor`, misalnya `subtotal_minor`, `tax_minor`, dan `paid_minor`.
- API mengirim monetary amount sebagai integer dan currency sebagai kode eksplisit pada konteks terkait.
- API mengirim quantity, cost, conversion, dan fixed-point value presisi sebagai decimal string.
- Flutter tidak mengubah decimal string menjadi `double` untuk business calculation.

## Rounding and Reconciliation

- Default rounding mode adalah half-up ke minor unit currency.
- Setiap komponen finansial yang disimpan memiliki hasil pembulatan eksplisit.
- Order total harus dapat direkonsiliasi dengan penjumlahan snapshot komponennya.
- Selisih pembagian discount/tax beberapa line dialokasikan secara deterministik; detail algoritma ditetapkan bersama calculation order.
- Receipt, payment, shift summary, dan reporting menggunakan snapshot nominal yang sama.

## Consequences

- Operasi penjumlahan dan rekonsiliasi nominal final tetap eksak.
- Signed `BIGINT` mendukung refund, adjustment, dan delta negatif.
- Recipe dan inventory tidak kehilangan fractional precision.
- Application layer memerlukan value object/utility numerik yang konsisten; desain implementasinya memerlukan persetujuan tersendiri jika berdampak arsitektural.
- Precision dan scale `DECIMAL` tidak dibuat universal; ditentukan per domain sebelum schema modul tersebut dibuat.

## Follow-Up Decisions

- Currency awal dan rencana multi-currency.
- Calculation order untuk discount, tax, dan service charge.
- Allocation algorithm untuk order-level adjustment.
- Precision/scale quantity, conversion, dan costing pada Inventory/Recipe.

