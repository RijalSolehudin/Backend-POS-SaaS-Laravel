# [rencana] Phase 10: SaaS Lifecycle, Onboarding, and Billing

Status: **Not Started**

## Outcome

Menambahkan capability SaaS platform agar tenant dapat melalui lifecycle komersial yang jelas: onboarding, subscription, billing, entitlement, suspension, dan reactivation. Phase ini berbeda dari payment gateway Phase 08 karena fokusnya adalah pembayaran tenant kepada platform, bukan pembayaran customer restoran terhadap sales order.

## Candidate Scope

- Public or assisted tenant signup.
- Tenant onboarding intake dan verification.
- Plan/package catalog.
- Subscription lifecycle.
- Billing invoice dan payment collection.
- Entitlement/feature gate.
- Trial, grace period, suspension, cancellation, dan reactivation.
- Platform Admin subscription operations.
- Audit, webhook, reconciliation, dan reporting minimum.

## Architecture Decisions Required

Phase ini belum boleh diimplementasikan sebelum keputusan berikut disetujui:

- Apakah onboarding bersifat public self-service, assisted sales, atau hybrid.
- Apakah tenant dibuat sebelum pembayaran, setelah pembayaran, atau setelah manual approval.
- Payment provider untuk SaaS billing.
- Trial policy, grace period, dan suspension policy.
- Plan hierarchy dan feature entitlement.
- Invoice numbering, tax/VAT handling, dan currency policy.
- Data retention setelah cancellation.
- Upgrade/downgrade proration policy.
- Batas hubungan dengan payment gateway Phase 08.

## Acceptance Criteria

Acceptance criteria final ditetapkan setelah decision gate selesai. Minimum acceptance criteria:

- Tenant lifecycle dapat dilacak dari intake sampai active subscription.
- Subscription status tidak bergantung pada tampilan frontend.
- Feature entitlement selalu dicek server-side.
- Billing payment dan webhook idempotent.
- Tenant suspension tidak menghapus data.
- Platform Admin dapat melihat status subscription dan melakukan controlled override dengan audit.
- Tidak ada data payment card sensitif disimpan di aplikasi.
- Manual recovery dan reconciliation flow terdokumentasi.

## Explicit Non-Goals

- Multi-currency billing di wave awal, kecuali disetujui.
- Marketplace billing.
- Accounting ledger penuh.
- Revenue recognition.
- Complex metered billing per transaksi.
- Multi-provider billing settlement reconciliation.
- Public customer CRM/loyalty.

## Dependency

- Phase 01-09 backend selesai.
- Platform Admin identity dan tenant provisioning sudah ada.
- Payment provider strategy untuk SaaS billing diputuskan.
- Web Admin planning memisahkan Landing/Pricing dari Tenant Admin.
