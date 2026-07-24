# ADR-020: Single-Tenant User Membership for MVP

- Status: Accepted
- Date: 2026-07-24

## Context

Mengizinkan satu tenant user menjadi anggota beberapa tenant menambah kompleksitas login identity resolution, membership switching, authorization, uniqueness, audit context, dan risiko cross-tenant access. Kebutuhan utama POS dapat dipenuhi melalui satu tenant yang mempunyai banyak outlet.

## Decision

- Satu tenant `User` hanya menjadi anggota satu tenant pada MVP.
- Tenant user dapat ditugaskan ke satu atau beberapa outlet dalam tenant yang sama.
- Platform Administrator tetap menggunakan identity terpisah sesuai ADR-018.
- Tidak ada cross-tenant membership pivot pada MVP.
- Jika orang yang sama perlu mengakses tenant lain, proses dan identity policy baru memerlukan ADR.
- Login tenant user tetap tidak meminta tenant context; login identifier harus dapat diselesaikan secara tidak ambigu.

## Invariants

- Tenant user mempunyai tepat satu owning tenant setelah provisioning selesai.
- Outlet assignment hanya dapat menunjuk outlet dari owning tenant user.
- User tidak dapat dipindahkan ke tenant lain melalui update biasa.
- Cross-tenant move memerlukan deactivate/re-provision flow atau keputusan arsitektur baru.
- Tenant scope tidak boleh berasal dari input client semata.

## Consequences

- User/tenant ownership dan authorization lebih sederhana.
- Satu bisnis dengan banyak cabang tetap didukung melalui multi-outlet assignment.
- Konsultan/operator yang perlu mengakses beberapa tenant membutuhkan akun terpisah pada MVP.
- Dukungan multi-tenant membership di masa depan memerlukan migration dan review login/audit context.

## Follow-Up

- Normalized login identifier uniqueness dan account recovery detail harus tetap konsisten dengan login tanpa tenant context.

