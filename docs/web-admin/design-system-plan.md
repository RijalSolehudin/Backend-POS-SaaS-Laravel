# Web Admin Design System Plan

Status: **Proposed**
Layer: **Frontend**

Dokumen ini menetapkan arah visual Web Admin. Detail token final dapat berubah saat implementasi, tetapi perubahan besar terhadap stack atau framework mengikuti ADR-015.

## Design Direction

Web Admin menggunakan arah **Modern Professional**:

- Dominan netral terang dan slate.
- Primary action biru profesional.
- Success/positive state emerald.
- Warning state amber.
- Danger state red.
- Aksen F&B hangat boleh dipakai terbatas untuk highlight, bukan warna dominan.

Referensi dari `design-system-options.html` yang paling cocok adalah Option B. Option C tidak dipilih sebagai baseline Admin karena purple/amber terlalu dominan untuk workflow operasional.

## Design Goals

- Cepat dibaca oleh owner, manager, dan admin outlet.
- Menampilkan data operasional tanpa terasa seperti landing page.
- Mendukung CRUD, table, form, filter, approval, dan status monitoring.
- Konsisten antara Platform Admin dan Tenant Admin.
- Responsive untuk laptop, tablet, dan mobile admin ringan.

## Visual Rules

| Area | Aturan |
|---|---|
| Layout | Sidebar desktop, top bar contextual, content width terkontrol. |
| Cards | Dipakai untuk item berulang, metric, modal, atau panel tools. Hindari card di dalam card. |
| Radius | Default kecil sampai sedang; hindari bentuk terlalu membulat pada admin dense UI. |
| Typography | Skala heading hemat. Hero-scale type hanya untuk landing, bukan admin. |
| Color | Jangan membuat UI satu warna saja. Purple tidak menjadi tema dominan. |
| Icon | Gunakan icon library yang tersedia saat implementasi bila project sudah memasangnya. Jika belum, tentukan dependensi secara eksplisit. |
| Table | Server-side pagination untuk data besar. Filter dan action row harus jelas. |
| Form | Label jelas, validation dekat field, destructive action memakai confirmation. |
| Empty state | Singkat, operasional, dan memberi next action yang valid. |
| Feedback | Flash message, inline validation, loading state untuk action penting. |

## Component Inventory

| Component | Layer | Status |
|---|---|---|
| Admin app shell | Frontend | Planned |
| Sidebar navigation | Frontend | Planned |
| Top bar and tenant/outlet switcher | Frontend | Planned |
| Page header with primary action | Frontend | Planned |
| Metric strip | Frontend | Planned |
| Data table | Frontend | Planned |
| Filter bar | Frontend | Planned |
| Status badge | Frontend | Planned |
| Form section | Frontend | Planned |
| Modal/confirmation | Frontend | Planned |
| Toast/flash | Frontend | Planned |
| Tabs | Frontend | Planned |
| Empty state | Frontend | Planned |
| Activity/audit list | Frontend + Backend | Needs read model review |
| Realtime status indicator | Frontend + Backend | Needs module integration review |

## Interaction Guidelines

- Alpine.js digunakan untuk dropdown, modal, tabs, dependent input, row repeater, dan confirmation.
- Long-running action menampilkan disabled state dan feedback setelah redirect.
- Destructive action selalu membutuhkan clear confirmation.
- Sensitive action mengikuti existing recent confirmation/approval flow bila tersedia.
- Search/filter besar tidak dilakukan seluruhnya di browser.

## Accessibility Baseline

- Semua control memiliki label atau accessible name.
- Fokus keyboard terlihat.
- Error form dapat dibaca tanpa mengandalkan warna saja.
- Contrast minimal sesuai WCAG AA untuk teks normal.
- Mobile layout tidak memotong teks tombol atau status.

## Out Of Scope Untuk Design System Admin

- Landing page hero.
- Pricing page marketing content.
- Public QR order visual polish.
- Flutter POS design system.
- Full SPA rewrite atau Inertia migration.
