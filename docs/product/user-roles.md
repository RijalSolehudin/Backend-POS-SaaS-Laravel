# User Roles

Dokumen ini mendefinisikan aktor, bukan implementasi permission final.

| Aktor | Tanggung jawab utama | Target |
|---|---|---|
| Platform Administrator | Controlled provisioning dan platform-level tenant administration | MVP terbatas melalui Platform Admin Web |
| Tenant Owner | Mengelola bisnis, outlet, dan pengguna tenant | MVP minimum |
| Outlet Manager | Mengelola operasi outlet dan approval | MVP minimum |
| Cashier | Shift, order, payment, dan receipt | MVP |
| Waiter | Table service dan pengambilan order | Post-MVP |
| Kitchen Staff | Memproses kitchen ticket/KDS | Post-MVP |
| Inventory Staff | Pembelian, penerimaan, dan stok | Post-MVP |
| Customer | QR self-order dan status order | Post-MVP |

## Prinsip Akses

- Setiap tenant user dimiliki tepat satu tenant pada MVP, tetapi dapat ditugaskan ke banyak outlet dalam tenant tersebut.
- Pengguna hanya dapat bertindak pada tenant dan outlet yang ditugaskan.
- Role adalah kumpulan permission; aturan kepemilikan data tetap diperiksa oleh policy.
- Aksi sensitif seperti void, refund, discount override, dan cash adjustment membutuhkan permission khusus.
- PIN cepat tidak mengurangi auditability; setiap tindakan tetap dikaitkan dengan user yang terautentikasi.
- Registrasi, reassignment, dan revocation perangkat POS membutuhkan permission owner/manager yang sesuai.
- Tenant hanya dapat dibuat melalui privileged platform provisioning; tidak ada public tenant registration pada MVP.
- Platform authority terpisah dari tenant role dan tidak dapat diperoleh melalui tenant role management.
- Platform Administrator menggunakan identity terpisah dan tidak mempunyai tenant membership secara otomatis.
- Tenant role pada MVP bersifat predefined: Tenant Owner, Outlet Manager, dan Cashier.
- Tenant user tidak dapat membuat/mengedit permission matrix atau custom role pada MVP.

## Keputusan Terbuka

- Kebijakan supervisor approval dan metode re-authentication.
