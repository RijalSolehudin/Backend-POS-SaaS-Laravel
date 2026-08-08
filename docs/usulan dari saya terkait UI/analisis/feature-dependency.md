# Analisis Feature Dependency per Subscription Tier

## Tujuan Dokumen
Memastikan setiap tier subscription memiliki fitur yang **lengkap dan fungsional** untuk use case-nya, sehingga **tidak ada flow yang terputus** yang membuat user tidak bisa bertransaksi.

---

## 1. Prinsip Dasar Feature Gating

### ❌ ANTI-PATTERN: Fitur Terpotong
```
User bisa buat order → tapi tidak bisa bayar
User bisa input produk → tapi tidak bisa jual
User bisa lihat laporan → tapi tidak bisa export
```

### ✅ PATTERN: Fitur Lengkap per Tier
```
Starter: Bisa jual → Bisa bayar → Bisa cetak struk → SELESAI
Growth:  Semua Starter + Inventory → Stock ter-track → LENGKAP
Pro:     Semua Growth + Recipe → Auto deduct → LENGKAP
```

---

## 2. Core Transaction Flow (WAJIB di SEMUA Tier)

### Flow Transaksi Minimum yang HARUS Berfungsi

```
┌─────────────────────────────────────────────────────────────────┐
│                    CORE TRANSACTION FLOW                         │
│              (Harus tersedia di SEMUA tier)                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  1. LOGIN                                                        │
│     └─→ User login ke POS App                                   │
│                                                                  │
│  2. OPEN SHIFT (Session)                                         │
│     └─→ Buka shift dengan cash awal                             │
│                                                                  │
│  3. BUAT ORDER                                                   │
│     ├─→ Pilih produk dari katalog                               │
│     ├─→ Set quantity                                            │
│     └─→ Lihat subtotal & total                                  │
│                                                                  │
│  4. PAYMENT                                                      │
│     ├─→ Pilih metode bayar (minimal: Cash)                      │
│     ├─→ Input jumlah bayar                                      │
│     ├─→ Hitung kembalian                                        │
│     └─→ Simpan transaksi                                        │
│                                                                  │
│  5. RECEIPT                                                      │
│     └─→ Cetak/tampilkan struk                                   │
│                                                                  │
│  6. CLOSE SHIFT                                                  │
│     ├─→ Hitung total penjualan                                  │
│     ├─→ Input cash actual                                       │
│     └─→ Lihat selisih (jika ada)                                │
│                                                                  │
│  7. BASIC REPORT                                                 │
│     └─→ Lihat ringkasan penjualan hari ini                      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Fitur Core yang TIDAK BOLEH Dibatasi

| Fitur | Keterangan | Tersedia di |
|-------|------------|-------------|
| **Login/Logout** | Akses sistem | ALL |
| **Open/Close Shift** | Manajemen sesi kasir | ALL |
| **Create Order** | Buat pesanan baru | ALL |
| **Add to Cart** | Tambah item ke order | ALL |
| **Payment - Cash** | Bayar tunai | ALL |
| **Print Receipt** | Cetak struk | ALL |
| **View Daily Sales** | Lihat penjualan hari ini | ALL |
| **Held Order** | Simpan order sementara | ALL |
| **Cash Drawer** | Buka laci kas | ALL |
| **Multi-payment (split)** | Bayar dengan >1 metode | ALL |

---

## 3. Feature Matrix per Tier

### TIER: STARTER (Rp 99K)
**Target:** Warung, gerobak, usaha rumahan
**Use Case:** Jual produk sederhana, terima cash, catat penjualan

#### ✅ INCLUDED (Flow Lengkap)
| Modul | Fitur | Status |
|-------|-------|--------|
| **POS Core** | Login, Shift, Order, Payment, Receipt | ✅ |
| **Product** | Single product (tanpa variant) | ✅ |
| **Product** | Kategori produk | ✅ |
| **Payment** | Cash payment | ✅ |
| **Payment** | 1 payment method tambahan (QRIS/Transfer) | ✅ |
| **Report** | Daily sales summary | ✅ |
| **Customer** | Basic customer data | ✅ |
| **Held Order** | Simpan order sementara | ✅ |
| **Cash Drawer** | Open drawer, cash in/out | ✅ |
| **Split Bill** | Bagi tagihan | ✅ |

#### ❌ NOT INCLUDED
| Modul | Fitur | Alasan |
|-------|-------|--------|
| Product Variant | Size, topping, etc | Complexity tidak perlu untuk warung |
| Product Combo | Paket bundling | Tidak perlu untuk warung |
| Discount/Promo | Diskon order/item | Simplicity |
| Inventory | Stock tracking | Tidak wajib untuk bisnis kecil |
| Table Management | Dine-in tables | Warung tidak perlu |
| Export | Excel/PDF | Self-service tier |
| Multi Payment Method | >2 metode | Cukup Cash + 1 |

#### Flow Analysis: STARTER
```
✅ COMPLETE FLOW:
Login → Open Shift → Add Product → Checkout → Cash Payment → Print Receipt → Close Shift

