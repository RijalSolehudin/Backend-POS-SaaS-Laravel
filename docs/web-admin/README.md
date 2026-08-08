# Web Admin Planning

Status: **Accepted planning baseline**

Dokumen ini menjadi pintu masuk perencanaan pengembangan tampilan Web Admin setelah backend Phase 01 sampai Phase 09 selesai. Scope dokumen ini adalah perencanaan, bukan implementasi.

## Sumber Referensi

- Backend roadmap Phase 01-09 yang sudah berstatus Done.
- [Web Admin Conventions](../architecture/web-admin-conventions.md).
- [ADR-014 Web Admin and Flutter Presentation Responsibilities](../architecture/decisions/014-web-admin-and-flutter-presentations.md).
- [ADR-015 Blade-First, Vue by Exception](../architecture/decisions/015-blade-first-vue-by-exception.md).
- [ADR-025 Module-Local Routes, Migrations, Views, and Translations](../architecture/decisions/025-module-local-resources.md).
- Referensi UI dari `docs/usulan dari saya terkait UI/`.

Dokumen manual-development tetap diabaikan sebagai sumber scope karena dibuat hanya untuk cadangan kerja manual.

## Prinsip Perencanaan

- Web Admin tetap memakai Laravel Blade + Alpine.js sebagai default.
- Vue hanya dapat dipakai untuk isolated component yang kompleks sesuai ADR-015.
- Web controller memanggil application action, bukan REST API internal.
- UI domain mengikuti owning module. Shared component hanya untuk primitive lintas modul.
- Fitur yang belum didukung backend tidak ditampilkan sebagai fitur aktif.
- QR customer ordering dipisahkan dari Web Admin karena target pengguna, layout, dan risiko UX berbeda.
- Landing/pricing dipisahkan dari Web Admin karena bersifat marketing.

## Label Layer

| Label | Arti |
|---|---|
| `Frontend` | Perubahan Blade, Alpine, Tailwind, layout, view, component, atau UX flow tanpa perubahan business capability backend. |
| `Backend` | Perubahan application action, controller, route, query/read model, policy, migration, job, event, atau kontrak API. |
| `Product/ADR` | Membutuhkan keputusan product owner atau ADR sebelum implementasi. |
| `Docs/QA` | Dokumentasi, checklist, manual test case, atau verification evidence. |

## Dokumen

| Dokumen | Tujuan |
|---|---|
| [Frontend Decision Record](frontend-decision-record.md) | Keputusan desain dan arsitektur frontend yang sudah disetujui. |
| [Design System Plan](design-system-plan.md) | Keputusan visual, komponen, dan aturan UX Web Admin. |
| [Information Architecture](information-architecture.md) | Struktur menu Platform Admin, Tenant Admin, QR Customer, dan Landing/Pricing. |
| [Feature Readiness Matrix](feature-readiness-matrix.md) | Pemetaan fitur terhadap kondisi backend dan kebutuhan frontend/backend. |
| [Work Packages](work-packages.md) | Paket kerja Web Admin dengan label Frontend/Backend/Product/Docs. |
| [Execution Plans](execution/README.md) | Phase dan work package detail untuk implementasi frontend. |

## Keputusan Awal

| Area | Keputusan |
|---|---|
| Admin visual direction | Modern Professional sebagai baseline. |
| Palette | Biru/slate/emerald dengan aksen hangat terbatas untuk F&B. |
| Implementation stack | Blade-first, Alpine untuk interaksi ringan, Tailwind 4 melalui Vite. |
| Admin density | Dashboard operasional yang padat, mudah discan, dan tidak bergaya landing page. |
| QR customer UI | Mobile-first public experience terpisah dari Admin Web. |
| Pricing/tier UI | Tidak menjadi feature gating aktif sebelum backend entitlement disetujui. |

## Output Yang Diharapkan

Pengembangan Web Admin dianggap siap masuk implementasi bila:

- Design system sudah disepakati.
- Navigasi Platform Admin dan Tenant Admin sudah disepakati.
- Setiap fitur sudah ditandai sebagai `Ready for UI`, `Needs Thin Backend`, `Needs Backend`, `Product/ADR`, atau `Defer`.
- Work package pertama berstatus `Ready`.
- Acceptance criteria dan verification plan tersedia untuk setiap work package.
