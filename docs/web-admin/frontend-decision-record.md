# Frontend Decision Record

Status: **Accepted**

Dokumen ini mengunci keputusan desain dan arsitektur frontend Web Admin sebelum implementasi. Keputusan ini berlaku untuk tahap awal Web Admin dan dapat direvisi melalui decision record/ADR baru bila scope berubah besar.

## Decision Summary

| ID | Decision | Status |
|---|---|---|
| D-FE-000 | Frontend framework/stack | Accepted |
| D-FE-001 | Target utama Web Admin | Accepted |
| D-FE-002 | Product surface dan batas aplikasi | Accepted |
| D-FE-003 | Prioritas surface implementasi awal | Accepted |
| D-FE-004 | Tenant Admin navigation model | Accepted |
| D-FE-005 | Feature exposure policy | Accepted |
| D-FE-006 | Tenant Admin dashboard scope | Accepted |
| D-FE-007 | Tenant Admin context model | Accepted |
| D-FE-008 | Admin visual direction | Accepted |
| D-FE-009 | Admin layout pattern | Accepted |
| D-FE-010 | Page composition pattern | Accepted |
| D-FE-011 | Data table strategy | Accepted |
| D-FE-012 | Form and mutation UX | Accepted |
| D-FE-013 | Feedback, validation, and error UX | Accepted |
| D-FE-014 | Responsive stance | Accepted |
| D-FE-015 | Authorization and navigation visibility | Accepted |
| D-FE-016 | Backend adapter policy | Accepted |
| D-FE-017 | Shared component ownership | Accepted |
| D-FE-018 | Icon and visual asset policy | Accepted |
| D-FE-019 | QR Customer frontend stance | Accepted |
| D-FE-020 | Landing/Pricing frontend stance | Accepted |
| D-FE-021 | Reporting/export UI policy | Accepted |
| D-FE-022 | Accessibility and QA gate | Accepted |
| D-FE-023 | Implementation wave order | Accepted |
| D-FE-024 | Icon library | Accepted |
| D-FE-025 | KDS realtime vs polling | Accepted |
| D-FE-026 | KDS display purpose | Accepted |
| D-FE-027 | Waiter workflow surface | Accepted |
| D-FE-028 | QR Customer priority | Accepted |
| D-FE-029 | Landing/Pricing CTA | Accepted |
| D-FE-030 | Payment gateway UI before real provider | Accepted |
| D-FE-031 | Subscription/Billing visibility | Accepted |
| D-FE-032 | Dashboard metric scope | Accepted |
| D-FE-033 | Frontend QA approach | Accepted |

## D-FE-000: Frontend Framework/Stack

Decision:

- Web Admin memakai Laravel Blade + Alpine.js + Tailwind CSS + Vite.
- Vue hanya boleh digunakan sebagai isolated component untuk layar kompleks yang memenuhi exception criteria.

Tradeoff:

- Stack ini paling cepat dan sesuai ADR untuk CRUD, form, table, dan dashboard admin.
- SPA penuh ditunda agar backend tidak perlu menambah Admin API besar sebelum dibutuhkan.

Consequence:

- Tidak memakai React SPA, Inertia/Vue full app, Filament, atau Nova sebagai framework utama.
- Business logic tetap berada di backend/application action.

## D-FE-001: Target Utama Web Admin

Decision:

- Web Admin adalah Tenant Owner/Manager-first Back-office.
- Platform Admin adalah area pendukung.
- POS operasional tetap Flutter/API.

Tradeoff:

- Tenant Admin mendapat prioritas karena paling dekat dengan nilai produk F&B.
- Web Admin tidak dipaksa menjadi aplikasi kasir utama.

Consequence:

- Fokus layar awal adalah konfigurasi, master data, monitoring, reporting, dan back-office.

## D-FE-002: Product Surface dan Batas Aplikasi

Decision:

- Frontend dibagi menjadi empat product surface:
  - Platform Admin.
  - Tenant Admin.
  - QR Customer.
  - Landing/Pricing.
- Semua tetap berada dalam satu Laravel codebase/deployment untuk tahap awal.

Tradeoff:

- UX boundary jelas tanpa menambah kompleksitas deployment.
- Setiap surface dapat punya layout dan tone sendiri.

Consequence:

- QR Customer dan Landing/Pricing tidak memakai admin layout.
- Platform Admin dan Tenant Admin tidak dicampur secara mental atau visual.

