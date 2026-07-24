# ADR-029: GitHub Actions as the CI Platform

- Status: Accepted
- Date: 2026-07-24

## Context

Phase 01 membutuhkan automated quality gate untuk dependency boundary, test, MariaDB compatibility, dan frontend build. Source repository menggunakan GitHub sebagai remote, sehingga CI sebaiknya terintegrasi dengan pull request tanpa membuat command hanya dapat dijalankan pada satu provider.

## Decision

GitHub Actions digunakan sebagai CI platform resmi. Quality commands tetap dibungkus dalam Composer/npm scripts yang dapat dijalankan identik pada local development.

Pipeline minimum:

```text
dependency validation/install
  -> formatting check
  -> architecture/static analysis
  -> unit tests
  -> MariaDB 11.4 migration + integration/feature tests
  -> frontend build
```

Aturan:

- Workflow berjalan pada pull request dan branch utama.
- MariaDB test service mengikuti ADR-027.
- Deptrac menjadi required architecture quality gate sesuai ADR-026.
- Secret tidak ditulis ke repository, log, artifact, atau test fixture.
- Dependency cache boleh digunakan, tetapi tidak boleh mengubah correctness.
- Local quality command dan CI menggunakan entry point script yang sama sejauh praktis.
- CI failure tidak diabaikan melalui blanket skip atau permanent baseline.
- Deployment otomatis bukan bagian keputusan ini.

## Consequences

- Pull request memperoleh automated feedback dan status check dalam platform repository yang sama.
- Developer dapat mereproduksi sebagian besar failure melalui command lokal.
- Workflow YAML dan GitHub Actions permissions perlu dirawat.
- CI usage mengikuti availability dan quota GitHub.
- Perpindahan provider tetap lebih mudah karena substantive checks tidak hanya tertulis sebagai vendor-specific steps.

## Alternatives Considered

### Local scripts without a CI provider

Menghindari provider lock-in, tetapi tidak memberikan enforcement otomatis dan belum memenuhi CI gate Phase 01.

### External CI platform

Dapat dipilih bila deployment infrastructure membutuhkannya, tetapi saat ini menambah integration surface tanpa manfaat yang jelas.

