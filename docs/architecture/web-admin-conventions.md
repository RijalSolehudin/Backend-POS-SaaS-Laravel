# Web Admin Conventions

Status: **Accepted baseline**

## Purpose

Web Admin adalah presentation utama untuk konfigurasi dan back-office. Flutter tetap fokus pada operasi POS.

Platform Admin dan Tenant Admin adalah dua area dalam Web Admin Laravel yang sama. Keduanya bukan aplikasi, repository, atau deployment terpisah.

## Frontend Strategy

- Blade adalah page, layout, navigation, form, table, dan rendering default Web Admin.
- Alpine.js digunakan untuk interaksi lokal/ringan seperti modal, dropdown, tabs, row repeater, confirmation, dan dependent input.
- Vue tidak digunakan sebagai global Web Admin framework pada MVP.
- Vue dapat digunakan sebagai isolated component untuk layar dengan client-side state yang kompleks dan setelah kebutuhan tersebut direview.
- Contoh kandidat Vue: floor-plan editor, spreadsheet-like stock opname, visual designer, atau high-frequency realtime dashboard.
- Alpine dan Vue tidak boleh mengontrol DOM subtree yang sama.
- Penggunaan Vue tidak memindahkan business rules dari backend/application use case.
- Perubahan menjadi Inertia + Vue secara luas membutuhkan ADR baru.

## Authentication and Context

- Web menggunakan Laravel session authentication dan CSRF protection.
- Tenant Web Admin dan Platform Admin berada pada authentication/route area terpisah.
- Keduanya menggunakan database-backed session dan cookie terpisah.
- Tenant Admin session memiliki idle timeout 30 menit dan absolute timeout 8 jam.
- Platform Admin session memiliki idle timeout 15 menit dan absolute timeout 4 jam.
- Remember-me tidak digunakan pada MVP.
- Platform Admin wajib menggunakan TOTP MFA; Tenant Admin MFA optional pada MVP.
- Tenant/outlet context ditampilkan secara eksplisit pada route.
- Preferensi tenant/outlet terakhir dalam session hanya membantu navigasi dan bukan sumber authorization.
- Policy, membership, dan tenant/outlet scope tetap diperiksa server-side.

## Application Boundary

- Web controller menerima request, melakukan transport validation, memanggil application action, lalu menghasilkan redirect/view.
- Web controller tidak mengimplementasikan workflow bisnis.
- Web controller tidak memanggil REST API aplikasi yang sama melalui HTTP.
- ViewModel/presenter menyiapkan data display tanpa mengubah domain state.
- Alpine.js digunakan untuk interaksi ringan; business rule tetap diputuskan backend.

## Routes

```text
/platform/...
/admin/tenants/{tenant}/...
/admin/tenants/{tenant}/outlets/{outlet}/...
```

Route final ditentukan per module. Internal web route tidak menggunakan API version prefix.

## Platform Administration

- Platform Admin Web adalah kanal utama controlled tenant provisioning.
- Platform Admin dan Tenant Admin berbagi Laravel application, deployment, database connection, Blade/Alpine stack, Vite build, dan design-system primitives.
- Platform permission tidak berasal dari tenant role/permission.
- Platform route tidak memakai tenant context sebagai authorization boundary.
- Tenant user tidak dapat mengakses Platform Admin area.
- CLI tersedia hanya untuk initial bootstrap dan emergency operation.
- Web dan CLI memanggil application provisioning use case yang sama.
- Platform Administrator menggunakan `PlatformUser`/`platform_users` serta guard/provider terpisah dari tenant `User`.
- Orang yang sama menggunakan akun terpisah jika memerlukan platform dan tenant access.
- Impersonation tidak tersedia pada MVP.
- Platform Admin dibatasi maksimal dua active sessions.
- Sensitive platform action membutuhkan recent password/MFA confirmation yang berlaku 10 menit.
- Emergency platform credential recovery dilakukan melalui controlled CLI dan mencabut seluruh session/recovery credential lama.
- MVP menggunakan route prefix `/platform` dan `/admin`; pemisahan subdomain tidak diperlukan.

## Ownership

- View, web request, web controller, dan ViewModel mengikuti owning module.
- Shared Blade component hanya digunakan untuk presentation primitive yang benar-benar lintas modul.
- API Resource tidak digunakan sebagai ViewModel secara otomatis; keduanya boleh memiliki shape berbeda.

## Open Decisions

- Lokasi Blade view dan komposisi Platform Admin shell ditetapkan pada [ADR-033](decisions/033-platform-admin-shell-composition.md): capability view tetap module-local, sedangkan layout dan primitive lintas-domain berada pada global `resources/`.
- Design system dan shared presentation component catalog yang lebih luas tetap ditentukan secara inkremental; P01-03 hanya menetapkan primitive shell Platform Admin.
