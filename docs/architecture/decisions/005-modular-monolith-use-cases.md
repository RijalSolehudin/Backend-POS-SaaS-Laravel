# ADR-005: Modular Monolith with Application Use Cases

- Status: Accepted
- Date: 2026-07-21

## Decision

Aplikasi menggunakan Laravel modular monolith. Workflow bisnis diekspresikan sebagai application use cases/actions. Eloquent dipakai secara pragmatis dan repository dibuat hanya ketika memberikan abstraction nyata.

## Consequences

- Modul memiliki ownership dan public application boundary yang jelas.
- Controller hanya menangani transport concerns.
- Satu action mewakili satu intent bisnis dan mengelola transaction boundary bila diperlukan.
- Tidak ada kewajiban memisahkan domain sepenuhnya dari Laravel.
- Struktur folder fisik mengikuti ADR-006.