✅ NO BROKEN FLOW:
- Bisa jual tanpa inventory (stock tidak di-track, OK untuk warung)
- Bisa bayar cash + 1 metode lain
- Bisa lihat penjualan harian (cukup untuk warung)
```

---

### TIER: GROWTH (Rp 299K)
**Target:** Cafe, resto kecil, toko retail
**Use Case:** Produk dengan variasi, multi payment, inventory basic

#### ✅ INCLUDED (Semua Starter +)
| Modul | Fitur | Status |
|-------|-------|--------|
| **Semua Starter** | - | ✅ |
| **Product Variant** | Size, ice level, sugar level | ✅ |
| **Product Combo** | Paket bundling | ✅ |
| **Modifiers** | Add-on/topping | ✅ |
| **Discount** | Diskon order-level | ✅ |
| **Discount** | Diskon item-level | ✅ |
| **Promo** | Voucher/coupon | ✅ |
| **Table Management** | Floor, tables, status | ✅ |
| **Inventory Basic** | Stock level tracking | ✅ |
| **Inventory Basic** | Low stock alert | ✅ |
| **Multi Payment** | Unlimited payment methods | ✅ |
| **Report** | Advanced reports | ✅ |
| **Export** | Excel/PDF | ✅ |
| **Loyalty** | Customer points | ✅ |

#### ❌ NOT INCLUDED
| Modul | Fitur | Alasan |
|-------|-------|--------|
| Recipe/BOM | Auto stock deduction | Pro feature |
| Stock Transfer | Antar outlet | Pro feature (multi-outlet) |
| Purchase Order | PO ke supplier | Pro feature |
| Manager Auth | PIN untuk void/refund | Pro feature |
| Waiter App | Device terpisah | Pro feature |
| QR Order | Customer self-order | Pro feature |
| API Access | External integration | Enterprise |

#### Flow Analysis: GROWTH
```
✅ COMPLETE FLOWS:

1. Variant Product Flow:
   Add Product → Select Variant (Size: L) → Select Modifier (Extra shot) → Checkout

2. Discount Flow:
   Add Products → Apply Discount 10% → Checkout → Payment

3. Table Management Flow:
   Select Table → Create Order → Add Items → Checkout → Table Released

4. Inventory Flow:
   Product Sold → Stock Decreased → Low Stock Alert (if threshold)

