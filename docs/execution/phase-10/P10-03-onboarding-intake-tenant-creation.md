# [rencana] P10-03: Onboarding Intake and Tenant Creation Policy

Status: **Planned**
Layer: **Backend**

## Outcome

Onboarding calon tenant dapat dicatat dan diproses tanpa melanggar controlled provisioning invariants.

## Scope

- Onboarding intake record.
- Duplicate business/email handling policy.
- Email verification policy bila public signup dipilih.
- Approval/rejection lifecycle.
- Bridge menuju existing tenant provisioning action.
- Audit untuk setiap decision.

## Acceptance Criteria

- Public/assisted intake tidak langsung membuat tenant kecuali policy mengizinkan.
- Provisioning tetap atomik saat onboarding disetujui.
- Failure tidak membuat tenant/outlet/owner setengah jadi.
- Sensitive onboarding metadata tidak bocor ke tenant lain.

## Out of Scope

- Marketing landing page.
- Email campaign/CRM.
- Full KYC/business verification otomatis.
