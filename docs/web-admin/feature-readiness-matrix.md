# Web Admin Feature Readiness Matrix

Status: **Proposed**

Dokumen ini membedakan fitur yang siap dibuat UI-nya dari fitur yang membutuhkan backend atau keputusan product/ADR. Matrix ini menjaga agar UI tidak menjanjikan capability yang belum tersedia.

## Legend

| Status | Arti |
|---|---|
| `Ready for UI` | Backend capability dan route/action utama sudah tersedia; fokus frontend polish. |
| `Needs Thin Backend` | Domain/action sudah ada, tetapi perlu web route, controller, ViewModel, atau query agregasi. |
| `Needs Backend` | Capability utama belum tersedia atau belum lengkap. |
| `Product/ADR` | Perlu keputusan scope/arsitektur sebelum implementasi. |
| `Defer` | Tidak masuk Web Admin wave awal. |

## Platform Admin

| Feature | Status | Layer | Catatan |
|---|---|---|---|
| Platform login/MFA/session replacement | Ready for UI | Frontend | UI bisa dipoles tanpa mengubah auth policy. |
| Platform security page | Ready for UI | Frontend | Existing session/recovery controls siap dipoles. |
| Tenant provisioning | Ready for UI | Frontend | Create/show/disable tenant sudah tersedia. |
| Platform overview metrics | Needs Thin Backend | Backend + Frontend | Perlu read model ringkas untuk tenant count, active tenant, recent provisioning, security warnings. |
| Operational readiness page | Needs Thin Backend | Backend + Frontend | Bisa membaca schedule/config/evidence tertentu, tetapi jangan menjalankan operasi destruktif dari UI. |
| API docs viewer | Ready for UI | Frontend + Docs | Bisa membuka/link OpenAPI YAML melalui viewer lokal/documentation page. |

## Tenant Administration

| Feature | Status | Layer | Catatan |
|---|---|---|---|
| Tenant home/dashboard shell | Needs Thin Backend | Backend + Frontend | Perlu agregasi sales, low stock, devices, recent actions. |
| Outlet management | Ready for UI | Frontend | Existing web routes tersedia. |
| User role assignment | Ready for UI | Frontend | Role masih predefined sesuai ADR-021. Jangan tampilkan custom role builder. |
| Device management | Ready for UI | Frontend | Register/reassign/revoke tersedia. |
| Catalog category/product | Ready for UI | Frontend | Existing web routes tersedia. |
| Variant and modifier administration | Needs Thin Backend | Frontend + Backend | Variant web routes tersedia; modifier capability perlu dicek route/view exposure saat implementasi. |
| Outlet availability/price override | Ready for UI | Frontend | Existing route tersedia. |
| Daily sales summary | Ready for UI | Frontend | Existing web page tersedia. |
| Void completed order | Ready for UI | Frontend | Existing web mutation tersedia; harus menjaga approval/idempotency behavior. |
| Refund management | Needs Thin Backend | Backend + Frontend | API/action ada, web screen perlu ditambahkan bila ingin admin refund dari web. |
| Cash movements/shift review | Needs Thin Backend | Backend + Frontend | API/action ada; web monitoring/detail belum lengkap. |
| Dining floors/tables | Ready for UI | Frontend | Existing web routes tersedia. |
| Table sessions | Needs Thin Backend | Backend + Frontend | Domain/action ada; admin monitor/control perlu route/view. |
| Kitchen stations/routing | Needs Thin Backend | Backend + Frontend | Action ada; web management perlu route/view. |
| KDS snapshot/monitor | Needs Thin Backend | Backend + Frontend | API snapshot ada; web KDS screen perlu keputusan realtime/polling. |
| Print job monitoring/reprint | Needs Thin Backend | Backend + Frontend | Action ada; web screen perlu route/view. |
| Inventory units/items/settings | Ready for UI | Frontend | Existing web routes tersedia. |
| Stock card and low stock | Ready for UI | Frontend | Existing web routes tersedia. |
| Opening balance/adjustment/waste | Ready for UI | Frontend | Existing web mutations tersedia; approval feedback harus jelas. |
| Inventory transfer lifecycle | Needs Thin Backend | Backend + Frontend | Actions ada; web route/view belum lengkap. |
| Recipe CRUD/status | Ready for UI | Frontend | Existing web routes tersedia. |
| Recipe version/costing detail | Needs Thin Backend | Backend + Frontend | Capability ada; perlu UI detail sesuai action yang tersedia. |
| Supplier and supplier items | Ready for UI | Frontend | Existing baseline route tersedia. |
| Purchase order lifecycle | Needs Thin Backend | Backend + Frontend | Actions ada; web screens perlu dibuat. |
| Goods receipt/purchase return | Needs Thin Backend | Backend + Frontend | Actions ada; web screens perlu dibuat. |