## D-FE-003: Prioritas Surface Implementasi Awal

Decision:

- Implementasi awal memprioritaskan Tenant Admin.
- Dimulai dari shared admin design system dan admin shell.

Tradeoff:

- Tenant value muncul lebih cepat.
- Platform Admin polish, QR Customer, dan Landing/Pricing menyusul setelah fondasi admin stabil.

Consequence:

- Work awal mengarah ke WEB-01, WEB-02, lalu Tenant Admin dashboard/master data.

## D-FE-004: Tenant Admin Navigation Model

Decision:

- Tenant Admin memakai hybrid navigation.
- Menu utama mengikuti workflow bisnis; sub-menu tetap dekat dengan module backend.

Navigation baseline:

```text
Overview
Setup
Menu
Sales
Floor & Kitchen
Stock
Recipe & Procurement
Growth
Reports
Sync & Scale
Settings
```

Tradeoff:

- Lebih mudah dipahami user dibanding module-only navigation.
- Tetap menjaga kedekatan dengan ownership backend.

Consequence:

- Role/permission mengatur visibility dan akses, bukan membuat aplikasi berbeda per role.

## D-FE-005: Feature Exposure Policy

Decision:

- Campuran, dengan default fitur yang belum siap disembunyikan.

Rules:

- Ready backend + ready Web Admin: tampil aktif.
- Backend ada tetapi Web Admin belum lengkap: tampil hanya bila masuk work package aktif atau read-only/status terbatas.
- Belum ada backend atau butuh ADR: default disembunyikan.
- Fitur demo strategis boleh disabled/coming soon hanya dengan keputusan eksplisit.

Tradeoff:

- UI tidak menjanjikan fitur yang belum siap.
- Demo tetap bisa menampilkan arah produk bila benar-benar dibutuhkan.

Consequence:

- Subscription/billing, custom roles, split payment, loyalty, combo, manager PIN, receipt branding, provider real, dan scheduled export tidak tampil sebagai fitur aktif.

## D-FE-006: Tenant Admin Dashboard Scope

Decision:

- Dashboard awal adalah operational overview dashboard yang ringan dan jujur terhadap data backend.

Scope:

- Tenant/outlet context.
- Today sales summary.
- Operational status.
- Quick actions.
- Attention list.
- Recent activity ringan.

Tradeoff:

- Lebih berguna dari landing kosong.
- Tidak berubah menjadi analytics-heavy sebelum reporting read model matang.

Consequence:

- WEB-04 membutuhkan thin backend read model untuk agregasi tenant/outlet.

## D-FE-007: Tenant Admin Context Model

Decision:

- Tenant Admin memakai hybrid tenant-wide + outlet filter.

Tradeoff:

- Cocok untuk tenant satu outlet dan multi-outlet.
- Owner dapat melihat semua outlet; manager dapat fokus ke outlet yang diizinkan.

Consequence:

- Route utama tetap `/admin/tenants/{tenant}`.
- Dashboard/read model menerima `tenant_id` dan optional `outlet_id`.
- Outlet filter menampilkan hanya outlet yang boleh diakses user.

## D-FE-008: Admin Visual Direction

Decision:

- Admin visual direction adalah Hybrid Professional + F&B Accent.

Rules:

- Base netral terang/slate.
- Primary blue.
- Success emerald.
- Warning amber.
- Danger red.
- F&B accent orange/coral secara terbatas.

Tradeoff:

- Admin tetap profesional dan nyaman untuk kerja harian.
- Domain F&B tetap terasa tanpa membuat UI seperti landing page.

Consequence:

- Purple/amber tidak menjadi tema dominan.
- Tidak memakai gradient/orb/dekorasi berat pada admin shell.

## D-FE-009: Admin Layout Pattern

Decision:

- Tenant Admin memakai collapsible sidebar + topbar.

Tradeoff:

- Sidebar kuat untuk module banyak.
- Topbar fokus pada tenant/outlet context, user menu, dan action contextual.

Consequence:

- Desktop memakai sidebar kiri.
- Tablet dapat collapse.
- Mobile memakai drawer.

## D-FE-010: Page Composition Pattern

Decision:

- Default halaman memakai workspace layout.

Structure:

```text
Page Header
Optional Metric/Status Strip
Filter/Search Bar
Main Workspace
Secondary Interaction
```

