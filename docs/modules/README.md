# Module Map

Dokumen modul mendefinisikan ownership dan capability. Nama class, tabel, endpoint, serta struktur fisik final bukan keputusan implementasi sampai disetujui.

| Modul | Status roadmap | Dokumen |
|---|---|---|
| Platform Identity | MVP | [Platform Identity](platform-identity.md) |
| Identity | MVP | [Identity](identity.md) |
| Tenancy | MVP | [Tenancy](tenancy.md) |
| Catalog | MVP dasar | [Catalog](catalog.md) |
| Sales | MVP | [Sales](sales.md) |
| Payments | MVP | [Payments](payments.md) |
| Inventory | Post-MVP | [Inventory](inventory.md) |
| Procurement | Post-MVP | [Procurement](procurement.md) |
| Dining | Post-MVP | [Dining](dining.md) |
| Kitchen | Post-MVP | [Kitchen](kitchen.md) |
| Reporting | MVP dasar | [Reporting](reporting.md) |
| [rencana] SaaS Lifecycle and Billing | Proposed | [[rencana] SaaS Lifecycle and Billing](saas-lifecycle-billing.md) |

## Boundary Rules

- Setiap capability memiliki satu owning module.
- Modul lain menggunakan published application use case, contract, atau event.
- Shared code hanya berisi konsep yang benar-benar lintas domain.
- Reporting boleh membaca data lintas modul, tetapi tidak mengubah write model modul sumber.
- Pemisahan menjadi service terpisah bukan bagian roadmap saat ini.

## Presentation Responsibilities

- Back-office module capabilities dipresentasikan melalui Web Admin Blade + Alpine.js.
- Operational POS capabilities dipresentasikan melalui REST API v1 untuk Flutter.
- Kedua adapter memanggil application actions yang sama dan tidak menduplikasi business logic.

## Boundaries Requiring Decisions

- Recipe dimiliki Catalog atau Inventory.
- Shift dimiliki Sales atau modul Operations terpisah.
- Customer/loyalty menjadi modul sendiri atau bagian Sales.
- Printer abstraction dimiliki Kitchen atau Shared Infrastructure.
- Discount/promotion dimiliki Catalog, Sales, atau modul Pricing.
