# POS Device and API Operations

Dokumen ini berlaku untuk operator yang mengelola device POS terdaftar dan token Flutter API.

## Device Registration

1. Login Tenant Admin sebagai Tenant Owner, atau Outlet Manager yang ditugaskan pada outlet target.
2. Buka `Admin > Devices`.
3. Daftarkan `installation_id` dari aplikasi Flutter, nama device, platform, app version, dan outlet.
4. Pastikan audit `pos_device.registered` tercatat pada `tenancy_audit_events`.

Installation ID bukan secret. Jangan gunakan IMEI, MAC address, atau hardware fingerprint sebagai prasyarat.

## Device Reassignment

1. Login sebagai Tenant Owner.
2. Buka `Admin > Devices`.
3. Pilih outlet baru dalam tenant yang sama.
4. Isi reason operasional yang dapat diaudit.
5. Setelah reassignment, seluruh token terkait device dicabut dan POS harus login ulang.

## Device Revocation

1. Login sebagai Tenant Owner.
2. Buka `Admin > Devices`.
3. Isi reason revocation.
4. Jalankan revoke.
5. Pastikan token lama menerima error API dengan stable code `DEVICE_REVOKED` atau tidak lagi terautentikasi.

Device record tidak dihapus agar audit lifecycle tetap tersedia.

## Flutter API Token

- Login POS memakai `POST /api/v1/pos/auth/login`.
- Token berlaku maksimal 30 hari.
- Login ulang pada user-device yang sama mengganti token lama.
- Logout memakai `POST /api/v1/pos/auth/logout` dan hanya mencabut current token.
- Scheduler menjalankan `sanctum:prune-expired --hours=24` harian untuk membersihkan expired token yang sudah melewati retention window.

## Troubleshooting

- `DEVICE_NOT_REGISTERED`: installation ID belum terdaftar pada tenant user.
- `DEVICE_REVOKED`: device pernah direvoke; daftarkan device baru atau investigasi reason.
- `OUTLET_NOT_FOUND`: route outlet tidak cocok dengan binding device atau outlet tidak aktif.
- `TENANCY_FORBIDDEN`: user tidak punya role/assignment yang cukup.

Gunakan `X-Request-ID` atau `trace_id` dari error body untuk korelasi log.
