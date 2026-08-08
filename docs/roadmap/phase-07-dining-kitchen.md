# Phase 07: Dining and Kitchen

Status: **Implemented — Pending MariaDB Verification**

## Outcome

Sistem mendukung dine-in table service dan alur order menuju kitchen/printer.

## Candidate Scope

- Floor dan table management.
- Table assignment, transfer, dan merge.
- Kitchen station routing.
- Kitchen ticket/KDS lifecycle.
- Real-time updates.
- Kitchen chit dan receipt printing adapters.

## Architecture Decisions Required

- Accepted decision: [ADR-042 Dining and Kitchen MVP Policy](../architecture/decisions/042-dining-kitchen-mvp-policy.md).
- Table occupancy source of truth adalah table session.
- Kitchen ticket lifecycle dan routing station ditentukan server-side.
- Real-time memakai broadcasting/Reverb bila deployment tersedia, dengan API snapshot fallback.
- Printer dispatch best-effort dan gagal print tidak membatalkan Sales order.

## Acceptance Criteria

- Confirmed items dirutekan tepat satu kali ke station yang benar.
- Real-time channel tidak membocorkan data outlet lain.
- Kegagalan printer/KDS terlihat dan dapat dipulihkan tanpa menggandakan ticket.
- Table transfer/merge tidak merusak order/payment state.
