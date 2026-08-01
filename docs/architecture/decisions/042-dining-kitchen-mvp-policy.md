# ADR-042: Dining and Kitchen MVP Policy

- Status: Accepted
- Date: 2026-08-01

## Context

Phase 07 menambah dine-in table service, kitchen routing, KDS/ticket, dan printer adapter setelah POS, Catalog, Sales, Inventory, dan Recipe lebih stabil.

## Decision

### Module Boundary

- Module `Dining` memiliki floor, table, table session, seating, table transfer, dan merge.
- Module `Kitchen` memiliki station, routing rule, kitchen ticket, ticket item, KDS state, dan printer dispatch log.
- Sales tetap pemilik order/payment lifecycle.
- Kitchen membaca confirmed Sales order items melalui application boundary dan membuat kitchen ticket idempotent.

### Table Source Of Truth

- Table occupancy source of truth adalah `dining_table_sessions`.
- Table session status: `open`, `merged`, `transferred`, `closed`, `cancelled`.
- Satu table hanya boleh memiliki satu open session per outlet.
- Merge/transfer tidak mengubah order financial state.

### Kitchen Lifecycle

- Kitchen ticket status: `queued`, `preparing`, `ready`, `served`, `cancelled`.
- Kitchen ticket dibuat saat order item confirmed untuk kitchen routing.
- Ticket creation idempotent per order item and station.
- Cancel item/order menghasilkan kitchen cancellation ticket/event, bukan menghapus ticket lama.

### Routing

- Station tenant/outlet-scoped.
- Routing rule menghubungkan category/product/variant ke station.
- Jika tidak ada routing rule, item masuk fallback station outlet bila tersedia; jika tidak, item tetap orderable tapi muncul pada kitchen exception report.

### Real-Time

- MVP real-time memakai Laravel broadcasting/Reverb bila tersedia pada deployment.
- Semua channel tenant/outlet-scoped dan device/user authorized.
- Reconnection client wajib fetch latest state dari API snapshot, bukan mengandalkan event yang hilang.

### Printing

- Printer adapter adalah best-effort dispatch.
- Print job status: `queued`, `sent`, `failed`, `cancelled`.
- Kegagalan printer tidak membatalkan order/payment.
- Reprint membuat print job baru dengan reason dan actor.

## Deferred Scope

- Offline kitchen.
- Advanced course firing.
- Kitchen capacity planning.
- Hardware-specific guaranteed delivery.
- Split bill/table payment kompleks.

## Approval

Product owner menyetujui keputusan ini sebagai pre-accepted Phase 07 implementation policy pada 2026-08-01.