✅ NO BROKEN FLOW:
- Inventory basic: stock ter-track otomatis saat jual
- Tidak perlu recipe untuk cafe sederhana (beli jadi, jual langsung)
```

#### ⚠️ DEPENDENCY CHECK: GROWTH
```
┌─────────────────────────────────────────────────────────────────┐
│  INVENTORY BASIC vs RECIPE                                       │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  GROWTH (Inventory Basic):                                       │
│  • Product "Es Kopi Susu" dijual                                │
│  • Stock "Es Kopi Susu" berkurang 1                             │
│  • ✅ WORKS untuk produk jadi (beli jadi, jual langsung)        │
│                                                                  │
│  PROFESSIONAL (Recipe/BOM):                                      │
│  • Product "Es Kopi Susu" dijual                                │
│  • Auto deduct: Kopi 20g, Susu 100ml, Gula 10g                  │
│  • ✅ WORKS untuk produk racikan                                 │
│                                                                  │
│  CONCLUSION:                                                     │
│  • Growth CUKUP untuk cafe yang beli bahan jadi                 │
│  • Cafe yang racik sendiri → perlu upgrade ke Pro               │
│  • TIDAK ADA BROKEN FLOW, hanya level of detail berbeda         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

### TIER: PROFESSIONAL (Rp 599K)
**Target:** Resto menengah, multi-outlet, F&B dengan kitchen
**Use Case:** Recipe management, stock transfer, manager control

#### ✅ INCLUDED (Semua Growth +)
| Modul | Fitur | Status |
|-------|-------|--------|
| **Semua Growth** | - | ✅ |
| **Recipe/BOM** | Resep dengan bahan | ✅ |
| **Recipe/BOM** | Auto stock deduction | ✅ |
| **Recipe/BOM** | Recipe costing | ✅ |
| **Advanced Inventory** | Purchase Order | ✅ |
| **Advanced Inventory** | Goods Receiving | ✅ |
| **Advanced Inventory** | Stock Adjustment | ✅ |
| **Advanced Inventory** | Waste Logging | ✅ |
| **Stock Transfer** | Transfer antar outlet | ✅ |
| **Manager Auth** | PIN verification | ✅ |
| **Manager Auth** | Void authorization | ✅ |
| **Manager Auth** | Refund authorization | ✅ |
| **Manager Auth** | Discount authorization | ✅ |
| **Waiter App** | 1 device included | ✅ |
| **QR Order** | Customer self-order | ✅ |
| **Multi Kitchen** | Kitchen station assignment | ✅ |
| **KOT** | Kitchen Order Ticket | ✅ |

#### ❌ NOT INCLUDED
| Modul | Fitur | Alasan |
|-------|-------|--------|
| API Access | External integration | Enterprise |
| Custom Branding | White-label | Enterprise |
| Waiter Unlimited | Device tak terbatas | Enterprise |
| KDS | Kitchen Display System | Enterprise |
| Dedicated Support | Account Manager | Enterprise |

#### Flow Analysis: PROFESSIONAL
```
✅ COMPLETE FLOWS:

1. Recipe Auto-Deduction Flow:
   Sell "Nasi Goreng" → Recipe lookup → Deduct: Nasi 200g, Telur 1pc, Minyak 20ml

2. Purchase Order Flow:
   Create PO → Send to Supplier → Receive Goods → Stock Updated

3. Manager Authorization Flow:
   Cashier request Void → Manager PIN required → Authorized → Void completed

4. Stock Transfer Flow:
   Outlet A → Create Transfer → Outlet B Receive → Both stocks updated

5. Waiter Flow:
   Waiter App → Take Order → Send to Kitchen → KOT printed → Served → Checkout

✅ NO BROKEN FLOW:
- Recipe + Inventory terintegrasi penuh
- Manager Auth untuk semua sensitive action
- Multi-outlet dengan stock transfer
```

---

### TIER: ENTERPRISE (Rp 1.499K)
**Target:** Jaringan resto, franchise, white-label
**Use Case:** Full feature, API integration, unlimited scale

#### ✅ INCLUDED (Semua Professional +)
| Modul | Fitur | Status |
|-------|-------|--------|
| **Semua Professional** | - | ✅ |
| **API Access** | REST API | ✅ |
| **API Access** | Webhook | ✅ |
| **Custom Branding** | Logo, warna, receipt | ✅ |
| **Waiter App** | Unlimited devices | ✅ |
| **KDS** | Kitchen Display System | ✅ |
| **Custom Reports** | Report builder | ✅ |
| **Scheduled Export** | Auto export data | ✅ |
| **Dedicated Support** | Account Manager | ✅ |
| **Training** | Staff training | ✅ |
| **Onboarding** | Setup assistance | ✅ |

