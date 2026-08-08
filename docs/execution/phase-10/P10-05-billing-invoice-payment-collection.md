# [rencana] P10-05: Billing Invoice and Payment Collection

Status: **Planned**
Layer: **Backend**

## Outcome

Sistem dapat menerbitkan invoice SaaS tenant dan membuat billing payment attempt.

## Scope

- Billing invoice model.
- Invoice numbering policy.
- Invoice line items.
- Payment attempt for invoice.
- Currency and tax/VAT behavior sesuai ADR.
- Manual paid/void controls untuk fallback bila disetujui.

## Acceptance Criteria

- Invoice issued tidak di-hard-delete.
- Retry payment attempt tidak menggandakan invoice/payment.
- Amount memakai minor units dan currency konsisten.
- Invoice hanya visible untuk platform dan tenant terkait.

## Out of Scope

- Accounting ledger.
- Revenue recognition.
- Multi-currency.
- Customer restaurant sales payment.
