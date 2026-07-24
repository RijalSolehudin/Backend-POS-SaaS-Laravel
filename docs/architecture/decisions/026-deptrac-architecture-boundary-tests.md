# ADR-026: Deptrac for Architecture Boundary Tests

- Status: Accepted
- Date: 2026-07-24

## Context

Modular monolith membutuhkan automated guardrail untuk mendeteksi dependency direction dan akses antar-modul yang melanggar ownership. Project menggunakan PHPUnit sebagai test framework dan tidak membutuhkan perubahan test stack hanya untuk architecture assertions.

## Decision

Deptrac digunakan sebagai analyzer utama untuk dependency direction dan boundary antar-modul. PHPUnit tetap menjadi test framework utama untuk unit, feature, integration, dan convention checks yang tidak tepat ditangani Deptrac.

Aturan awal Deptrac harus mencakup:

- Presentation boleh bergantung pada Application, tetapi Application dan Domain tidak boleh bergantung pada Presentation.
- Application boleh mengorkestrasi Domain dan contract yang diizinkan.
- Domain tidak bergantung pada Presentation atau detail Infrastructure.
- Satu modul tidak mengakses internal module lain di luar published action, contract, atau event yang disetujui.
- `Shared` tidak boleh menjadi jalur untuk menghindari module ownership.
- Pelanggaran baru menggagalkan CI.

Aturan:

- Konfigurasi disimpan dan ditinjau bersama source code.
- Exception/skip harus mempunyai alasan spesifik dan tidak digunakan sebagai baseline permanen.
- Dependency check dijalankan sebagai quality gate CI terpisah dari PHPUnit.
- PHPUnit dapat menangani convention seperti class/action shape bila lebih sederhana dan lebih tepat.
- Tidak membuat custom source-code dependency parser menggunakan pencarian string.

## Consequences

- Dependency rules menjadi executable dan dapat mencegah boundary erosion sejak awal.
- PHPUnit tidak perlu diganti atau dibungkus framework test lain.
- Development dependency dan CI command bertambah.
- Konfigurasi layer harus diperbarui secara disiplin ketika boundary yang disetujui berubah.

## Alternatives Considered

### Custom PHPUnit architecture tests

Tidak menambah dependency, tetapi reliable dependency analysis membutuhkan parser dan maintenance buatan sendiri.

### Pest architecture testing

Mempunyai DSL yang ringkas, tetapi memperkenalkan perubahan test stack sementara project telah menggunakan PHPUnit dan kebutuhan utama adalah dependency analysis antar-layer/modul.

## Reference

- [Deptrac documentation](https://deptrac.github.io/deptrac/)

