# Sales Retry and Recovery

Runbook ini dipakai saat operator atau developer menemukan request POS yang timeout, response tidak jelas, atau ada indikasi state finansial tidak lengkap.

## Safe Retry Policy

Gunakan `Idempotency-Key` yang sama untuk retry mutation yang sama persis. Jangan mengganti payload, actor, outlet, atau target saat retry memakai key yang sama.

| Mutation | Idempotency action | Lock boundary | Retry result expected |
|---|---|---|---|
| Create draft order | `orders.create` | active shift, order number counter, idempotency record | order yang sama dikembalikan |
| Complete order with payment | `orders.complete` | order, shift, idempotency record | payment dan receipt tidak terduplikasi |
| Void completed order | `orders.void` | order, approval, idempotency record | order tetap voided satu kali |
| Request approval | `approvals.request` | approval fingerprint, idempotency record | approval yang sama dikembalikan |
| Full refund | `payments.refund` | order, payment, approval, shift, idempotency record | refund hanya tercatat satu kali |
| Cash movement | `cash_movements.record` | shift, approval bila wajib, idempotency record | cash movement hanya tercatat satu kali |
| Close shift | `shifts.close` | shift | summary closing tetap satu versi final |

Jika retry memakai key yang sama tetapi payload berubah, API harus mengembalikan `IDEMPOTENCY_CONFLICT`. Buat request baru hanya setelah operator memastikan mutation sebelumnya belum berhasil.

## Recovery Check

Jalankan command berikut dari backend:

```bash
php artisan sales:recovery-check
```

Untuk output yang lebih mudah diproses:

```bash
php artisan sales:recovery-check --json
```

Exit code `0` berarti tidak ada temuan. Exit code `1` berarti ada state yang perlu review operator sebelum transaksi dilanjutkan atau dibetulkan manual.

## Finding Codes

| Code | Meaning | First response |
|---|---|---|
| `ORDER_COMPLETED_WITHOUT_RECORDED_PAYMENT` | Order sudah `completed`, tetapi tidak ada payment `recorded`. | Cek request completion, audit event, dan idempotency record. Jangan buat payment manual sebelum nominal terkonfirmasi. |
| `ORDER_COMPLETED_WITHOUT_RECEIPT` | Order sudah `completed`, tetapi receipt snapshot belum ada. | Verifikasi payment sudah recorded, lalu pertimbangkan regenerate receipt snapshot lewat maintenance fix yang diaudit. |
| `PAYMENT_RECORDED_FOR_NON_COMPLETED_ORDER` | Payment recorded menempel ke order yang bukan `completed`. | Tahan settlement operasional dan review apakah completion rollback sebagian. |
| `REFUND_APPROVAL_NOT_CONSUMED` | Refund tercatat, tetapi approval belum `consumed` atau hilang. | Review audit approval/refund dan tandai approval secara konsisten melalui fix yang diaudit. |
| `CASH_OUT_APPROVAL_NOT_CONSUMED` | Cash out tercatat tanpa approval consumed. | Cocokkan threshold approval dan bukti supervisor sebelum shift closing diterima. |
| `IDEMPOTENCY_RESOURCE_MISSING` | Idempotency record menunjuk resource yang tidak ada. | Jangan retry dengan payload berbeda; review log berdasarkan action dan idempotency key. |

## Audit and Correlation

Saat membaca error API, simpan `X-Request-ID` atau `trace_id` dari response. Cocokkan nilai tersebut dengan `correlation_id` pada `sales_audit_events`, lalu bandingkan target type, target ID, actor, outlet, reason, dan timestamp.

Recovery manual harus selalu meninggalkan audit trail baru yang menjelaskan:

- finding code;
- operator/developer yang melakukan review;
- resource yang terdampak;
- alasan keputusan;
- correlation ID dari request awal bila tersedia.

## Stop Rule

Berhenti dan eskalasi ke product owner bila temuan berhubungan dengan settlement payment gateway, offline sync conflict, multi-outlet duplicate request, atau perubahan nominal finansial yang tidak bisa dibuktikan dari audit/internal database.