Tradeoff:

- Cocok untuk CRUD, monitoring, dan back-office.
- Layar kompleks tetap boleh punya layout khusus.

Consequence:

- Table, filter, status, form, empty state, dan action pattern harus konsisten.

## D-FE-011: Data Table Strategy

Decision:

- Data table memakai hybrid server-side pagination + light client interactions.

Tradeoff:

- Data besar tetap aman dan efisien.
- Alpine cukup untuk bulk select, dropdown action, modal, row expansion, dan interaksi ringan.

Consequence:

- Filter/search/sort utama lewat request server dan query string.
- Tidak memuat seluruh dataset ke browser untuk filtering lokal.

## D-FE-012: Form and Mutation UX

Decision:

- Form memakai pola campuran berdasarkan kompleksitas.

Rules:

- Full page form untuk form besar, multi-section, atau mutation sensitif.
- Modal untuk form kecil dan action sederhana.
- Side panel/drawer untuk create/edit sedang yang butuh tetap melihat konteks list.
- Inline editing hanya untuk field kecil dan rendah risiko.

Tradeoff:

- User mendapat flow cepat untuk mutation kecil.
- Mutation kompleks tetap aman dan jelas.

Consequence:

- Destructive dan sensitive action selalu memakai confirmation.
- Backend validation tetap sumber kebenaran.

## D-FE-013: Feedback, Validation, and Error UX

Decision:

- Semua mutation memberi feedback eksplisit melalui flash, inline validation, dan error state yang konsisten.

Rules:

- Validation error tampil dekat field.
- Business failure menampilkan pesan operasional tanpa membocorkan detail internal.
- Success message singkat dan actionable.
- Long-running action menampilkan disabled/loading state.

Tradeoff:

- UI lebih terasa terpercaya.
- Membutuhkan shared error/flash component yang disiplin.

Consequence:

- Error code backend boleh diterjemahkan menjadi pesan UI, tetapi logic keputusan tetap backend.

## D-FE-014: Responsive Stance

Decision:

- Admin Web bersifat desktop-first responsive.
- Mobile didukung untuk monitoring/admin ringan, bukan sebagai POS cashier utama.

Tradeoff:

- Layout admin tetap optimal untuk kerja serius.
- Mobile tetap bisa membuka halaman penting tanpa rusak.

Consequence:

- Table boleh horizontal scroll pada mobile bila perlu.
- Action penting tetap reachable pada viewport kecil.

## D-FE-015: Authorization and Navigation Visibility

Decision:

- UI boleh menyembunyikan menu/action berdasarkan permission, tetapi authorization tetap wajib server-side.

Rules:

- Tenant/outlet context divalidasi backend.
- Role visibility tidak menggantikan policy/middleware/action guard.
- Fitur belum siap disembunyikan kecuali diputuskan sebagai preview.

Tradeoff:

- UX bersih tanpa menurunkan security.

Consequence:

- Setiap mutation tetap memanggil action yang mengecek permission dan context.

## D-FE-016: Backend Adapter Policy

Decision:

- Web controller tetap memanggil application action/read model, bukan REST API internal.
- Thin backend adapter boleh dibuat untuk UI yang capability domain-nya sudah ada.

Tradeoff:

- Blade Web Admin tetap sederhana dan selaras dengan modular monolith.
- Tidak perlu Admin API luas sebelum ada kebutuhan SPA.

Consequence:

- Dashboard, KDS monitor, QR admin, reporting, dan sync console boleh menambah web route/controller/ViewModel.

## D-FE-017: Shared Component Ownership

Decision:

- Shared layout, shell, dan primitives berada di `resources/`.
- Domain-specific views tetap module-local.

Rules:

- Shared component hanya untuk primitive lintas modul.
- Domain UI mengikuti owning module.

Tradeoff:

- Design system konsisten tanpa mengaburkan ownership capability.

Consequence:

- Catalog UI tetap di Catalog module, Inventory UI tetap di Inventory module, dan seterusnya.

## D-FE-018: Icon and Visual Asset Policy

Decision:

- Admin memakai icon untuk action dan navigasi yang umum.
- Bila icon library belum tersedia, dependency diputuskan saat WEB-01; kandidat utama adalah icon set ringan yang cocok dengan Blade/Vite.

Tradeoff:

- UI lebih mudah discan.
- Dependency baru tetap dikontrol.

