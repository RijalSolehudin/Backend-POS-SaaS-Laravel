# ADR-001: Use MariaDB

- Status: Accepted
- Date: 2026-07-21

## Decision

MariaDB menjadi database engine utama. Dokumentasi dan desain tidak menjanjikan kompatibilitas PostgreSQL/MySQL tambahan pada MVP.

## Consequences

- SQL dan migration harus kompatibel dengan versi minimum MariaDB yang kelak disetujui.
- Fitur PostgreSQL seperti `JSONB`, array type, dan `gen_random_uuid()` tidak digunakan.
- Versi minimum, operational topology, dan backup policy masih harus diputuskan.

