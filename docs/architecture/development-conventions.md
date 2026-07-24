# Development Conventions

Status: **Accepted**

Dokumen ini menerjemahkan ADR foundation menjadi convention yang harus diikuti selama implementasi. Jika kebutuhan baru bertentangan dengan convention ini, hentikan pekerjaan dan lakukan architecture review sebelum mengubahnya.

## Module Bootstrap

Module berada di `app/Modules/<Module>` dan hanya membuat folder yang benar-benar dibutuhkan.

Module dengan bootstrap responsibility mempunyai provider:

```text
app/Modules/<Module>/Infrastructure/Providers/<Module>ServiceProvider.php
```

Provider didaftarkan eksplisit pada `bootstrap/providers.php`. `register()` hanya untuk container binding; route, view, translation, migration, command, dan event bootstrapping dilakukan pada `boot()`.

Resource milik domain ditempatkan di dalam module:

```text
<Module>/
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

Shared layout dan design primitive yang tidak dimiliki satu domain tetap berada pada `resources/`.

## Application Actions

- Satu action mewakili satu intent bisnis.
- Action `final` secara default.
- Satu public workflow entry point bernama `handle()`.
- Dependency diberikan melalui constructor injection.
- Input kompleks menggunakan explicit data object.
- Action tidak membaca `auth()`, `request()`, atau `session()` untuk actor/target context.
- Target tenant, outlet, dan resource menjadi explicit input.
- Action yang membutuhkan actor menerima `ActorContext`.
- Mutation action menetapkan transaction boundary pada application layer.
- Success menggunakan typed return; expected business failure menggunakan typed `BusinessException`.

Contoh bentuk:

```php
final class CreateOutlet
{
    public function handle(
        CreateOutletData $data,
        ActorContext $actor,
    ): Outlet {
        // Application orchestration.
    }
}
```

## Shared Code

`app/Shared` hanya digunakan jika sebuah konsep benar-benar lintas-domain dan tidak memiliki owning module yang lebih tepat.

`ActorContext` sengaja berada pada Shared Application. Ia hanya membawa actor type, actor ID, dan correlation ID. Tenant/outlet, permission, role, session, credential, dan HTTP request tidak boleh ditambahkan.

## Dependency Rules

Deptrac menegakkan aturan dasar:

```text
Presentation -> Application -> Domain
Infrastructure -> Application / Domain
Domain -> Shared Domain
Application -> Shared Application / Shared Domain
```

Module tidak memperoleh akses ke module lain secara default. Published action, contract, atau event lintas-module hanya ditambahkan setelah ownership dan dependency disetujui.

Legacy Laravel skeleton pada `app/Http`, `app/Models`, dan `app/Providers` belum menjadi contoh struktur module dan akan dipindahkan secara proporsional oleh owning work package.

## Quality Commands

Static quality gate:

```shell
composer quality:static
```

Full quality gate dengan MariaDB test container yang sudah berjalan:

```shell
composer quality
```

Feature test foundation menolak database selain MariaDB dan memverifikasi strict SQL mode. Jangan mengganti test database ke SQLite untuk meloloskan quality gate.

Individual commands:

```shell
composer format
composer format:check
composer analyse
composer architecture
composer test:unit
composer test:feature
npm run build
```

Tidak membuat permanent PHPStan/Deptrac baseline. Ignore hanya untuk false positive terverifikasi dan harus sempit serta mempunyai alasan.

## Local MariaDB Test

Prasyarat: Docker-compatible container runtime.

Start database:

```shell
docker compose up -d mariadb-testing
```

Jalankan quality gate:

```shell
composer quality
```

Stop database:

```shell
docker compose down
```

Default local test port adalah `33067` agar tidak bertabrakan dengan MariaDB development. Override melalui `POS_TEST_DB_PORT` bila diperlukan.
