# Inventory Module

## Owns

- Inventory item, unit, stock balance projection, batch, dan stock movement.
- Stock adjustment, waste, transfer, dan low-stock signal.
- Recipe bila keputusan ownership menetapkannya di sini.

## Planned Use Cases

- Receive stock movement dari sumber yang sah.
- Adjust dan transfer stock dengan approval/audit.
- Record waste.
- Deduct ingredients secara idempotent dari sales event.
- Query stock card dan valuation.

## Invariants

- Balance berubah melalui movement yang dapat ditelusuri.
- Unit conversion deterministik.
- Retry tidak menggandakan movement.
- Cross-outlet transfer mempunyai lifecycle kirim dan terima.

## Open Decisions

- Recipe ownership.
- Deduction timing.
- Negative stock policy.
- FIFO/weighted-average costing.
- Batch/expiry inclusion phase pertama inventory.
- Precision/scale quantity, conversion factor, dan unit cost.
