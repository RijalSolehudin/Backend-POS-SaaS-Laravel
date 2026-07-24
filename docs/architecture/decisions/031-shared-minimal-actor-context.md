# ADR-031: Shared Minimal Actor Context

- Status: Accepted
- Date: 2026-07-24

## Context

Actor context digunakan oleh Platform Identity, tenant Identity, Tenancy, Catalog, device management, dan modul bisnis lain. Menempatkannya pada salah satu identity module akan membuat module tersebut menjadi dependency universal atau memerlukan context berbeda pada setiap application action.

## Decision

Actor context menjadi shared application concept yang minimal dan ditempatkan pada:

```text
app/Shared/Application/Context/ActorContext.php
```

Actor context minimum membawa:

- actor type;
- actor identifier;
- correlation/request identifier yang relevan.

Aturan:

- Actor context immutable.
- Actor context tidak bergantung pada HTTP request, session, controller, authenticated model, atau Laravel presentation state.
- Actor identity dibentuk oleh trusted presentation/composition boundary dan tidak diterima dari client sebagai source of truth.
- Tenant ID, outlet ID, dan target resource tetap menjadi explicit use-case input.
- Actor context tidak membawa tenant/outlet model, permission list, role snapshot, credential, token, atau arbitrary metadata.
- Authorization membaca state aktual melalui owning module/policy; tidak mempercayai permission snapshot dalam context.
- Penambahan field baru harus mempunyai kebutuhan lintas-modul yang nyata dan melalui architecture review.

## Consequences

- Modul bisnis tidak bergantung langsung pada tenant Identity atau Platform Identity hanya untuk mengenali actor.
- Web, API, CLI, queue consumer, audit, dan test dapat memakai bentuk actor yang konsisten.
- Penggunaan `Shared` mempunyai scope yang jelas dan kecil.
- Discipline diperlukan agar actor context tidak berkembang menjadi generic request/context bag.

## Alternatives Considered

### Separate context per identity module

Menjaga ownership identity sangat tegas, tetapi memaksa modul bisnis menerima union/interface atau menduplikasi action boundary.

### Actor context owned by tenant Identity

Sederhana secara lokasi, tetapi membuat Platform Identity dan seluruh modul bisnis bergantung pada tenant Identity serta melemahkan identity isolation.