Consequence:

- Jangan membuat tombol teks panjang untuk action yang punya simbol umum.
- Icon unfamiliar wajib punya label atau tooltip.

## D-FE-019: QR Customer Frontend Stance

Decision:

- QR Customer adalah public mobile-first surface terpisah dari Admin.
- Implementasi awal tetap boleh dalam Laravel codebase.

Tradeoff:

- QR bisa lebih visual dan customer-friendly.
- Tidak mengganggu Admin UI yang lebih utilitarian.

Consequence:

- QR menggunakan existing QR token contract.
- Tidak membuat schema/API baru dari prototype tanpa ADR.

## D-FE-020: Landing/Pricing Frontend Stance

Decision:

- Landing/Pricing adalah marketing surface, bukan bagian dari Admin.
- CTA default sebelum Phase 10 adalah Request Demo/Hubungi Sales/Daftar Pilot.

Tradeoff:

- Tidak menjanjikan public self-service signup yang belum ada.
- Tetap bisa menjual value produk.

Consequence:

- Pricing tiers boleh tampil sebagai marketing concept, tetapi tidak sebagai active feature gating sebelum backend Phase 10.

## D-FE-021: Reporting/Export UI Policy

Decision:

- Report ringan boleh tampil sebagai server-rendered table/summary.
- Export berat harus server-side dan dapat menjadi async bila data besar.

Tradeoff:

- Browser tidak dibebani dataset besar.
- Export tetap auditable dan aman tenant-scope.

Consequence:

- Export tidak dihasilkan dari data table browser.
- Report berat perlu backend read model/export action.

## D-FE-022: Accessibility and QA Gate

Decision:

- Setiap wave frontend harus melewati responsive, accessibility, dan manual QA smoke test.

Baseline:

- Accessible names untuk controls.
- Visible focus state.
- Error tidak mengandalkan warna saja.
- Contrast teks memadai.
- Desktop dan mobile viewport dicek.

Tradeoff:

- Delivery sedikit lebih disiplin.
- Mengurangi risiko UI tampak selesai tetapi sulit dipakai.

Consequence:

- WEB-10 menjadi gate sebelum UI dinyatakan production-ready.

## D-FE-023: Implementation Wave Order

Decision:

- Urutan implementasi mengikuti wave berikut:

```text
Wave 1: WEB-01 Shared Admin Design System
Wave 2: WEB-02 Tenant Admin Shell and Navigation
Wave 3: WEB-05 Master Data UX Hardening for ready screens
Wave 4: WEB-04 Tenant Dashboard and Overview read model
Wave 5: WEB-03 Platform Admin UX Refresh
Wave 6: WEB-06 Operational Back-office Screens
Wave 7: WEB-07 Growth and QR Admin Screens
Wave 8: WEB-08 Public QR Customer Experience
Wave 9: WEB-09 Reporting, Sync, and Readiness Consoles
Wave 10: WEB-10 Manual QA, Accessibility, and Production UI Readiness
```

Tradeoff:

- Wave awal memberi fondasi visual dan navigasi sebelum layar kompleks.
- Dashboard yang butuh read model dikerjakan setelah shell dan master data pattern jelas.

Consequence:

- Implementasi frontend berikutnya dapat dimulai dari WEB-01 tanpa keputusan tambahan.

## D-FE-024: Icon Library

Decision:

- Admin memakai icon library ringan bila belum ada icon library existing.
- Kandidat default adalah Lucide atau icon set ringan lain yang cocok dengan Blade/Vite.

Tradeoff:

- Menambah dependency kecil.
- UI lebih mudah discan dan action umum tidak perlu teks panjang.

Consequence:

- Icon dipakai untuk navigasi dan action umum.
- Icon yang tidak familiar wajib memiliki label, tooltip, atau accessible name.

## D-FE-025: KDS Realtime vs Polling

Decision:

- KDS memakai hybrid strategy: polling snapshot sebagai baseline, realtime sebagai enhancement bila channel siap.

Tradeoff:

- Polling memberi fallback yang stabil.
- Realtime tetap bisa ditambahkan tanpa redesign layar.

Consequence:

- KDS UI awal tidak bergantung penuh pada websocket.
- Jika realtime bermasalah, snapshot fallback tetap dapat digunakan.

## D-FE-026: KDS Display Purpose

Decision:

