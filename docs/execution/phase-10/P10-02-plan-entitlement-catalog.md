# [rencana] P10-02: Plan and Entitlement Catalog

Status: **Planned**
Layer: **Backend**

## Outcome

Sistem memiliki catalog paket berlangganan dan entitlement yang dapat dipakai untuk membatasi fitur server-side.

## Scope

- Plan/package model.
- Feature entitlement model.
- Optional quota model.
- Effective date dan status active/disabled.
- Read service untuk mengecek entitlement tenant.

## Acceptance Criteria

- Plan dan entitlement dapat dibuat secara controlled seed/admin path.
- Entitlement check dapat dipanggil oleh application action atau policy.
- Feature key memakai nama stabil dan terdokumentasi.
- Default behavior untuk tenant tanpa subscription dikunci oleh ADR.

## Out of Scope

- Public pricing page.
- Billing invoice.
- Payment provider.
- Usage metering kompleks.