---

## 4. Feature Dependency Matrix

### Critical Dependencies (HARUS di Tier yang Sama)

| Fitur Utama | Fitur Dependent | Tier Min | Alasan |
|-------------|-----------------|----------|--------|
| Create Order | Payment | ALL | Tidak ada gunanya order tanpa bayar |
| Payment | Receipt | ALL | Bukti transaksi wajib |
| Open Shift | Close Shift | ALL | Session harus bisa ditutup |
| Product Variant | Add Variant to Cart | Growth+ | Variant harus bisa dijual |
| Discount | Apply to Order | Growth+ | Discount harus applicable |
| Table Mgmt | Table Checkout | Growth+ | Table harus bisa di-release |
| Inventory | Stock Deduction | Growth+ | Jual = stock berkurang |
| Recipe | Auto Deduction | Pro+ | Recipe harus auto-apply |
| Manager Auth | Void/Refund | Pro+ | Auth action harus bisa dilakukan |
| Stock Transfer | Receive Transfer | Pro+ | Transfer harus bisa diterima |
| QR Order | Process QR Order | Pro+ | QR order harus bisa diproses |

### Fitur yang TIDAK Dependent (Bisa Berdiri Sendiri)

| Fitur | Bisa Tanpa | Keterangan |
|-------|------------|------------|
| Inventory | Recipe | Track stock produk jadi saja |
| Table Mgmt | QR Order | Manual order dari waiter |
| Report | Export | Lihat di screen saja |
| Customer | Loyalty | Data customer tanpa points |
| Discount | Promo/Voucher | Manual discount saja |

---

## 5. Upgrade Path & Feature Unlock

### Starter → Growth
```
UNLOCKED:
├── Product Variant & Combo
├── Modifiers/Add-ons
├── Discount & Promo
├── Table Management
├── Inventory Basic
├── Multi Payment Method
├── Export Excel/PDF
└── Customer Loyalty

DATA MIGRATION: Tidak ada
- Produk tetap (bisa tambah variant)
- Customer tetap (bisa dapat points)
- Transaksi tetap
```

### Growth → Professional
```
UNLOCKED:
├── Recipe/BOM
├── Advanced Inventory (PO, Receiving, Adjustment)
├── Stock Transfer
├── Manager Authorization
├── Waiter App (1 device)
├── QR Order
└── Multi Kitchen Station

DATA MIGRATION:
- Produk perlu di-setup recipe (optional)
- User perlu assign PIN untuk manager auth
```

### Professional → Enterprise
```
UNLOCKED:
├── API Access
├── Custom Branding
├── Unlimited Waiter Devices
├── KDS
├── Custom Reports
├── Scheduled Export
└── Dedicated Support

DATA MIGRATION: Tidak ada
- API key generated
- Branding bisa di-custom
```

---

## 6. Edge Cases & Handling

### Case 1: Downgrade dengan Data Melebihi Limit
```
SCENARIO: Pro (unlimited product) → Growth (500 product limit)
User punya 600 produk

HANDLING:
1. Warning sebelum downgrade: "100 produk akan di-archive"
2. Archive 100 produk TERBARU (by created_at)
3. 500 produk TERLAMA tetap aktif
4. Archived products bisa di-restore jika upgrade lagi

TIDAK ADA BROKEN FLOW:
- User tetap bisa jual 500 produk aktif
- Data tidak hilang, hanya hidden
```

### Case 2: Downgrade dengan Fitur Aktif
```
SCENARIO: Pro → Growth, user punya Recipe aktif

HANDLING:
1. Recipe tetap tersimpan (data tidak hilang)
2. Recipe tidak berfungsi (auto-deduct OFF)
3. Inventory jadi "basic mode" (deduct product level)
4. Upgrade lagi → Recipe aktif kembali

TIDAK ADA BROKEN FLOW:
- Transaksi tetap jalan (tanpa recipe)
- Stock tetap berkurang (level produk)
```

