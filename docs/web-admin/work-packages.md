# Web Admin Work Packages

Status: **Accepted planning baseline**

Work package ini hanya boleh masuk implementasi setelah product owner menyetujui scope dan statusnya berubah menjadi `Ready`. Setiap paket diberi label layer agar jelas mana frontend, backend, product decision, atau docs/QA.

## Status Legend

- `Planned`: sudah direncanakan, belum siap implementasi.
- `Ready`: keputusan cukup dan boleh dikerjakan.
- `In Progress`: sedang dikerjakan.
- `In Review`: implementasi selesai dan sedang diverifikasi.
- `Done`: acceptance criteria, test, dan evidence selesai.
- `Blocked`: ada keputusan/dependency yang menghentikan.

## Package List

| ID | Work Package | Layer | Dependency | Status |
|---|---|---|---|---|
| WEB-00 | Web Admin Decision Gate | Product/ADR + Docs | Backend Phase 01-09 | Done |
| WEB-01 | Shared Admin Design System | Frontend | WEB-00 | Ready |
| WEB-02 | Admin Shell and Navigation | Frontend | WEB-01 | Ready |
| WEB-03 | Platform Admin UX Refresh | Frontend | WEB-02 | Planned |
| WEB-04 | Tenant Dashboard and Overview | Frontend + Backend | WEB-02 | Planned |
| WEB-05 | Master Data UX Hardening | Frontend | WEB-02 | Planned |
| WEB-06 | Operational Back-office Screens | Frontend + Backend | WEB-04 | Planned |
| WEB-07 | Growth and QR Admin Screens | Frontend + Backend | WEB-04 | Planned |
| WEB-08 | Public QR Customer Experience | Frontend + Backend | WEB-07 | Planned |
| WEB-09 | Reporting, Sync, and Readiness Consoles | Frontend + Backend | WEB-04 | Planned |
| WEB-10 | Manual QA, Accessibility, and Production UI Readiness | Docs/QA + Frontend | WEB-03 to WEB-09 | Planned |

## WEB-00 Web Admin Decision Gate

Layer: **Product/ADR + Docs**

Outcome:

- Product owner menyetujui design direction, navigation, dan feature exposure.
- Fitur backend gap ditandai tidak tampil aktif.
- Kebutuhan ADR baru dicatat sebelum implementation wave.

Acceptance criteria:

- Design system baseline disetujui.
- Platform Admin dan Tenant Admin IA disetujui.
- QR Customer diputuskan sebagai surface terpisah.
- Pricing/tier tidak menjadi gating aktif tanpa backend entitlement.

## WEB-01 Shared Admin Design System

Layer: **Frontend**

Outcome:

- Shared Blade primitives untuk admin siap dipakai lintas module.
- Token warna, spacing, typography, badge, button, table, form, modal, dan flash konsisten.

Acceptance criteria:

- Mengikuti Blade-first dan Tailwind 4.
- Tidak membuat card bertumpuk atau palette satu warna.
- Komponen cukup untuk CRUD/table/form admin.
- Tidak memindahkan domain logic ke frontend.

Detailed execution:

- [WEB-P01 Admin Foundation](execution/web-p01-admin-foundation.md)

## WEB-02 Admin Shell and Navigation

Layer: **Frontend**

Outcome:

- Platform Admin dan Tenant Admin memiliki shell konsisten.
- Sidebar, top bar, context tenant/outlet, dan responsive navigation tersedia.

Acceptance criteria:

- Platform dan Tenant auth boundary tetap terpisah.
- Tenant/outlet context terlihat jelas.
- Route aktif dan permission visibility tidak menggantikan authorization backend.
- Mobile/tablet tidak merusak layout.

Detailed execution:

- [WEB-P01 Admin Foundation](execution/web-p01-admin-foundation.md)

## WEB-03 Platform Admin UX Refresh

Layer: **Frontend**

Outcome:

