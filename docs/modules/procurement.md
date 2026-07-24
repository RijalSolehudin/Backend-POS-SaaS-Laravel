# Procurement Module

## Owns

- Supplier dan supplier item mapping.
- Purchase order dan approval.
- Goods receipt dan hubungan ke inventory movement.

## Planned Use Cases

- Create/approve/cancel purchase order.
- Receive goods partially atau fully.
- Record supplier price dan purchasing summary.

## Invariants

- Goods receipt yang diposting menghasilkan inventory movement satu kali.
- Quantity received dapat direkonsiliasi terhadap purchase order.
- Approval dan perubahan status dapat diaudit.

## Open Decisions

- Partial receipt dan over-receipt policy.
- Purchase return.
- Tax dan landed cost.
- Supplier ownership tenant versus outlet.

