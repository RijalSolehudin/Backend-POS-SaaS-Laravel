# Reporting Module

## Owns

- Read models, analytical queries, exports, dan dashboard composition.
- Tidak memiliki source-of-truth transaksi.

## MVP Use Cases

- Shift summary.
- Daily sales summary.
- Payment method breakdown.
- Void/cancel audit summary minimum.

## Principles

- Reporting boleh membaca lintas modul tanpa mengubah data sumber.
- Definisi metric terdokumentasi dan konsisten.
- Timezone outlet dan business date diterapkan secara eksplisit.
- Laporan finansial dapat direkonsiliasi dengan transaksi sumber.

## Open Decisions

- Synchronous query versus projection.
- Export formats.
- Data retention dan aggregation strategy.
- Definisi net sales, gross sales, discount, tax, dan service charge final.