## Growth, QR, Payment, Reporting, Sync

| Feature | Status | Layer | Catatan |
|---|---|---|---|
| QR session management | Needs Thin Backend | Backend + Frontend | Backend module ada; web route/view untuk create/disable/print QR perlu dirancang. |
| Public QR menu | Ready for UI | Frontend | Gunakan kontrak QR token yang sudah ada, bukan schema/API baru dari referensi Claude. |
| Public QR cart/checkout | Needs Thin Backend | Backend + Frontend | Application layer ada; public route exposure perlu ditinjau. |
| Staff confirmation order request | Needs Thin Backend | Backend + Frontend | Backend capability ada; perlu web/admin or waiter UI. |
| Waiter workflow | Needs Thin Backend | Backend + Frontend | Backend capability ada; tentukan apakah masuk Tenant Admin atau dedicated waiter screen. |
| Payment gateway intent/webhook review | Needs Thin Backend | Backend + Frontend | Provider saat ini fake/local; produksi perlu provider decision. |
| Xendit/Midtrans/real provider | Product/ADR + Backend | Product/ADR | Belum boleh dianggap aktif. |
| Reservation minimum | Needs Thin Backend | Backend + Frontend | Module ada; web screens perlu dibuat. |
| Promotion MVP | Needs Thin Backend | Backend + Frontend | Module ada; UI apply/config perlu dirancang. |
| Analytics/export | Needs Thin Backend | Backend + Frontend | Reporting/export UI perlu query dan async policy review. |
| Offline sync dashboard | Needs Thin Backend | Backend + Frontend | API/console ada; web monitoring belum ada. |
| Conflict review | Needs Thin Backend | Backend + Frontend | Backend conflict model ada; admin resolution UI perlu hati-hati. |
| Device trust/security review | Needs Thin Backend | Backend + Frontend | API/device trust capability ada; web display perlu route/view. |

## Features Not Active Yet

| Feature | Status | Layer | Reason |
|---|---|---|---|
| Subscription tiers and feature gating | Product/ADR + Backend | Product/ADR | Pricing docs belum menjadi entitlement system. |
| Custom roles/permission builder | Product/ADR + Backend | Product/ADR | MVP memakai predefined roles. |
| Split/multi-payment | Needs Backend | Backend | Current payment completion masih terbatas. |
| Held order as explicit workflow | Needs Backend | Backend | Draft order ada, tetapi hold/reopen sebagai product feature belum dikunci. |
| Loyalty points/customer membership | Needs Backend | Backend | Belum ada module customer/loyalty final. |
| Product combos/bundles | Needs Backend | Backend | Belum menjadi catalog capability aktif. |
| Manager PIN authorization | Product/ADR + Backend | Product/ADR | Approval ada, tetapi PIN auth berbeda dari current identity policy. |
| Custom receipt branding | Needs Backend | Backend | Receipt snapshot ada; branding config belum dikunci. |
| Scheduled export | Product/ADR + Backend | Product/ADR | Membutuhkan schedule, storage, permission, dan retention decision. |