### Case 3: Offline dengan Subscription Expired
```
SCENARIO: User sedang offline, subscription expire

HANDLING:
1. User tetap bisa transaksi offline
2. Close shift → Sync ke server
3. Server terima semua transaksi (data aman)
4. Server return: subscription_expired = true
5. App FREEZE setelah shift closed
6. Tidak bisa open shift baru sampai bayar

TIDAK ADA BROKEN FLOW:
- Transaksi terakhir tetap tersimpan
- Freeze di "clean point" (antar shift)
- User tidak kehilangan data
```

### Case 4: Manager Auth dengan Manager Absent
```
SCENARIO: Cashier perlu void, tapi Manager tidak ada

HANDLING (Pro+):
1. Void butuh Manager PIN
2. Manager tidak ada → tidak bisa void
3. Alternative: Cancel order (buat order baru)
4. Manager bisa void nanti saat available

BUSINESS LOGIC:
- Ini INTENDED behavior, bukan bug
- Mencegah void tanpa approval
- Owner bisa override (Owner juga punya PIN)
```

---

## 7. Checklist Implementasi Feature Gating

### Backend Checklist
- [ ] Middleware `CheckFeatureAccess` untuk setiap route
- [ ] Helper `$tenant->hasFeature('feature_name')`
- [ ] Response 403 dengan message "Upgrade to {tier} to access this feature"
- [ ] Feature flags di-cache (invalidate on subscription change)

### Frontend Checklist
- [ ] Disable/hide menu untuk fitur tidak tersedia
- [ ] Tooltip "Upgrade to access" saat hover disabled menu
- [ ] Upgrade CTA di halaman fitur terkunci
- [ ] Banner "Current Plan: {tier}" di dashboard

### Mobile App Checklist
- [ ] Sync feature flags saat app start
- [ ] Cache feature flags untuk offline
- [ ] Hide UI elements untuk fitur tidak tersedia
- [ ] Deep link ke upgrade page

### Feature Flag List
```php
// Starter (default)
'pos_core' => true,
'single_product' => true,
'cash_payment' => true,
'basic_report' => true,
'held_order' => true,
'cash_drawer' => true,
'split_bill' => true,

// Growth+
'product_variant' => false,
'product_combo' => false,
'modifiers' => false,
'discount' => false,
'promo' => false,
'table_management' => false,
'inventory_basic' => false,
'multi_payment' => false,
'export' => false,
'loyalty' => false,

// Professional+
'recipe_bom' => false,
'inventory_advanced' => false,
'stock_transfer' => false,
'manager_auth' => false,
'waiter_app' => false,
'qr_order' => false,
'multi_kitchen' => false,

// Enterprise
'api_access' => false,
'custom_branding' => false,
'waiter_unlimited' => false,
'kds' => false,
'custom_reports' => false,
'scheduled_export' => false,
```

---

## 8. Summary

### Prinsip Utama
1. **Core transaction flow TIDAK BOLEH dibatasi** - semua tier bisa jualan
2. **Fitur yang di-unlock harus LENGKAP** - tidak ada setengah-setengah
3. **Upgrade membuka fitur, bukan memperbaiki yang rusak** - tidak ada "fixing" saat upgrade
4. **Downgrade tidak menghapus data** - hanya archive/disable
5. **Offline tetap fungsional** - freeze hanya di "clean point"

### Validasi Final
Sebelum release tier baru, pastikan:
- [ ] User bisa complete full transaction cycle
- [ ] Tidak ada error 403 di tengah flow transaksi
- [ ] Report sesuai dengan fitur yang di-unlock
- [ ] Export tersedia jika fitur export aktif
- [ ] Upgrade/downgrade tidak corrupt data
