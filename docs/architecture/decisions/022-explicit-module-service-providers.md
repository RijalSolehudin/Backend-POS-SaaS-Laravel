# ADR-022: Explicit Module Service Providers

- Status: Accepted
- Date: 2026-07-24

## Context

Struktur modular membutuhkan mekanisme yang jelas untuk mendaftarkan binding, route, event listener, command, dan resource lain milik modul. Pilihannya adalah registrasi eksplisit, filesystem auto-discovery, atau package modular pihak ketiga.

## Decision

Setiap modul aktif menggunakan service provider modul yang didaftarkan secara eksplisit melalui `bootstrap/providers.php`.

Provider modul ditempatkan pada infrastructure modul:

```text
app/Modules/<Module>/Infrastructure/Providers/<Module>ServiceProvider.php
```

Aturan:

- Tidak menggunakan filesystem scanning untuk menemukan modul.
- Tidak menambahkan package modular hanya untuk registrasi atau discovery.
- Modul didaftarkan hanya ketika mempunyai bootstrap responsibility nyata.
- Daftar provider menjadi sumber eksplisit untuk mengetahui modul yang aktif dan urutan registrasinya.
- Dependency antar-provider harus diminimalkan dan tidak digunakan untuk menyembunyikan dependency domain.

## Consequences

- Modul aktif, boot order, dan registration entry point mudah ditelusuri.
- Static analysis, review, dan debugging tidak bergantung pada discovery convention tersembunyi.
- Penambahan atau penghapusan modul membutuhkan perubahan eksplisit pada `bootstrap/providers.php`.
- Setiap provider menambah sedikit boilerplate, tetapi tetap proporsional karena folder/provider kosong tidak perlu dibuat.

## Alternatives Considered

### Filesystem auto-discovery

Mengurangi registrasi manual, tetapi menambah scanning dan perilaku implisit serta membuat boot order lebih sulit ditelusuri.

### Third-party modular package

Menyediakan generator dan lifecycle modul, tetapi menambah dependency dan convention yang belum dibutuhkan oleh modular monolith ini.

