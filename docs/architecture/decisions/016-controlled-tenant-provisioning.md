# ADR-016: Controlled Tenant Provisioning

- Status: Accepted
- Date: 2026-07-24

## Context

Public self-registration akan menambah tenant abuse/spam controls, verification, trial/subscription lifecycle, duplicate-business handling, dan onboarding UI yang tidak diperlukan untuk membuktikan MVP POS. Tenant dan initial owner tetap harus dapat dibuat secara konsisten melalui jalur berprivilege.

## Decision

- MVP tidak menyediakan public tenant registration.
- Tenant dibuat melalui privileged platform provisioning.
- Satu provisioning operation membuat tenant, initial outlet, initial owner, membership, dan role assignment minimum secara atomik.
- Actor tanpa platform authority tidak dapat memulai provisioning.
- Tenant owner kemudian mengelola user operasional dan outlet assignment melalui Web Admin sesuai permission.
- Email invitation lifecycle tidak menjadi syarat MVP dan dapat ditambahkan melalui ADR/roadmap lanjutan.

## Invariants

- Provisioning berhasil seluruhnya atau rollback seluruhnya.
- Identifier, ownership, role, dan membership awal konsisten dalam satu tenant.
- Initial owner aktif hanya setelah seluruh provisioning state valid.
- Provisioning attempt mencatat actor, waktu, outcome, dan failure correlation tanpa mencatat credential rahasia.
- Retry provisioning tidak membuat tenant atau owner duplikat; idempotency policy final ditentukan bersama delivery channel.

## Out of Scope

- Public SaaS signup.
- Trial/subscription/billing onboarding.
- Email invitation, resend, expiry, dan acceptance workflow.
- Automated business verification.

## Open Decisions

- Delivery channel telah diputuskan melalui ADR-017: Platform Admin Web utama dan CLI emergency.
- Platform identity menggunakan model/provider terpisah sesuai ADR-018.
- Credential delivery, duplicate detection, dan provisioning idempotency ditetapkan melalui ADR-034.

## Consequences

- Pilot/MVP onboarding bersifat manual dan terkontrol.
- Flutter POS tidak memiliki business registration flow.
- Web Admin tenant tidak mengekspos action create-tenant kepada tenant user.
- Public self-service onboarding di masa depan membutuhkan scope security dan lifecycle tersendiri.
- Kanal provisioning mengikuti ADR-017.
