# Phase 07: Dining and Kitchen

Status: **Not Started**

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

- Table occupancy source of truth.
- Kitchen ticket lifecycle dan routing.
- Printer ownership/deployment model.
- Reverb, broadcast authorization, dan reconnection strategy.
- Failure behavior jika KDS/printer tidak tersedia.

## Acceptance Criteria

- Confirmed items dirutekan tepat satu kali ke station yang benar.
- Real-time channel tidak membocorkan data outlet lain.
- Kegagalan printer/KDS terlihat dan dapat dipulihkan tanpa menggandakan ticket.
- Table transfer/merge tidak merusak order/payment state.

