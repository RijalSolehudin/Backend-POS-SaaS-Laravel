# ADR-024: Explicit Actor Context in Application Actions

- Status: Accepted
- Date: 2026-07-24

## Context

Application action dipakai oleh Web, API, CLI, queue consumer, dan automated test. Action sensitif harus mengetahui actor dan target context tanpa bergantung pada global HTTP request atau mengasumsikan bahwa presentation sudah melakukan seluruh authorization.

## Decision

Application action yang memerlukan actor menerima immutable actor context secara eksplisit pada method `handle()`.

Contoh bentuk:

```php
$action->handle(
    data: $data,
    actor: $actorContext,
);
```

Aturan:

- Actor context minimum mengidentifikasi actor type, actor ID, dan correlation/request ID yang relevan.
- Actor context dibuat oleh trusted presentation/composition boundary dari authenticated principal; client tidak boleh menentukan actor identity.
- Target bisnis seperti tenant ID, outlet ID, atau resource ID tetap menjadi input use case yang eksplisit.
- Target tenant/outlet tidak disimpulkan hanya dari actor karena Platform Administrator dapat bertindak terhadap tenant tanpa menjadi tenant member.
- Action mengerjakan atau mengorkestrasi authorization yang diperlukan untuk workflow.
- Presentation boleh melakukan early authorization untuk UX atau transport concern, tetapi bukan satu-satunya enforcement.
- Action tidak membaca `auth()`, `request()`, atau `session()` untuk memperoleh actor atau target context.
- Actor context bukan generic property bag dan tidak membawa object HTTP request.
- Action tanpa actor yang sah, seperti internal deterministic calculation, tidak dipaksa menerima actor context.

## Consequences

- Authorization dependency dan actor audit terlihat pada action boundary.
- Use case dapat dipakai dari Web, API, CLI, dan test tanpa HTTP global state.
- Entry point baru tidak otomatis melewati authorization.
- Presentation harus membentuk actor context secara konsisten.
- Signature action mempunyai parameter tambahan ketika actor memang diperlukan.

## Alternatives Considered

### Read Laravel authentication and request globals inside actions

Lebih singkat, tetapi mengikat application layer pada HTTP state, menyulitkan reuse, dan menyembunyikan tenant context.

### Authorize only in presentation

Menjaga action lebih kecil, tetapi menduplikasi policy antar-presentation dan memungkinkan entry point baru melewati enforcement.