- KDS Web Admin dimulai sebagai admin monitor.
- Dedicated operational KDS display dapat dibuat kemudian sebagai work package terpisah.

Tradeoff:

- Scope awal lebih ringan dan cocok untuk Tenant Admin.
- Operasional dapur penuh tetap bisa dikembangkan dengan layout khusus.

Consequence:

- WEB-P05 membuat monitor/ticket status dulu.
- Full-screen kitchen display tidak diklaim sebagai target awal.

## D-FE-027: Waiter Workflow Surface

Decision:

- Waiter workflow web dibuat sebagai dedicated lightweight test surface bila diperlukan.
- Tujuannya hanya untuk menguji fitur backend/flow, bukan menjadi pusat operasional.
- Fokus operasional utama tetap Flutter/mobile.

Tradeoff:

- Web surface membantu QA/demo tanpa memaksa Tenant Admin menjadi waiter app.
- UI waiter production-grade tetap diarahkan ke mobile.

Consequence:

- Tenant Admin mengelola/monitor waiter workflow.
- Dedicated web waiter entry, bila dibuat, diberi label internal/test-oriented dalam scope planning.

## D-FE-028: QR Customer Priority

Decision:

- QR Customer dikerjakan setelah Tenant Admin core dan QR admin siap.

Tradeoff:

- Public QR tidak lebih dulu daripada setup/admin capability yang dibutuhkan.
- Demo public akan datang lebih lambat dibanding langsung membuat prototype customer.

Consequence:

- QR Customer masuk WEB-P07 secara normal.
- WEB-P07 boleh dipercepat hanya bila product priority berubah.

## D-FE-029: Landing/Pricing CTA

Decision:

- Landing/Pricing memakai CTA Request Demo, Hubungi Sales, atau Daftar Pilot sebelum Phase 10 tersedia.

Tradeoff:

- Tidak menjanjikan public self-service signup yang belum didukung backend.
- Marketing tetap dapat berjalan dengan flow assisted onboarding.

Consequence:

- Tidak ada tombol "Daftar dan langsung buat tenant" sampai onboarding/billing Phase 10 diimplementasikan.

## D-FE-030: Payment Gateway UI Before Real Provider

Decision:

- Payment gateway UI boleh menampilkan provider-agnostic review/status.
- Jika provider masih fake/local, UI harus menyatakan status tersebut dengan jelas.

Tradeoff:

- Admin/dev tetap bisa memonitor intent/webhook.
- UI tidak mengklaim Xendit/Midtrans atau provider produksi yang belum dipilih.

Consequence:

- Real provider settings tetap disembunyikan sampai provider decision/ADR tersedia.

## D-FE-031: Subscription/Billing Visibility

Decision:

- Subscription/Billing tidak tampil sebagai fitur aktif di Admin.
- Rencana subscription/billing tetap berada pada dokumen `[rencana]` Phase 10 backend.

Tradeoff:

- UI tenant lebih bersih dan jujur.
- Arah produk billing tetap terdokumentasi tanpa membingungkan tenant.

Consequence:

- Pricing tiers boleh tampil sebagai marketing concept.
- Feature gating tidak tampil aktif sebelum backend entitlement tersedia.

## D-FE-032: Dashboard Metric Scope

Decision:

- Dashboard awal memakai metric operasional minimum.

Scope:

- Outlet count.
- Device status.
- Sales hari ini.
- Low stock count.
- Quick actions.
- Attention list ringan.

Tradeoff:

- Dashboard langsung berguna tanpa menjadi analytics-heavy.
- Advanced reporting tetap ditunda sampai read model/reporting siap.

Consequence:

- WEB-P03 read model fokus pada agregasi ringan yang tenant/outlet scoped.

## D-FE-033: Frontend QA Approach

Decision:

- Frontend QA memakai build + focused feature tests + manual responsive/accessibility smoke.

Tradeoff:

- Lebih realistis untuk tahap awal dibanding full visual E2E automation.
- Tetap cukup kuat untuk mencegah regresi penting pada Blade/Web Admin.

Consequence:

- Setiap wave minimal menjalankan `npm run build`.
- Focused Laravel feature tests dijalankan untuk area yang terdampak.
- Manual desktop/mobile responsive check dan accessibility smoke dilakukan.
- Full E2E visual automation boleh ditambahkan nanti untuk layar berisiko tinggi, tetapi bukan gate awal.
