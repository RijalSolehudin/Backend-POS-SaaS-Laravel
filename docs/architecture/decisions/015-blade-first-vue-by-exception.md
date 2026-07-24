# ADR-015: Blade-First, Vue by Exception

- Status: Accepted
- Date: 2026-07-24

## Context

Web Admin terutama melayani konfigurasi, CRUD, form, table, reporting, dan back-office dengan jumlah pengguna relatif sedikit. Operasi POS yang membutuhkan pengalaman aplikasi dedicated berada di Flutter. Menggunakan Vue untuk seluruh Web Admin akan menambah client-state, dependency, testing, dan maintenance surface sebelum kompleksitas tersebut diperlukan.

## Decision

- Blade menjadi page/layout/rendering default Web Admin.
- Alpine.js digunakan untuk interaksi lokal dan ringan.
- Vue tidak menjadi global framework Web Admin pada MVP.
- Vue dapat digunakan sebagai isolated component hanya ketika sebuah layar mempunyai client-side state kompleks yang tidak layak dipelihara dengan Alpine.
- Perubahan luas menjadi Inertia + Vue membutuhkan ADR baru.

## Suitable Blade and Alpine Use Cases

- CRUD form dan server-side validation.
- Paginated/filterable table.
- Modal, dropdown, tabs, confirmation, dan toast.
- Dependent input, row repeater, preview, dan lightweight autocomplete.
- Bulk selection dan status polling sederhana.

## Vue Exception Criteria

Vue dapat dipertimbangkan untuk:

- Floor-plan drag/drop editor dengan zoom, resize, dan complex state.
- Spreadsheet-like stock opname dengan banyak editable cells.
- Visual layout/template designer.
- High-frequency realtime dashboard dengan banyak stateful widgets.
- Nested editor dengan undo/redo atau complex cross-component state.

Penggunaan Vue memerlukan review yang menunjukkan bahwa Alpine menghasilkan complexity/maintainability risk yang lebih besar.

## Boundary Rules

- Vue dipasang sebagai isolated component pada halaman Blade bila memungkinkan.
- Alpine dan Vue tidak mengontrol DOM subtree yang sama.
- Vue component tidak memuat domain/business rules yang seharusnya berada pada backend.
- Vue menggunakan Vite build yang sama dan tidak membuat aplikasi/deployment backend terpisah.
- Web session, CSRF, policy, tenant context, dan application use cases tetap berlaku.
- Vue exception tidak mengubah Flutter/mobile REST API contract secara otomatis.

## Performance and Reliability Rules

- Table besar menggunakan server-side pagination.
- Query menggunakan eager loading/index yang sesuai dan menghindari N+1.
- Report/export berat diproses asynchronous bila diperlukan.
- Blade views diprecompile/cache pada production deployment.
- Alpine tidak memuat dataset besar ke browser hanya untuk local filtering.
- Autocomplete/search data besar menggunakan server endpoint yang terkontrol.

## Consequences

- Default stack memiliki lebih sedikit moving parts dan lebih mudah dipelihara.
- Full-page navigation tetap menjadi pola normal Web Admin.
- Complex screen tetap memiliki escape hatch tanpa menulis ulang seluruh admin.
- Tim harus menjaga agar Alpine component tidak tumbuh menjadi mini-SPA; ketika itu terjadi, layar tersebut harus direview untuk Vue exception.

