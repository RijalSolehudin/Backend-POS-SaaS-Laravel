# P09-04 — Offline Order Queue

Status: **Implemented — Pending MariaDB Verification**

## Outcome

Offline draft/order completion request dari device dapat disinkronkan secara idempotent.

## Scope

- Buat offline order draft/event tables.
- Accept queued local order changes.
- Convert accepted offline completion into Sales order/payment.

## Delivered

- Tabel `offline_order_drafts` dan `offline_order_events` tersedia.
- Mutasi draft/add/update/remove dicatat idempotent sebagai event offline order.
- Completion `offline_order.complete_cash` dan `offline_order.complete_manual` replay ke action Sales untuk order, payment, dan receipt final.
- Offline gateway payment, refund, void, approval, inventory, procurement, device, dan catalog mutation ditolak oleh sync allowlist.

## Implementation Contract

- Ikuti [Phase 09 Implementation Contract](implementation-contract.md).
- Server tetap membuat final order number dan receipt.
- Gateway payment offline ditolak.

## Verification

- Offline order replay tests.
- Duplicate prevention tests.
- Sales integration tests.
- `composer quality`.