- Login, MFA, recovery, security, tenant provisioning, tenant show, dan disable tenant dipoles.

Acceptance criteria:

- Sensitive action tetap memakai recent confirmation policy.
- MFA/recovery flow tidak menurunkan security posture.
- Tenant provisioning menampilkan success/failure dengan jelas.

## WEB-04 Tenant Dashboard and Overview

Layer: **Frontend + Backend**

Outcome:

- Tenant home menjadi dashboard operasional yang berguna.
- Metric awal meliputi outlet, device, sales daily, low stock, dan quick actions.

Backend needs:

- Read model agregasi tenant dashboard.
- Query harus tenant-scoped dan menghindari N+1.

Acceptance criteria:

- Dashboard tidak menampilkan fitur belum aktif.
- Data kosong tetap memberi next action yang valid.
- Query aman untuk tenant multi-outlet.

## WEB-05 Master Data UX Hardening

Layer: **Frontend**

Outcome:

- UX CRUD untuk outlet, users/roles, devices, catalog, inventory, recipe, procurement baseline, dan dining baseline dibuat konsisten.

Acceptance criteria:

- Table, filter, status badge, form, validation, dan destructive confirmation konsisten.
- Existing route/action digunakan.
- Role masih predefined; tidak ada custom role builder.

## WEB-06 Operational Back-office Screens

Layer: **Frontend + Backend**

Outcome:

- Web Admin mendapat screen untuk fitur operational yang backend action-nya sudah ada tetapi belum punya UI lengkap.

Candidate screens:

- Sales order detail/refund/shift review.
- Table session monitor.
- Kitchen station/routing/ticket/KDS/print job.
- Inventory transfer lifecycle.
- Purchase order, goods receipt, purchase return.

Acceptance criteria:

- Setiap screen memiliki owning module.
- Mutation sensitif memakai approval/idempotency sesuai backend.
- KDS realtime/polling diputuskan sebelum implementasi.

## WEB-07 Growth and QR Admin Screens

Layer: **Frontend + Backend**

Outcome:

- Tenant Admin dapat mengelola QR session, reservation baseline, promotion MVP, payment gateway review, dan staff confirmation.

Acceptance criteria:

- QR admin memakai OrderingChannel module yang sudah ada.
- Payment provider produksi tidak diklaim aktif sebelum provider ADR.
- Promotion/reservation UI hanya mengekspos capability yang tersedia.

## WEB-08 Public QR Customer Experience

Layer: **Frontend + Backend**

Outcome:

- Public QR ordering menjadi mobile-first experience terpisah dari Web Admin.

Acceptance criteria:

- Menggunakan existing QR token contract.
- Tidak membuat schema/API baru dari prototype tanpa ADR.
- Menu, item detail, cart, checkout, confirmation, dan tracking jelas.
- Public surface tidak membuka data tenant yang tidak perlu.

## WEB-09 Reporting, Sync, and Readiness Consoles

Layer: **Frontend + Backend**

Outcome:

- Admin memiliki visibility untuk laporan, export, offline sync, conflict, device trust, dan readiness.

Acceptance criteria:

- Report besar memakai async/export decision bila diperlukan.
- Conflict resolution UI menjaga audit dan tidak auto-repair sembarangan.
- Readiness console bersifat observability, bukan shortcut operasi berbahaya.

## WEB-10 Manual QA, Accessibility, and Production UI Readiness

Layer: **Docs/QA + Frontend**

Outcome:

- Manual test case Web Admin lengkap.
- Accessibility dan responsive smoke test terdokumentasi.
- Build frontend dan automated test lulus.

Acceptance criteria:

- Manual QA mencakup Platform Admin, Tenant Admin, QR Customer, landing/pricing bila masuk scope.
- `npm run build` lulus.
- `php artisan test` atau focused suites terkait lulus.
- Screens utama diverifikasi pada desktop dan mobile viewport.
