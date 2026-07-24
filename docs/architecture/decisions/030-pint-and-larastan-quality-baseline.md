# ADR-030: Pint and Larastan Quality Baseline

- Status: Accepted
- Date: 2026-07-24

## Context

Phase 01 membutuhkan formatting dan code analysis yang konsisten. Pint telah tersedia untuk style, tetapi formatting tidak mendeteksi type mismatch, nullable access, undefined member, atau Laravel-specific type issue.

## Decision

Laravel Pint digunakan sebagai formatting gate. Larastan/PHPStan digunakan sebagai Laravel-aware code analysis gate dengan initial rule level 8.

Aturan:

- Pint check dan Larastan analysis dijalankan di local quality scripts serta GitHub Actions.
- Larastan menganalisis application code dan test/support code yang relevan, bukan vendor source.
- Initial PHPStan rule level adalah 8.
- Permanent/generated baseline tidak digunakan pada greenfield foundation.
- Ignore hanya diperbolehkan secara sempit untuk false positive yang terverifikasi, dengan identifier/pattern dan alasan yang jelas.
- Blanket ignore atau penurunan level untuk meloloskan CI tidak diperbolehkan.
- Rule level dapat dinaikkan setelah evaluasi terpisah; perubahan tersebut tidak dilakukan diam-diam.
- Deptrac tetap menangani dependency direction; Larastan menangani code/type correctness; PHPUnit menangani behavior.

## Consequences

- Formatting, architecture boundary, type correctness, dan behavior mempunyai quality gate yang berbeda dan jelas.
- Banyak defect dapat ditemukan sebelum runtime.
- Laravel magic tertentu mungkin membutuhkan type annotation atau narrow ignore.
- Analysis menambah waktu CI dan maintenance configuration.

## Alternatives Considered

### Pint without static analysis

Lebih sederhana, tetapi formatting tidak memeriksa type dan contract correctness.

### Maximum PHPStan level from the beginning

Memberi strictness tertinggi, tetapi dapat menambah annotation ceremony sebelum memberi nilai yang proporsional pada Laravel/Eloquent codebase.

## References

- [Larastan](https://github.com/larastan/larastan)
- [PHPStan documentation](https://phpstan.org/user-guide/getting-started)

