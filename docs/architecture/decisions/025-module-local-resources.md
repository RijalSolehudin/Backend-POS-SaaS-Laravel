# ADR-025: Module-Local Routes, Migrations, Views, and Translations

- Status: Accepted
- Date: 2026-07-24

## Context

Modular monolith membutuhkan ownership yang dapat dikenali bukan hanya untuk class PHP, tetapi juga untuk route, migration, view, dan translation. Alternatifnya adalah menempatkan resource domain di dalam modul, menggunakan struktur hybrid, atau mempertahankan seluruh resource pada direktori global Laravel.

## Decision

Route, migration, view, dan translation yang dimiliki suatu domain ditempatkan bersama modul pemiliknya dan dimuat secara eksplisit oleh service provider modul.

Struktur proporsional:

```text
app/Modules/<Module>/
├── Infrastructure/
│   └── Persistence/
│       └── Migrations/
└── Presentation/
    ├── Http/
    │   └── Routes/
    │       ├── web.php
    │       └── api.php
    └── Resources/
        ├── views/
        └── lang/
```

Aturan:

- Hanya folder yang dibutuhkan yang dibuat.
- Service provider modul memuat resource module-local secara eksplisit.
- Route file dipisahkan berdasarkan presentation boundary dan tidak memuat workflow bisnis.
- View modul menggunakan namespace yang jelas untuk mencegah collision.
- Migration tetap mempunyai timestamp/nama unik dan mengikuti dependency schema yang eksplisit.
- Shared Web Admin layout, design primitives, frontend entry point, dan resource yang benar-benar lintas-domain tetap berada pada direktori global `resources/`.
- Resource tidak dipindahkan ke `Shared` hanya karena dipakai lebih dari satu layar; ownership ditentukan berdasarkan capability.

## Consequences

- Ownership capability, schema, route, dan UI domain dapat ditelusuri dari satu modul.
- Modul bertambah tanpa membuat direktori global utama bercampur antar-domain.
- Service provider memperoleh tanggung jawab tambahan untuk resource loading.
- Generator dan command Laravel mungkin membutuhkan path eksplisit.
- Shared presentation resource harus dibedakan secara disiplin dari resource domain.

## Alternatives Considered

### Hybrid structure

Route/view/translation berada di modul sementara seluruh migration tetap global. Lebih dekat dengan default migration workflow, tetapi ownership persistence tidak terlihat dari struktur modul.

### Standard Laravel global directories

Paling dekat dengan skeleton dan generator Laravel, tetapi route, schema, dan presentation resource lintas-domain cepat bercampur saat capability bertambah.

