# P09-03 — Offline Catalog Snapshot

Status: **Implemented — Pending MariaDB Verification**

## Outcome

Device POS dapat mengambil catalog snapshot untuk offline read.

## Scope

- Buat catalog snapshot/version response.
- Tambahkan sync outbox event untuk catalog changes.
- Client receives cache retention policy.

## Delivered

- `GetOfflineCatalogSnapshot` menghasilkan version hash dari catalog outlet yang tersedia.
- Snapshot response membawa `retention_hours` untuk local cache policy.
- `catalog.snapshot.generated` dicatat ke `sync_outbox_records`.
- Offline catalog tetap read-only; mutation catalog tidak termasuk allowlist sync.

## Implementation Contract

- Ikuti [Phase 09 Implementation Contract](implementation-contract.md).
- Snapshot read-only; offline catalog mutation tidak diizinkan.

## Verification

- Catalog snapshot tests.
- Outbox event tests.
- Tenant/outlet isolation tests.
- `composer quality`.
