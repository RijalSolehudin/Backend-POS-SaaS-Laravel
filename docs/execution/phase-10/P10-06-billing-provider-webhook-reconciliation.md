# [rencana] P10-06: Billing Provider Webhook and Reconciliation

Status: **Planned**
Layer: **Backend**

## Outcome

Billing payment provider dapat mengirim webhook yang diverifikasi, idempotent, dan dapat direkonsiliasi.

## Scope

- Billing provider contract.
- Webhook signature verification.
- Provider event inbox.
- Idempotent event processing.
- Payment status update.
- Reconciliation command/report minimum.

## Acceptance Criteria

- Provider event id dipakai untuk replay safety.
- Invalid signature ditolak dan diaudit.
- Webhook paid mengubah invoice/subscription sesuai transition policy.
- Reconciliation tidak membuat mutation ganda.

## Out of Scope

- Menyimpan card data.
- Multi-provider settlement reconciliation.
- Chargeback/dispute complex workflow.
