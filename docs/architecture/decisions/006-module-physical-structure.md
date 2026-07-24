# ADR-006: Module Physical Structure

- Status: Accepted
- Date: 2026-07-21

## Decision

Setiap domain module berada di bawah `app/Modules/<Module>` dan dapat memiliki empat lapisan berikut:

```text
<Module>/
├── Application/
│   ├── Actions/
│   ├── Data/
│   └── Contracts/
├── Domain/
│   ├── Models/
│   ├── Enums/
│   ├── Events/
│   └── Exceptions/
├── Infrastructure/
└── Presentation/
    └── Http/
        ├── Api/V1/
        └── Web/
```

Konsep lintas modul ditempatkan di `app/Shared` hanya jika tidak mempunyai owning domain module yang tepat.

## Rules

- Folder dibuat secara proporsional; struktur kosong tidak perlu dibuat.
- Application action mewakili satu intent bisnis.
- Presentation tidak menyimpan business logic.
- Web dan API presentation memanggil application actions yang sama dan tidak saling memanggil melalui HTTP.
- Eloquent model boleh berada di `Domain/Models`.
- Infrastructure berisi adapter dan detail integrasi.
- Module lain menggunakan published action, contract, atau event; tidak mengubah internal model secara langsung.
- `Shared` bukan tempat penampungan helper umum.

## Consequences

- Ownership kode dapat dikenali dari struktur direktori.
- Namespace lebih panjang daripada struktur Laravel default.
- Developer harus menjaga boundary melalui review dan automated architecture tests bila kelak disetujui.
- Tidak semua modul harus memiliki seluruh subfolder sejak awal.
