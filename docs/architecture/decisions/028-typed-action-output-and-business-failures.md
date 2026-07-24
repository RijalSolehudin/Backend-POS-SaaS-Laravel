# ADR-028: Typed Action Output and Business Failures

- Status: Accepted
- Date: 2026-07-24

## Context

Application action dipanggil dari Web, API, CLI, dan test. Semua presentation membutuhkan cara konsisten untuk menerima hasil sukses dan memetakan expected business failure tanpa menggunakan return value ambigu atau wrapper universal yang menambah branching pada setiap call site.

## Decision

Application action mengembalikan success value dengan return type yang jelas. Expected business failure dinyatakan melalui typed domain/application exception yang memiliki stable application error code.

Aturan:

- Action mengembalikan domain model ketika model tersebut memang merupakan hasil use case dan aman pada application boundary.
- Explicit output DTO digunakan ketika hasil terdiri dari beberapa value, memerlukan snapshot, atau internal model tidak boleh menjadi kontrak pemanggil.
- `void` hanya digunakan ketika use case benar-benar tidak mempunyai hasil yang berguna bagi pemanggil.
- `bool`, `null`, atau associative array bebas tidak digunakan untuk menyatakan hasil/failure yang memiliki makna bisnis.
- Expected business exception mempunyai type dan stable code yang dapat dipetakan secara konsisten.
- Presentation menerjemahkan exception menjadi Web validation/domain feedback, CLI failure, atau RFC 9457 API response.
- Domain/application exception tidak membawa HTTP status, redirect, session, atau response object.
- Unexpected infrastructure/programming exception ditangani centralized exception boundary, dicatat dengan correlation ID, dan tidak membocorkan detail sensitif.
- Validasi transport tetap dilakukan di presentation; action/domain tetap menegakkan invariant dan authorization yang tidak boleh dipercaya dari presentation.

## Consequences

- Happy path action dan call site tetap ringkas.
- Failure mapping dapat digunakan bersama oleh Web, API, dan CLI tanpa mengikat domain ke HTTP.
- Static return type membantu review dan refactoring.
- Tim harus menjaga taxonomy exception dan stable code agar tidak menjadi kumpulan exception generik.
- Presentation membutuhkan centralized mapping untuk business failure yang relevan.

## Alternatives Considered

### Universal Result object

Membuat success/failure eksplisit pada return value, tetapi menambah wrapper dan branching pada setiap call site sementara PHP tidak memiliki algebraic result type native.

### Boolean, null, or unstructured array

Ringkas pada awalnya, tetapi makna outcome ambigu, sulit dianalisis, dan tidak cukup kuat untuk multiple presentation contracts.

