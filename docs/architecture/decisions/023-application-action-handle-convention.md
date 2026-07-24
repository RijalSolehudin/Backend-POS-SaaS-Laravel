# ADR-023: Application Action `handle()` Convention

- Status: Accepted
- Date: 2026-07-24

## Context

Application use case membutuhkan bentuk pemanggilan yang konsisten untuk Web, API, CLI, test, dan presentation adapter lain. Alternatif yang dipertimbangkan adalah action dengan method bernama, invokable action, atau command/handler melalui command bus.

## Decision

Satu application action mewakili satu intent bisnis dan mempunyai satu public entry point bernama `handle()`.

Contoh bentuk:

```php
final class CreateOutlet
{
    public function handle(CreateOutletData $data): Outlet
    {
        // Application orchestration.
    }
}
```

Aturan:

- Nama class menggunakan intent bisnis, misalnya `CreateOutlet`, bukan nama teknis generik.
- Action dibuat `final` secara default.
- Dependency action diterima melalui constructor injection.
- DTO/input data object digunakan ketika input cukup kompleks atau melewati presentation boundary; tidak diwajibkan untuk parameter sederhana tanpa manfaat nyata.
- Action tidak menggunakan command bus hanya untuk meneruskan pemanggilan synchronous.
- Controller, command, dan presentation adapter memanggil `handle()`; mereka tidak menduplikasi workflow bisnis.
- Public method tambahan hanya diperbolehkan jika bukan entry point workflow dan memiliki alasan yang jelas; helper internal dibuat private.

## Consequences

- Bentuk pemanggilan use case konsisten dan mudah dicari.
- Unit test dapat memanggil action secara langsung.
- Stack trace menampilkan nama method yang eksplisit.
- Sistem tidak memperoleh middleware command bus secara otomatis; cross-cutting behavior diterapkan pada boundary yang tepat dan hanya jika diperlukan.

## Alternatives Considered

### Invokable action

Lebih ringkas sebagai callable, tetapi semua entry point menggunakan nama `__invoke` dan intent method kurang eksplisit saat pencarian atau debugging.

### Command, handler, and command bus

Menyediakan message dispatch abstraction dan middleware bus, tetapi menambah class dan indirection yang belum dibutuhkan oleh synchronous modular monolith.

