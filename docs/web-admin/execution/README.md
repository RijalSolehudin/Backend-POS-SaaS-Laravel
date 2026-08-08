# Web Admin Execution Plans

Status: **Accepted planning baseline**

Dokumen pada area ini memecah Web Admin planning menjadi phase dan work package yang dapat dieksekusi. Scope ini tetap mengikuti [Frontend Decision Record](../frontend-decision-record.md), [Feature Readiness Matrix](../feature-readiness-matrix.md), dan ADR Web Admin yang sudah diterima.

## Rules

- Implementasi dimulai dari work package berstatus `Ready`.
- Work package frontend tidak boleh menampilkan fitur aktif yang belum siap backend-nya.
- Thin backend adapter boleh dibuat hanya bila capability domain sudah ada dan matrix menandai `Needs Thin Backend`.
- Jika sebuah layar membutuhkan keputusan arsitektur baru, pekerjaan berhenti dan decision record/ADR dibuat dulu.
- Status `Done` membutuhkan acceptance criteria, verification, dan evidence.

## Status Legend

- `Planned`: scope awal ditulis, belum siap implementasi.
- `Ready`: boleh dikerjakan.
- `In Progress`: sedang dikerjakan.
- `In Review`: implementasi selesai dan sedang diverifikasi.
- `Done`: acceptance criteria dan evidence selesai.
- `Blocked`: ada dependency/keputusan yang menghentikan.

## Phase List

| Phase | Outcome | Status |
|---|---|---|
| [WEB-P01](web-p01-admin-foundation.md) | Shared admin design system, primitives, and shell foundation | Ready |
| [WEB-P02](web-p02-tenant-admin-core.md) | Tenant Admin core ready screens and master data UX | Planned |
| [WEB-P03](web-p03-tenant-dashboard.md) | Tenant operational dashboard and read model | Planned |
| [WEB-P04](web-p04-platform-admin-polish.md) | Platform Admin polish and platform support screens | Planned |
| [WEB-P05](web-p05-operational-backoffice.md) | Operational back-office screens needing thin backend adapters | Planned |
| [WEB-P06](web-p06-growth-qr-admin.md) | Growth, QR, reservation, promotion, and payment review screens | Planned |
| [WEB-P07](web-p07-public-surfaces.md) | QR Customer and Landing/Pricing public surfaces | Planned |
| [WEB-P08](web-p08-production-readiness.md) | Manual QA, accessibility, responsive, and production readiness | Planned |

## Recommended Order

```text
WEB-P01 -> WEB-P02 -> WEB-P03 -> WEB-P04 -> WEB-P05 -> WEB-P06 -> WEB-P07 -> WEB-P08
```

WEB-P07 dapat dipercepat bila kebutuhan demo QR Customer atau Landing/Pricing menjadi prioritas product.
