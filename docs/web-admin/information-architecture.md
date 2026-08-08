# Web Admin Information Architecture

Status: **Proposed**
Layer: **Frontend + Backend**

Dokumen ini memetakan struktur navigasi Web Admin berdasarkan capability backend yang sudah ada dan rencana UI berikutnya.

## Product Surfaces

| Surface | Pengguna | Tujuan | Layer Dominan |
|---|---|---|---|
| Platform Admin | Operator platform/SaaS owner | Tenant provisioning, platform security, system readiness. | Frontend + Backend |
| Tenant Admin | Owner/manager/admin outlet | Back-office tenant, master data, operasional, reporting. | Frontend |
| QR Customer | Customer scan QR | Browse menu, cart, submit request, track status. | Frontend + Backend |
| Landing/Pricing | Calon pembeli | Marketing, pricing, onboarding interest. | Frontend + Product |

## Platform Admin Navigation

| Menu | Capability | Backend Status | Frontend Status |
|---|---|---|---|
| Overview | Platform home, tenant summary, security reminders. | Partial | Needs dashboard read model review |
| Tenants | List, create, show, disable tenant. | Ready | Ready for UI polish |
| Security | Active sessions, MFA, recovery codes. | Ready | Ready for UI polish |
| Operations | Queue, scheduler, backup/readiness indicators. | Partial | Needs backend read model |
| API Docs | Link/view OpenAPI documentation. | Ready as docs | Needs frontend/docs viewer decision |

## Tenant Admin Navigation

| Menu | Capability | Backend Status | Frontend Status |
|---|---|---|---|
| Overview | Tenant dashboard, sales summary, low stock, quick actions. | Partial | Needs read model aggregation |
| Outlets | Outlet CRUD, disable, user assignment. | Ready | Ready for UI polish |
| Users & Roles | Assign/remove predefined roles. | Ready | Ready for UI polish |
| Devices | POS device registration, reassignment, revocation. | Ready | Ready for UI polish |
| Catalog | Categories, products, variants, availability, price override. | Ready | Ready for UI polish |
| Sales | Daily sales summary, void completed order. | Partial | Needs expanded screens |
| Dining | Floor/table admin. | Ready | Ready for UI polish |
| Kitchen | Stations, routing, tickets, KDS, print jobs. | Partial | Needs web routes/controllers for several screens |
| Inventory | Items, units, outlet settings, stock card, low stock, movements. | Ready | Ready for UI polish |
| Recipes | Recipe CRUD/status. | Ready | Ready for UI polish |
| Procurement | Supplier and supplier item baseline. | Partial | Needs expanded PO/receipt/return screens |
| Growth | QR sessions, order requests, waiter, gateway, reservation, promotion. | Partial | Needs web routes/controllers |
| Reports | Sales/export/reporting readiness. | Partial | Needs reporting UI and async/export decisions |
| Sync & Scale | Sync status, conflicts, device trust, performance baseline. | API/console ready | Needs web read model/controllers |
| Settings | Tenant profile, payment methods, tax/service/receipt branding. | Mixed | Several backend gaps |

## QR Customer Navigation

| Screen | Capability | Backend Alignment |
|---|---|---|
| QR entry | Resolve signed token and outlet/table context. | Use existing public QR token contract. |
| Menu | Browse active catalog snapshot. | Align with current public QR catalog endpoint. |
| Item detail | Variant/modifier selection. | Align with Catalog module constraints. |
| Cart | Customer cart/session state. | Use OrderingChannel module; review public route exposure. |
| Submit order | Customer order request pending staff confirmation. | Use existing staff confirmation model. |
| Tracking | Show request/order status. | Needs public read endpoint review. |

## Landing/Pricing Navigation

Landing and pricing are not part of Admin IA. They may reuse visual tokens but should have separate layout and copy strategy.

## Open IA Decisions

| Decision | Layer | Reason |
|---|---|---|
| Whether Tenant Admin overview is tenant-wide or outlet-first. | Product/ADR | Affects route context, dashboard queries, and default landing. |
| Whether Kitchen/KDS web screen is admin monitoring or operational display. | Product/ADR | Operational KDS may need realtime-heavy isolated component. |
| Whether QR Customer is served by Laravel Blade/PWA or separate frontend. | Product/ADR | Affects deployment, asset strategy, and public API contract. |
| Whether pricing tiers gate features inside Tenant Admin. | Product/ADR + Backend | Requires entitlement model before UI gating. |
