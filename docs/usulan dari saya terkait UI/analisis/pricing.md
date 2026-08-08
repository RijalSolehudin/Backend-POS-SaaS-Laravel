# Analisis Pricing Strategy - Ultimate POS SaaS

## Daftar Isi
1. [Benchmark Kompetitor](#1-benchmark-kompetitor)
2. [Fitur Ultimate POS SaaS](#2-fitur-ultimate-pos-saas)
3. [Analisis Perbandingan](#3-analisis-perbandingan)
4. [Rekomendasi Pricing](#4-rekomendasi-pricing)
5. [Trial & Onboarding Model](#5-trial--onboarding-model)
6. [Strategi Monetisasi](#6-strategi-monetisasi)
7. [Technical Decisions](#7-technical-decisions)
8. [Go-To-Market Strategy](#8-go-to-market-strategy)
9. [Implementasi Teknis](#9-implementasi-teknis)
10. [Gap Analysis & Enhancement](#10-gap-analysis--enhancement-required)

---

## 1. Benchmark Kompetitor

### Moka POS (GoTo Group)

| Tier | Harga/outlet/bulan | Employee Limit | Customer DB | Fitur Utama |
|------|-------------------|----------------|-------------|-------------|
| **Basic** | Rp 299.000 | 5 (+Rp10k/slot) | 10.000 | POS, Report, Inventory, QRIS |
| **Pro** | Rp 499.000 | 10 | 50.000 | + QR Order, Table Management, Loyalty |
| **Enterprise** | Rp 799.000 | 20 | Unlimited | + Advanced Inventory, Account Manager |

**Key Insight Moka:**
- Model: **Per outlet/bulan**
- Diferensiasi: Jumlah employee, customer database, fitur
- Add-on berbayar terpisah (GoFood/GrabFood integration, Accounting)
- Tidak ada transaction fee di subscription (terpisah dari payment processing)
- Sudah tersebar di 100+ kota dengan 35.000+ merchant

### Kompetitor Lain (Referensi)

| POS | Model | Harga Mulai | Catatan |
|-----|-------|-------------|---------|
| **Pawoon** | Per outlet/bulan | Rp 249.000 | Fokus UMKM |
| **Majoo** | Per outlet/bulan | Rp 299.000 | F&B focused |
| **iSeller** | Per outlet/bulan | Rp 199.000 | Multi-channel retail |
| **Olsera** | Per outlet/bulan | Rp 149.000 | Budget option |

**Insight Pasar Indonesia:**
- Range harga: Rp 149.000 - Rp 799.000 per outlet/bulan
- Model dominan: Per outlet subscription
- Tiering berdasarkan: Fitur, user limit, outlet limit

---

## 2. Fitur Ultimate POS SaaS

### Fitur yang Sudah Dibangun

```
CORE FEATURES
├── Multi-Tenancy & Outlet Management
│   ├── Multi-tenant architecture (white-label ready)
│   ├── Unlimited outlets per tenant
│   ├── Custom tax & service charge per outlet
│   └── Custom receipt branding
│
├── User & Access Control
│   ├── Multi-role (Super Admin, Owner, Manager, Cashier, Waiter, Kitchen)
│   ├── Custom roles per tenant
│   ├── Fine-grained permissions
│   └── PIN login untuk akses cepat
│
├── Inventory & Stock Management
│   ├── Multi-unit conversion (kg, g, L, pcs, dll)
│   ├── Supplier management
│   ├── Stock level tracking (min/max/reorder)
│   ├── Expiry tracking
│   ├── Recipe/BOM with auto stock deduction
│   ├── Purchase orders
│   ├── Stock transfers between outlets
│   └── Stock adjustments & waste logging
│
├── Product & Menu Management
│   ├── Single, Variant, Combo products
│   ├── Hierarchical categories
│   ├── Variant groups (Size, Ice Level, etc.)
│   ├── Modifiers/Add-ons
│   ├── Per-outlet pricing
│   └── Kitchen station assignment
│
├── POS Core
│   ├── Shift/Session management
│   ├── Order types (dine-in, takeaway, delivery, QR)
│   ├── Multi-item with variant & modifier
│   ├── Discount (order/item level)
│   ├── Tax & service charge calculation
│   ├── Void/cancel with manager authorization
│   └── Held orders
│
├── Payment Management
│   ├── Multi-payment method
│   ├── Split payment
│   ├── Payment gateway (Xendit integration)
│   └── Refund management
│
├── Table Management & Floor Plan
│   ├── Multi-floor support
│   ├── Table status tracking
│   ├── Table merge & transfer
│   ├── QR code per table
│   └── Table sessions
│
├── Manager Authorization
│   ├── PIN verification for sensitive actions
│   ├── Configurable actions (void, refund, discount, etc.)
│   ├── Authorization logs
│   └── Lockout protection
│
└── Reporting & Analytics
    ├── Sales summary (daily/weekly/monthly)
    ├── Sales by category, product, payment method
    ├── Shift reports
    └── Export to Excel/PDF
```

### Platform yang Tersedia

| Platform | Status | Fungsi |
|----------|--------|--------|
| Web Admin | ✅ Ready | Dashboard, master data, reporting |
| Flutter POS App | 🔄 In Progress | Kasir, pembayaran, offline-first |
| Flutter Waiter App | 🔄 In Progress | Order taking, table management |
| REST API | ✅ Ready | Mobile integration (Swagger docs) |
| KDS (Web) | 📋 Planned | Kitchen display |
| QR Order (PWA) | 📋 Planned | Customer self-order |

---

## 3. Analisis Perbandingan

### Ultimate POS vs Moka POS

| Fitur | Ultimate POS | Moka Basic | Moka Pro | Moka Enterprise |
|-------|-------------|------------|----------|-----------------|
| **Multi-tenant** | ✅ | ❌ | ❌ | ❌ |
| **POS Core** | ✅ | ✅ | ✅ | ✅ |
| **Inventory Basic** | ✅ | ✅ | ✅ | ✅ |
| **Recipe/BOM** | ✅ | ❌ | ✅ | ✅ |
| **Advanced Inventory** | ✅ | ❌ | ❌ | ✅ |
| **Table Management** | ✅ | ❌ | ✅ | ✅ |
| **QR Order** | ✅ | ❌ | ✅ | ✅ |
| **Multi-outlet** | ✅ | ❌ | ❌ | ❌ |
| **Stock Transfer** | ✅ | ❌ | ❌ | ✅ |
| **Manager Authorization** | ✅ | ❌ | ❌ | ❌ |
| **Loyalty/Points** | ✅ | ❌ | ✅ | ✅ |
| **Custom Roles** | ✅ | ❌ | ❌ | ❌ |
| **API Access** | ✅ | ❌ | ❌ | ❌ |
| **Waiter App** | ✅ | ❌ | ❌ | ❌ |
| **Offline Mode** | ✅ | ❌ | ❌ | ❌ |

### Value Proposition

**Keunggulan Ultimate POS:**
1. **Multi-tenant architecture** - Bisa white-label untuk reseller
2. **Recipe/BOM built-in** - Tidak perlu add-on terpisah
3. **Manager Authorization** - Keamanan operasional tinggi
4. **Offline-first POS App** - Tetap jalan tanpa internet
5. **Waiter App dedicated** - Bukan hanya POS biasa
6. **API Access** - Integrasi dengan sistem lain
7. **Custom roles & permissions** - Fleksibilitas tinggi

---

## 4. Rekomendasi Pricing

### Model Pricing: Hybrid (Per Tenant + Per Outlet)

Mengapa hybrid?
- **Per Tenant** = Base fee untuk infrastruktur
- **Per Outlet** = Scaling sesuai penggunaan
- Lebih fair untuk bisnis dengan banyak outlet

---

### Tier 1: STARTER
**Target:** Warung, gerobak, usaha rumahan

| Item | Detail |
|------|--------|
| **Harga** | **Rp 99.000/bulan** |
| **Outlet** | 1 outlet |
| **User** | 3 users |
| **Produk** | 100 produk |
| **Transaksi** | Unlimited |

**Fitur:**
- ✅ POS Core (order, payment, receipt)
- ✅ Manajemen produk (single product only)
- ✅ Laporan penjualan dasar (daily sales)
- ✅ Manajemen pelanggan
- ✅ Cash + 1 payment method
- ❌ Product variant/combo
- ❌ Diskon & promo
- ❌ Inventory/Stock
- ❌ Table Management
- ❌ Recipe/BOM
- ❌ Waiter App
- ❌ Export Excel/PDF
- ❌ API Access

---

### Tier 2: GROWTH (Most Popular)
**Target:** Cafe, resto kecil, toko retail

| Item | Detail |
|------|--------|
| **Harga** | **Rp 299.000/bulan** |
| **Outlet** | 2 outlets |
| **User** | 10 users |
| **Produk** | 500 produk |
| **Transaksi** | Unlimited |

**Fitur:**
- ✅ Semua fitur Starter
- ✅ Produk variant & combo
- ✅ Modifiers/Add-ons
- ✅ Table Management
- ✅ Inventory dasar (stock tracking)
- ✅ Multi payment method
- ✅ Laporan lanjutan
- ✅ Customer loyalty points
- ✅ Diskon & promo
- ✅ Export Excel/PDF
- ❌ Recipe/BOM
- ❌ Stock transfer
- ❌ Waiter App
- ❌ API Access

**Tambahan Outlet:** +Rp 100.000/outlet/bulan

---

### Tier 3: PROFESSIONAL
**Target:** Resto menengah, multi-outlet

| Item | Detail |
|------|--------|
| **Harga** | **Rp 599.000/bulan** |
| **Outlet** | 5 outlets |
| **User** | 25 users |
| **Produk** | Unlimited |
| **Transaksi** | Unlimited |

**Fitur:**
- ✅ Semua fitur Growth
- ✅ Recipe/BOM (auto stock deduction)
- ✅ Advanced Inventory (PO, receiving, adjustment)
- ✅ Stock transfer antar outlet
- ✅ Manager Authorization (void, refund, discount)
- ✅ Waiter App (1 device included)
- ✅ QR Order
- ✅ Multi kitchen station
- ❌ API Access
- ❌ Custom branding

**Tambahan Outlet:** +Rp 80.000/outlet/bulan
**Tambahan Waiter Device:** +Rp 50.000/device/bulan

---

### Tier 4: ENTERPRISE
**Target:** Jaringan resto besar, franchise, white-label reseller

| Item | Detail |
|------|--------|
| **Harga** | **Rp 1.499.000/bulan** |
| **Outlet** | Unlimited |
| **User** | Unlimited |
| **Produk** | Unlimited |
| **Transaksi** | Unlimited |

**Fitur:**
- ✅ Semua fitur Professional
- ✅ API Access (REST API)
- ✅ Custom branding (logo, warna, receipt)
- ✅ Waiter App unlimited devices
- ✅ KDS (Kitchen Display System)
- ✅ Custom reports
- ✅ Data export scheduled
- ✅ SLA 99.9% uptime

**Exclusive Enterprise Benefits:**
- ✅ **Onboarding session** (setup & configuration assistance)
- ✅ **Training session** (staff training via video call)
- ✅ **Dedicated Account Manager**
- ✅ **Priority support** (WhatsApp dedicated, response < 2 jam)
- ✅ **Quarterly business review**

**Custom pricing available untuk:**
- White-label licensing
- On-premise deployment
- Custom integration

---

### Perbandingan Tier

| Fitur | Starter | Growth | Professional | Enterprise |
|-------|---------|--------|--------------|------------|
| **Harga** | **Rp 99K** | **Rp 299K** | **Rp 599K** | **Rp 1.499K** |
| Outlets | 1 | 2 | 5 | Unlimited |
| Users | 3 | 10 | 25 | Unlimited |
| Products | 100 | 500 | Unlimited | Unlimited |
| Transaksi | Unlimited | Unlimited | Unlimited | Unlimited |
| POS Core | ✅ | ✅ | ✅ | ✅ |
| Variant/Combo | ❌ | ✅ | ✅ | ✅ |
| Diskon & Promo | ❌ | ✅ | ✅ | ✅ |
| Table Mgmt | ❌ | ✅ | ✅ | ✅ |
| Inventory | ❌ | Basic | Advanced | Advanced |
| Recipe/BOM | ❌ | ❌ | ✅ | ✅ |
| Stock Transfer | ❌ | ❌ | ✅ | ✅ |
| Manager Auth | ❌ | ❌ | ✅ | ✅ |
| Waiter App | ❌ | ❌ | 1 device | Unlimited |
| QR Order | ❌ | ❌ | ✅ | ✅ |
| KDS | ❌ | ❌ | ❌ | ✅ |
| API Access | ❌ | ❌ | ❌ | ✅ |
| Custom Brand | ❌ | ❌ | ❌ | ✅ |
| Export Excel/PDF | ❌ | ✅ | ✅ | ✅ |
| **Support** | Self-service | Self-service | Self-service | Dedicated |
| **Training** | ❌ | ❌ | ❌ | ✅ |
| **Onboarding** | ❌ | ❌ | ❌ | ✅ |

---

### Diskon & Promo

| Periode | Diskon |
|---------|--------|
| **Yearly (bayar 12 bulan)** | 20% off |
| **Yearly Enterprise** | 25% off |
| **Early Bird (100 customer pertama)** | 30% off 3 bulan pertama |
| **Referral** | 1 bulan gratis per referral sukses |

### Harga Tahunan (dengan diskon 20%)

| Tier | Bulanan | Tahunan | Per Bulan (Tahunan) |
|------|---------|---------|---------------------|
| Starter | Rp 99K | Rp 950.400 | **Rp 79.200** |
| Growth | Rp 299K | Rp 2.870.400 | **Rp 239.200** |
| Professional | Rp 599K | Rp 5.750.400 | **Rp 479.200** |
| Enterprise | Rp 1.499K | Rp 14.390.400 | **Rp 1.199.200** |

---

## 5. Trial & Onboarding Model

### Free Trial (14 Hari)

```
┌─────────────────────────────────────────────────────────────┐
│                    TRIAL MODEL                               │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  TRIAL (14 hari)                                            │
│  ───────────────                                            │
│  • Full akses Professional tier                             │
│  • Tidak perlu kartu kredit saat daftar                     │
│  • Semua fitur terbuka untuk dicoba                         │
│                                                              │
│  SETELAH TRIAL HABIS:                                       │
│  ────────────────────                                       │
│  ├── Bayar → Aktif (pilih tier: Starter/Growth/Pro/Ent)    │
│  └── Tidak bayar → FREEZE (read-only)                       │
│                                                              │
│  FREEZE MODE:                                                │
│  ────────────                                               │
│  • ❌ Tidak bisa buat transaksi baru                        │
│  • ❌ Tidak bisa tambah produk/user/outlet                  │
│  • ✅ Bisa login & lihat data lama                          │
│  • ✅ Bisa export data                                       │
│  • 🔔 Banner permanen "Upgrade untuk melanjutkan"           │
│                                                              │
│  TIDAK ADA FREE TIER SELAMANYA                              │
│  Alasan: Sustainability bisnis, fokus ke paying customers   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Subscription Status Flow

```
┌──────────┐    14 hari    ┌──────────┐   1 hari    ┌──────────┐
│  TRIAL   │──────────────▶│ EXPIRED  │────────────▶│  FROZEN  │
└──────────┘               └──────────┘             └──────────┘
     │                          │                        │
     │ Bayar                    │ Bayar                  │ Bayar
     ▼                          ▼                        ▼
┌──────────┐               ┌──────────┐             ┌──────────┐
│  ACTIVE  │               │  ACTIVE  │             │  ACTIVE  │
└──────────┘               └──────────┘             └──────────┘
     │
     │ Periode habis + 1 hari grace
     ▼
┌──────────┐
│  FROZEN  │ (read-only, bisa reaktivasi kapan saja)
└──────────┘
     │
     │ Tidak aktif 1 tahun
     ▼
┌──────────┐
│ DELETED  │ (data dihapus permanen, dengan warning email sebelumnya)
└──────────┘
```

### Grace Period & Data Retention

| Status | Durasi | Aksi |
|--------|--------|------|
| Trial habis | +1 hari grace | Freeze jika tidak bayar |
| Subscription habis | +1 hari grace | Freeze jika tidak perpanjang |
| Frozen | Unlimited | Data tetap aman, bisa reaktivasi |
| Frozen + tidak aktif | 1 tahun | Data dihapus (dengan warning email H-30, H-7) |

### Self-Service Onboarding Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    ONBOARDING FLOW                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. PRICING PAGE (Public)                                   │
│     └─→ /pricing                                            │
│         • Compare 4 tier                                    │
│         • CTA "Coba Gratis 14 Hari"                         │
│                                                             │
│  2. REGISTRATION                                            │
│     └─→ /register                                           │
│         • Nama bisnis                                       │
│         • Email & password                                  │
│         • No HP (optional)                                  │
│         • Auto: Trial 14 hari, akses Professional           │
│                                                             │
│  3. EMAIL VERIFICATION                                      │
│     └─→ Verify email sebelum lanjut                         │
│                                                             │
│  4. SETUP WIZARD (Post-registration)                        │
│     └─→ /onboarding                                         │
│         Step 1: Business settings (logo, tax, timezone)     │
│         Step 2: Tambah produk pertama                       │
│         Step 3: Setup payment methods                       │
│         Step 4: Invite staff (optional, skip-able)          │
│                                                             │
│  5. DASHBOARD + CHECKLIST                                   │
│     └─→ /dashboard                                          │
│         • Welcome banner dengan trial countdown             │
│         • Setup checklist (progress bar)                    │
│         • Quick actions                                     │
│         • Upgrade CTA                                       │
│                                                             │
│  6. TRIAL REMINDER                                          │
│     └─→ Email reminder                                      │
│         • H-7: "Trial Anda tersisa 7 hari"                  │
│         • H-3: "Trial Anda tersisa 3 hari"                  │
│         • H-1: "Trial Anda berakhir besok"                  │
│         • H+1: "Trial Anda sudah berakhir, pilih plan"      │
│                                                             │
│  7. CHOOSE PLAN (setelah trial/frozen)                      │
│     └─→ /subscription/plans                                 │
│         • Tampilkan 4 tier                                  │
│         • User bebas pilih tier mana saja                   │
│         • Bayar → langsung aktif                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Payment & Billing Rules

```
┌─────────────────────────────────────────────────────────────┐
│                    PAYMENT RULES                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  PAYMENT GATEWAY: Xendit                                    │
│  ───────────────────────                                    │
│  • Virtual Account (Bank Transfer)                          │
│  • QRIS                                                      │
│  • Credit/Debit Card                                        │
│                                                              │
│  PAYMENT FLOW:                                               │
│  ─────────────                                              │
│  1. User pilih plan → Create invoice (Xendit)               │
│  2. User bayar via VA/QRIS/CC                               │
│  3. ✅ Success → Aktif langsung                              │
│  4. ❌ Expired/Timeout → Tidak terjadi apa-apa              │
│     └─→ User buat order baru jika mau                       │
│                                                              │
│  SIMPLE RULES:                                               │
│  ─────────────                                              │
│  • Bayar = Aktif                                            │
│  • Tidak bayar = Freeze                                     │
│  • Tidak ada retry otomatis                                 │
│  • Tidak ada dunning emails                                 │
│  • Tidak ada pending state                                  │
│                                                              │
│  REFUND POLICY:                                              │
│  ──────────────                                             │
│  • ❌ TIDAK ADA REFUND                                       │
│  • Sudah ada trial 14 hari gratis                           │
│  • Tidak cocok? Tinggal tidak perpanjang                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Upgrade & Downgrade Rules

```
┌─────────────────────────────────────────────────────────────┐
│                 UPGRADE (Mid-Cycle)                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Contoh: Growth → Professional di tengah bulan              │
│                                                              │
│  PRORATION:                                                  │
│  ──────────                                                 │
│  Growth Rp 299K, sudah pakai 15 hari (sisa 15 hari)         │
│  Pro Rp 599K                                                 │
│                                                              │
│  Sisa nilai Growth = Rp 299K × (15/30) = Rp 149.500         │
│  Harga Pro 30 hari = Rp 599K                                 │
│  Bayar = Rp 599K - Rp 149.500 = Rp 449.500                  │
│                                                              │
│  Periode Pro dimulai dari hari upgrade (30 hari baru)       │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                 DOWNGRADE                                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  TIDAK ADA DOWNGRADE LANGSUNG                               │
│                                                              │
│  Flow:                                                       │
│  1. User cancel subscription saat ini                       │
│  2. Tetap aktif sampai periode habis                        │
│  3. Periode habis → Freeze (setelah 1 hari grace)           │
│  4. User subscribe ulang dengan tier yang diinginkan        │
│                                                              │
│  Contoh: Pro → Growth                                        │
│  ─────────────────────                                      │
│  1. Cancel Pro (masih aktif sampai akhir bulan)             │
│  2. Bulan depan: Freeze                                     │
│  3. Subscribe Growth Rp 299K                                │
│  4. Bayar → Aktif di Growth                                 │
│                                                              │
│  ⚠️  Fitur Pro yang tidak ada di Growth akan di-lock        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. Strategi Monetisasi

### Revenue Streams

```
┌─────────────────────────────────────────────────────────────────┐
│                    REVENUE STREAMS                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  SUBSCRIPTION ONLY (100%)                                        │
│  ────────────────────────                                        │
│  • Monthly atau Yearly subscription per tier                     │
│  • Payment via Xendit (VA, QRIS, Cards)                          │
│  • Tidak ada add-on / à la carte                                 │
│  • Butuh lebih? Upgrade ke tier berikutnya                       │
│                                                                  │
│  MODEL: FULL SELF-SERVICE                                        │
│  ─────────────────────────                                       │
│  • Tidak ada onboarding berbayar                                 │
│  • Tidak ada training berbayar                                   │
│  • User belajar mandiri via dokumentasi & video tutorial         │
│  • Support via email/ticket (semua tier)                         │
│  • Training & dedicated support = Enterprise only                │
│                                                                  │
│  TIDAK ADA INTEGRASI:                                            │
│  ────────────────────                                            │
│  • Payment gateway untuk transaksi POS (pencatatan saja)         │
│  • SMS notifications                                             │
│  • WhatsApp integration                                          │
│  • Food delivery (GoFood, GrabFood, dll)                         │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```


### Pricing Psychology

1. **Anchor Pricing**: Tampilkan Enterprise dulu untuk membuat Professional terlihat reasonable
2. **Popular Badge**: Tandai Growth sebagai "Most Popular"
3. **Free Trial**: 14 hari trial Growth tier untuk conversion
4. **Annual Discount**: Push yearly dengan diskon signifikan (20%)
5. **No Hidden Fees**: Transparan tentang add-on costs

### Competitive Positioning

```
                    PRICE
                      ↑
                     │
          Rp 799K    │     ┌─────────┐
                     │     │ Moka    │
                     │     │ Ent     │
                     │     └─────────┘
          Rp 599K    │                    ┌─────────────┐
                     │                    │ Ultimate    │
                     │                    │ Professional│◄── Fitur = Moka Ent
                     │                    └─────────────┘
          Rp 499K    │     ┌─────────┐
                     │     │ Moka    │
                     │     │ Pro     │
                     │     └─────────┘
          Rp 299K    │     ┌─────────┐    ┌─────────────┐
                     │     │ Moka    │    │ Ultimate    │
                     │     │ Basic   │    │ Growth      │◄── Fitur > Moka Basic
                     │     └─────────┘    └─────────────┘
                     │
          Rp 99K     │                    ┌─────────────┐
                     │                    │ Ultimate    │
                     │                    │ Starter     │◄── Entry terendah!
                     │                    └─────────────┘
                     └─────────────────────────────────────→
                                   FEATURES              High

Positioning: "FITUR PREMIUM, HARGA UMKM"
```

---

## 7. Technical Decisions

### Session Management (Per Role)

```
┌─────────────────────────────────────────────────────────────┐
│                 SESSION RULES BY ROLE                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SINGLE SESSION (1 device per user)                         │
│  ──────────────────────────────────                         │
│  • Owner, Manager, Cashier, Admin                           │
│  • Login baru = kick device lama                            │
│  • Alasan: Handle uang & data sensitif                      │
│                                                              │
│  MULTI SESSION (unlimited device)                           │
│  ────────────────────────────────                           │
│  • Waiter, Kitchen                                          │
│  • Bisa login di beberapa device bersamaan                  │
│  • Alasan: Fleksibel, hanya input order / lihat display     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Offline Mode + Subscription Expire

```
┌─────────────────────────────────────────────────────────────┐
│              OFFLINE + SUBSCRIPTION EXPIRE                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SCENARIO:                                                   │
│  • POS App sedang offline                                   │
│  • Subscription expire di server                            │
│                                                              │
│  FLOW:                                                       │
│  1. User tetap bisa transaksi offline ✅                     │
│  2. User close shift → App sync ke server                   │
│  3. Server terima semua transaksi ✅                         │
│  4. Server response: "subscription_expired: true"           │
│  5. App FREEZE setelah shift closed                         │
│  6. Tidak bisa open shift baru sampai bayar                 │
│                                                              │
│  ALASAN:                                                     │
│  • User tidak kehilangan data                               │
│  • Shift bisa close dengan benar                            │
│  • Freeze di titik "clean" (antar shift)                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Feature Flag Refresh

```
┌─────────────────────────────────────────────────────────────┐
│              FEATURE FLAG REFRESH                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SAAT UPGRADE:                                               │
│  • Fitur baru langsung available tanpa re-login             │
│  • Auto-refresh via API call                                │
│                                                              │
│  IMPLEMENTASI:                                               │
│  • Backend: Cache feature flags, invalidate on upgrade      │
│  • Frontend: Poll subscription status setiap X menit        │
│  • Mobile: Refresh saat app resume / sync                   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### API Rate Limiting

```
┌─────────────────────────────────────────────────────────────┐
│              API RATE LIMITING                               │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  STANDARD RATE LIMIT (Anti-Bruteforce):                     │
│  ──────────────────────────────────────                     │
│  • Login attempts: 5 req/minute per IP                      │
│  • API general: 60 req/minute per user                      │
│  • Burst: 10 req/second max                                 │
│                                                              │
│  RESPONSE SAAT LIMIT:                                        │
│  • HTTP 429 Too Many Requests                               │
│  • Header: Retry-After: X seconds                           │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Session & Timezone Rules

```
┌─────────────────────────────────────────────────────────────┐
│              SESSION RULES                                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SESSION TIMEOUT: Tidak ada                                 │
│  ─────────────────────────────                              │
│  • Session aktif sampai logout manual                       │
│  • Atau sampai login dari device lain (untuk single-session)│
│                                                              │
│  ALASAN:                                                     │
│  • POS dipakai seharian, tidak praktis re-login terus       │
│  • Shift bisa lebih dari 8 jam                              │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              TIMEZONE RULES                                  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  TRIAL EXPIRE: Berdasarkan timezone tenant                  │
│  ─────────────────────────────────────────                  │
│  • Default: Asia/Jakarta                                    │
│  • Trial expire jam 23:59:59 di timezone tenant             │
│  • Grace period 1 hari juga berdasarkan timezone tenant     │
│                                                              │
│  CONTOH:                                                     │
│  • Register 1 Mar 2026 10:00 WIB                            │
│  • Trial expire 15 Mar 2026 23:59:59 WIB                    │
│  • Grace expire 16 Mar 2026 23:59:59 WIB                    │
│  • Freeze mulai 17 Mar 2026 00:00:00 WIB                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Downgrade & Data Limit Handling

```
┌─────────────────────────────────────────────────────────────┐
│              DOWNGRADE WITH EXCESS DATA                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SCENARIO:                                                   │
│  User Pro (unlimited) punya 600 produk                      │
│  Downgrade ke Growth (max 500 produk)                       │
│                                                              │
│  BEHAVIOR:                                                   │
│  ─────────                                                  │
│  • Downgrade ALLOWED                                        │
│  • Sistem archive produk, sisakan 500 PERTAMA (by created)  │
│  • 500 produk terlama tetap aktif                           │
│  • 100 produk terbaru di-archive (soft delete / hidden)     │
│  • User tidak bisa tambah produk baru                       │
│  • Upgrade lagi → archived products restored                │
│                                                              │
│  SAME LOGIC APPLIES TO:                                      │
│  ───────────────────────                                    │
│  • Outlets (archive outlet terbaru)                         │
│  • Users (deactivate user terbaru)                          │
│                                                              │
│  NOTE:                                                       │
│  • Data TIDAK dihapus, hanya di-archive                     │
│  • User diberi warning sebelum downgrade                    │
│  • List item yang akan di-archive ditampilkan               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Email Verification

```
┌─────────────────────────────────────────────────────────────┐
│              EMAIL VERIFICATION                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  LINK VALIDITY: 24 jam                                      │
│  ─────────────────────                                      │
│  • Link expired → user bisa request ulang                   │
│                                                              │
│  UNVERIFIED BEHAVIOR: Block akses                           │
│  ────────────────────────────────                           │
│  • User TIDAK bisa akses sistem sampai verify email         │
│  • Tampilkan halaman "Verifikasi email Anda"                │
│  • Tombol "Kirim ulang email verifikasi"                    │
│                                                              │
│  FLOW:                                                       │
│  ─────                                                      │
│  1. Register → redirect ke halaman "Check your email"       │
│  2. User klik link di email                                 │
│  3. Email verified → redirect ke onboarding/dashboard       │
│  4. Jika tidak verify → tidak bisa masuk                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Offline Sync & Concurrent Users

```
┌─────────────────────────────────────────────────────────────┐
│              OFFLINE SYNC                                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SYNC LIMIT: Unlimited                                      │
│  ─────────────────────                                      │
│  • Semua transaksi offline di-sync sekaligus                │
│  • Server handle batch processing                           │
│  • Progress indicator di app saat sync banyak data          │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              CONCURRENT USERS PER OUTLET                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  LIMIT: Unlimited (sesuai plan)                             │
│  ──────────────────────────────                             │
│  • Tidak ada limit per outlet                               │
│  • Total user dibatasi oleh plan (3/10/25/unlimited)        │
│  • Semua user bisa login bersamaan di outlet yang sama      │
│                                                              │
│  CONTOH:                                                     │
│  • Growth plan: 10 users, 2 outlets                         │
│  • Bisa 10 user login di outlet A bersamaan                 │
│  • Atau 5 di outlet A, 5 di outlet B                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Security & Validation Rules

```
┌─────────────────────────────────────────────────────────────┐
│              DISPOSABLE EMAIL BLOCKING                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  APPROACH: Free library/package                             │
│  ──────────────────────────────                             │
│  • Gunakan package seperti:                                 │
│    - Laravel: "martijnc/disposable-email-detector"          │
│    - atau "evilfreelancer/disposable-email-domains"         │
│  • Auto-update list disposable domains                      │
│  • Reject saat registrasi jika disposable                   │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              PASSWORD REQUIREMENTS                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  MINIMUM: 8 karakter                                        │
│  ─────────────────────                                      │
│  • Tidak ada requirement khusus (huruf besar, angka, dll)   │
│  • Simple dan user-friendly                                 │
│  • Validasi: min:8                                          │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              FAILED LOGIN HANDLING                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  APPROACH: Rate limit only, no account lock                 │
│  ──────────────────────────────────────────                 │
│  • 5 login attempts per minute per IP                       │
│  • Setelah limit → HTTP 429, coba lagi nanti                │
│  • Tidak ada permanent/temporary account lock               │
│  • Alasan: Lebih simple, cukup untuk anti-bruteforce        │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              DATA EXPORT FORMAT                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  FORMAT: Excel (.xlsx) only                                 │
│  ──────────────────────────                                 │
│  • Semua export dalam format Excel                          │
│  • Familiar untuk user Indonesia                            │
│  • Bisa langsung buka di Excel/Google Sheets                │
│                                                              │
│  EXPORT AVAILABLE FOR:                                       │
│  ─────────────────────                                      │
│  • Transaksi / Sales                                        │
│  • Produk                                                   │
│  • Pelanggan                                                │
│  • Inventory / Stock                                        │
│  • Laporan shift                                            │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Payment Webhook Handling

```
┌─────────────────────────────────────────────────────────────┐
│              XENDIT WEBHOOK FAILURE                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  APPROACH: Manual check                                     │
│  ──────────────────────                                     │
│  • Jika webhook gagal (server down), Xendit tidak retry     │
│  • User lapor: "Saya sudah bayar tapi belum aktif"          │
│  • Admin check di Xendit dashboard                          │
│  • Manual activate subscription                             │
│                                                              │
│  KENAPA:                                                     │
│  • Simple, jarang terjadi                                   │
│  • Tidak perlu complex retry logic                          │
│  • Xendit dashboard sudah reliable                          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Sync Conflict Resolution

```
┌─────────────────────────────────────────────────────────────┐
│              SYNC CONFLICT HANDLING                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  APPROACH: User choose (conflict alert)                     │
│  ──────────────────────────────────────                     │
│                                                              │
│  SCENARIO:                                                   │
│  • Produk "Nasi Goreng" diedit di Web (harga 20K)           │
│  • Produk sama diedit di POS offline (harga 25K)            │
│  • POS sync ke server                                       │
│                                                              │
│  FLOW:                                                       │
│  1. Server detect conflict (updated_at berbeda)             │
│  2. Return conflict response ke app                         │
│  3. App tampilkan: "Conflict detected"                      │
│     - Server version: Rp 20.000 (edited by Admin)           │
│     - Local version: Rp 25.000 (edited by You)              │
│  4. User pilih: [Pakai Server] [Pakai Local] [Merge Manual] │
│                                                              │
│  NOTE:                                                       │
│  • Conflict hanya untuk master data (produk, kategori, dll) │
│  • Transaksi tidak conflict (selalu append)                 │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Transaction Number Format

```
┌─────────────────────────────────────────────────────────────┐
│              RECEIPT/INVOICE NUMBER FORMAT                   │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  FORMAT: {OUTLET_CODE}-{YYYYMMDD}-{SEQ}                     │
│  ──────────────────────────────────────                     │
│                                                              │
│  EXAMPLE:                                                    │
│  • OUT1-20260301-001                                        │
│  • OUT1-20260301-002                                        │
│  • OUT2-20260301-001 (outlet berbeda, reset)                │
│  • OUT1-20260302-001 (hari berbeda, reset)                  │
│                                                              │
│  RULES:                                                      │
│  • Sequence reset setiap hari per outlet                    │
│  • 3 digit sequence (001-999)                               │
│  • Jika > 999 transaksi/hari → 4 digit (0001)               │
│  • Outlet code dari kode outlet (user defined)              │
│                                                              │
│  BENEFITS:                                                   │
│  • Mudah trace transaksi per outlet per hari                │
│  • Tidak bentrok antar outlet                               │
│  • Readable dan meaningful                                  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Audit Log Retention

```
┌─────────────────────────────────────────────────────────────┐
│              AUDIT LOG RETENTION                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  RETENTION: 90 hari                                         │
│  ──────────────────                                         │
│                                                              │
│  LOGGED ACTIVITIES:                                          │
│  ──────────────────                                         │
│  • Login/logout                                             │
│  • Create/update/delete master data                         │
│  • Void/refund transaksi                                    │
│  • Manager authorization                                    │
│  • Settings changes                                         │
│  • User management                                          │
│                                                              │
│  CLEANUP:                                                    │
│  ────────                                                   │
│  • Cron job daily: hapus log > 90 hari                      │
│  • Reduce storage cost                                      │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. Tech Stack & Architecture

### Tech Stack

```
┌─────────────────────────────────────────────────────────────┐
│              TECH STACK                                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  BACKEND:                                                    │
│  ────────                                                   │
│  • PHP 8.3                                                  │
│  • Laravel 12                                               │
│  • MySQL (database)                                         │
│  • Redis (cache & queue)                                    │
│                                                              │
│  FRONTEND WEB:                                               │
│  ─────────────                                              │
│  • Blade templates                                          │
│  • Alpine.js                                                │
│  • Tailwind CSS v4                                          │
│                                                              │
│  MOBILE:                                                     │
│  ───────                                                    │
│  • Flutter (POS App & Waiter App)                           │
│  • SQLite (local database)                                  │
│  • Offline-first architecture                               │
│                                                              │
│  INFRASTRUCTURE:                                             │
│  ───────────────                                            │
│  • Hosting: Biznet Gio Cloud                                │
│  • Domain: ultimatepos.com                                  │
│  • SSL: Let's Encrypt                                       │
│  • Backup: Auto SQL backup daily                            │
│                                                              │
│  SERVICES:                                                   │
│  ─────────                                                  │
│  • Payment Gateway: Xendit                                  │
│  • Email: SMTP (Laravel Mail)                               │
│  • Error Monitoring: Laravel Log (file-based)               │
│                                                              │
│  TESTING:                                                    │
│  ────────                                                   │
│  • PHPUnit (unit & feature tests)                           │
│  • Laravel Dusk (E2E browser testing)                       │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Queue & Background Jobs

```
┌─────────────────────────────────────────────────────────────┐
│              QUEUE CONFIGURATION                             │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  DRIVER: Redis                                              │
│  ─────────────                                              │
│  • Fast, reliable                                           │
│  • Support delayed jobs                                     │
│  • Easy monitoring                                          │
│                                                              │
│  QUEUES:                                                     │
│  ───────                                                    │
│  • default - general jobs                                   │
│  • emails - email sending                                   │
│  • sync - mobile sync processing                            │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              BACKGROUND JOBS                                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  EMAIL JOBS:                                                 │
│  ───────────                                                │
│  • SendWelcomeEmail                                         │
│  • SendEmailVerification                                    │
│  • SendTrialReminderEmail (H-7, H-3, H-1)                   │
│  • SendTrialExpiredEmail                                    │
│  • SendPaymentSuccessEmail                                  │
│  • SendDataDeletionWarningEmail (H-30, H-7)                 │
│                                                              │
│  SCHEDULED JOBS (via Laravel Scheduler):                    │
│  ───────────────────────────────────────                    │
│  • CheckExpiredTrials - daily 00:05                         │
│  • CheckExpiredSubscriptions - daily 00:10                  │
│  • FreezeExpiredAccounts - daily 00:15                      │
│  • SendTrialReminders - daily 09:00                         │
│  • CleanupAuditLogs - daily 02:00 (hapus > 90 hari)         │
│  • DeleteFrozenAccounts - daily 02:30 (> 1 tahun)           │
│                                                              │
│  SYNC JOBS:                                                  │
│  ──────────                                                 │
│  • ProcessOfflineTransactions                               │
│  • HandleSyncConflict                                       │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Caching Strategy

```
┌─────────────────────────────────────────────────────────────┐
│              CACHING STRATEGY                                │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  DRIVER: Redis                                              │
│                                                              │
│  PER-REQUEST CACHE (TTL: 5 menit):                          │
│  ─────────────────────────────────                          │
│  • tenant:{id}:settings                                     │
│  • user:{id}:permissions                                    │
│  • tenant:{id}:features (subscription plan)                 │
│                                                              │
│  LONGER CACHE (TTL: 1 jam):                                 │
│  ──────────────────────────                                 │
│  • tenant:{id}:outlet:{id}:products                         │
│  • tenant:{id}:categories                                   │
│  • tenant:{id}:payment_methods                              │
│                                                              │
│  CACHE INVALIDATION:                                         │
│  ────────────────────                                       │
│  • Event-based (on model update)                            │
│  • Clear specific keys, not all                             │
│  • Subscription upgrade → clear feature cache               │
│  • Product update → clear product cache                     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### API Versioning

```
┌─────────────────────────────────────────────────────────────┐
│              API VERSIONING                                  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  STRATEGY: Support 2 versions                               │
│  ────────────────────────────                               │
│  • Current version: /api/v1/*                               │
│  • Previous version: supported for 6 months                 │
│  • After 6 months: deprecated, return warning               │
│  • After 9 months: removed                                  │
│                                                              │
│  VERSIONING RULES:                                           │
│  ─────────────────                                          │
│  • Minor changes: backward compatible, no new version       │
│  • Breaking changes: new version (v2, v3, etc)              │
│  • Document all breaking changes in changelog               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Mobile App Force Update

```
┌─────────────────────────────────────────────────────────────┐
│              FORCE UPDATE MECHANISM                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  APPROACH: Block app until update                           │
│  ───────────────────────────────                            │
│                                                              │
│  FLOW:                                                       │
│  1. App sends current version to server on startup          │
│  2. Server checks minimum required version                  │
│  3. If app version < min version:                           │
│     → Return force_update: true                             │
│     → App shows "Update Required" screen                    │
│     → Block all features until update                       │
│     → Link to Play Store / App Store                        │
│                                                              │
│  USE CASES:                                                  │
│  ──────────                                                 │
│  • Critical security fix                                    │
│  • Breaking API change                                      │
│  • Major bug fix                                            │
│                                                              │
│  API ENDPOINT:                                               │
│  ─────────────                                              │
│  GET /api/v1/app/version-check                              │
│  Request: { "version": "1.0.0", "platform": "android" }     │
│  Response: { "force_update": true, "min_version": "1.1.0" } │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### File Storage

```
┌─────────────────────────────────────────────────────────────┐
│              FILE STORAGE                                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  STORAGE: Local (server disk)                               │
│  ────────────────────────────                               │
│                                                              │
│  LOCATION:                                                   │
│  ─────────                                                  │
│  • storage/app/public/                                      │
│  • Symlink: public/storage → storage/app/public             │
│                                                              │
│  STRUCTURE:                                                  │
│  ──────────                                                 │
│  storage/app/public/                                        │
│  ├── tenants/{tenant_id}/                                   │
│  │   ├── logo.png                                           │
│  │   ├── products/                                          │
│  │   │   ├── {product_id}.jpg                               │
│  │   ├── receipts/                                          │
│  │   │   ├── logo.png                                       │
│                                                              │
│  FILE TYPES:                                                 │
│  ───────────                                                │
│  • Images: jpg, jpeg, png, webp                             │
│  • Max size: 2MB                                            │
│  • Auto resize/compress on upload                           │
│                                                              │
│  BACKUP:                                                     │
│  ───────                                                    │
│  • Included in daily backup                                 │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Deployment Strategy

```
┌─────────────────────────────────────────────────────────────┐
│              DEPLOYMENT                                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  APPROACH: Manual via SSH                                   │
│  ────────────────────────                                   │
│                                                              │
│  DEPLOYMENT STEPS:                                           │
│  ─────────────────                                          │
│  1. SSH to server                                           │
│  2. cd /var/www/ultimatepos                                 │
│  3. php artisan down                                        │
│  4. git pull origin main                                    │
│  5. composer install --no-dev                               │
│  6. php artisan migrate --force                             │
│  7. php artisan config:cache                                │
│  8. php artisan route:cache                                 │
│  9. php artisan view:cache                                  │
│  10. php artisan queue:restart                              │
│  11. php artisan up                                         │
│                                                              │
│  ROLLBACK:                                                   │
│  ─────────                                                  │
│  • git revert atau git checkout                             │
│  • php artisan migrate:rollback jika perlu                  │
│                                                              │
│  FUTURE (jika scale):                                        │
│  ─────────────────────                                      │
│  • CI/CD pipeline (GitHub Actions)                          │
│  • Zero-downtime deployment                                 │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Environment & Development Workflow

```
┌─────────────────────────────────────────────────────────────┐
│              ENVIRONMENTS                                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  2 ENVIRONMENTS:                                            │
│  ───────────────                                            │
│  • Local (development)                                      │
│  • Production                                               │
│                                                              │
│  NO STAGING:                                                 │
│  ───────────                                                │
│  • Solo founder, keep simple                                │
│  • Test thoroughly di local sebelum deploy                  │
│  • Jika perlu staging nanti, bisa tambah                    │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              DEVELOPMENT WORKFLOW                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  APPROACH: Direct push to main                              │
│  ─────────────────────────────                              │
│                                                              │
│  WORKFLOW:                                                   │
│  ─────────                                                  │
│  1. Code di local                                           │
│  2. Test di local (unit + feature tests)                    │
│  3. git add + commit                                        │
│  4. git push origin main                                    │
│  5. SSH to server + deploy                                  │
│                                                              │
│  WHY:                                                        │
│  ────                                                       │
│  • Solo founder = no PR review needed                       │
│  • Faster iteration                                         │
│  • AI-assisted = code quality maintained                    │
│                                                              │
│  SAFETY:                                                     │
│  ───────                                                    │
│  • Run tests before push                                    │
│  • Small, incremental commits                               │
│  • Easy rollback via git revert                             │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Error Monitoring & Backup

```
┌─────────────────────────────────────────────────────────────┐
│              ERROR MONITORING                                │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  APPROACH: Manual log check                                 │
│  ──────────────────────────                                 │
│                                                              │
│  LOG LOCATION:                                               │
│  ─────────────                                              │
│  • storage/logs/laravel.log                                 │
│  • Daily rotation (laravel-2026-03-01.log)                  │
│                                                              │
│  MONITORING:                                                 │
│  ───────────                                                │
│  • Check log file secara berkala                            │
│  • Grep for "ERROR" atau "CRITICAL"                         │
│  • tail -f untuk live monitoring saat deploy               │
│                                                              │
│  FUTURE (jika perlu):                                        │
│  ─────────────────────                                      │
│  • Add Sentry/Bugsnag                                       │
│  • Email/Telegram notification                              │
│                                                              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              BACKUP STRATEGY                                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  DATABASE BACKUP:                                            │
│  ────────────────                                           │
│  • Auto backup SQL setiap tengah malam                      │
│  • Handled by Biznet Gio Cloud                              │
│                                                              │
│  VERIFICATION:                                               │
│  ─────────────                                              │
│  • Trust backup berjalan                                    │
│  • Test restore hanya jika diperlukan                       │
│  • Check backup file exists secara berkala                  │
│                                                              │
│  FILE BACKUP:                                                │
│  ────────────                                               │
│  • storage/app/public/ included in backup                   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Trial Abuse Prevention

```
┌─────────────────────────────────────────────────────────────┐
│              TRIAL ABUSE PREVENTION                          │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  EMAIL VERIFICATION:                                         │
│  • Wajib verify email sebelum bisa pakai sistem             │
│  • 1 email = 1 trial (email unique di database)             │
│  • Block disposable email domains (mailinator, etc)         │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Revenue & Margin

```
┌─────────────────────────────────────────────────────────────┐
│              NET REVENUE (setelah Xendit fee)                │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Xendit fee ditanggung oleh kita (bukan user)               │
│                                                              │
│  VA Fee: ~Rp 4.500 (flat)                                   │
│  ─────────────────────────────────────────────────────────  │
│  │ Tier         │ Harga     │ Fee    │ Net Revenue │        │
│  │──────────────│───────────│────────│─────────────│        │
│  │ Starter      │ Rp 99K    │ 4.5K   │ Rp 94.500   │        │
│  │ Growth       │ Rp 299K   │ 4.5K   │ Rp 294.500  │        │
│  │ Professional │ Rp 599K   │ 4.5K   │ Rp 594.500  │        │
│  │ Enterprise   │ Rp 1.499K │ 4.5K   │ Rp 1.494.500│        │
│  ─────────────────────────────────────────────────────────  │
│                                                              │
│  QRIS Fee: 0.7%                                              │
│  ─────────────────────────────────────────────────────────  │
│  │ Tier         │ Harga     │ Fee    │ Net Revenue │        │
│  │──────────────│───────────│────────│─────────────│        │
│  │ Starter      │ Rp 99K    │ 693    │ Rp 98.307   │        │
│  │ Growth       │ Rp 299K   │ 2.093  │ Rp 296.907  │        │
│  │ Professional │ Rp 599K   │ 4.193  │ Rp 594.807  │        │
│  │ Enterprise   │ Rp 1.499K │ 10.493 │ Rp 1.488.507│        │
│  ─────────────────────────────────────────────────────────  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Support Model

```
┌─────────────────────────────────────────────────────────────┐
│              SUPPORT MODEL BY TIER                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  STARTER / GROWTH / PROFESSIONAL (Self-Service):            │
│  ───────────────────────────────────────────────            │
│  • Ticket system (email-based)                              │
│  • Knowledge base (FAQ, how-to articles)                    │
│  • Video tutorials                                          │
│  • Response time: 1-2 hari kerja                            │
│                                                              │
│  ENTERPRISE (Dedicated):                                     │
│  ────────────────────────                                   │
│  • Semua di atas +                                          │
│  • Dedicated WhatsApp                                       │
│  • Response time: < 2 jam                                   │
│  • Dedicated account manager                                │
│  • Onboarding & training session                            │
│  • Quarterly business review                                │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. Go-To-Market Strategy

### Target Segment

```
┌─────────────────────────────────────────────────────────────┐
│              TARGET MARKET - PHASE 1                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SEGMENT: F&B Kecil                                         │
│  ─────────────────────                                      │
│  • Cafe                                                      │
│  • Warteg                                                    │
│  • Warung makan                                             │
│  • Kedai kopi                                               │
│  • Food court tenant                                        │
│                                                              │
│  KENAPA SEGMENT INI:                                         │
│  ───────────────────                                        │
│  • Volume besar (jutaan UMKM F&B di Indonesia)              │
│  • Entry barrier rendah (butuh solusi murah)                │
│  • Pain point jelas (catat manual, tidak ada laporan)       │
│  • Word-of-mouth kuat di komunitas                          │
│  • Cocok dengan tier Starter (Rp 99K) & Growth (Rp 299K)    │
│                                                              │
│  LATER PHASES:                                               │
│  • Phase 2: Resto menengah (upgrade dari kompetitor)        │
│  • Phase 3: Retail (toko, minimarket)                       │
│  • Phase 4: Enterprise (franchise, chain)                   │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Acquisition Channel

```
┌─────────────────────────────────────────────────────────────┐
│              ACQUISITION - ORGANIC FIRST                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  PRIMARY: Organic / Content Marketing                       │
│  ────────────────────────────────────                       │
│  • SEO: "aplikasi kasir gratis", "POS murah", etc           │
│  • Blog: Tips bisnis F&B, tutorial                          │
│  • YouTube: Demo produk, testimonial                        │
│  • Social media: Instagram, TikTok (behind the scenes)      │
│                                                              │
│  SECONDARY: Referral (setelah ada user base)                │
│  ───────────────────────────────────────────                │
│  • 1 bulan gratis per referral sukses                       │
│  • Program affiliate untuk influencer F&B                   │
│                                                              │
│  WHY ORGANIC:                                                │
│  ────────────                                               │
│  • Low cost, sustainable                                    │
│  • Build trust & authority                                  │
│  • Compound effect over time                                │
│  • Target market aktif di social media                      │
│                                                              │
│  LATER (jika ada budget):                                    │
│  • Google Ads untuk high-intent keywords                    │
│  • Meta Ads untuk awareness                                 │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Launch Timeline

```
┌─────────────────────────────────────────────────────────────┐
│              LAUNCH TIMELINE                                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  TARGET LAUNCH: MARET 2026                                  │
│                                                              │
│  TIMELINE:                                                   │
│  ──────────                                                 │
│  Feb 2026 (now)                                             │
│  ├── Finalisasi pricing & business model ✅                 │
│  ├── Development subscription system                        │
│  ├── Development onboarding flow                            │
│  └── Prepare knowledge base & tutorials                     │
│                                                              │
│  Mar 2026 (Week 1-2)                                        │
│  ├── Soft launch (limited beta users)                       │
│  ├── Bug fixing & feedback                                  │
│  └── Content preparation (blog, video)                      │
│                                                              │
│  Mar 2026 (Week 3-4)                                        │
│  ├── Public launch                                          │
│  ├── Publish content & SEO                                  │
│  └── Monitor & iterate                                      │
│                                                              │
│  DEVELOPMENT APPROACH:                                       │
│  ─────────────────────                                      │
│  • AI-assisted development untuk speed                      │
│  • Focus on MVP features first                              │
│  • Iterate based on user feedback                           │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Success Metrics

```
┌─────────────────────────────────────────────────────────────┐
│              SUCCESS METRICS                                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  PRIMARY METRIC: Trial → Paid Conversion Rate               │
│  ────────────────────────────────────────────               │
│  • Benchmark industri SaaS: 10-25%                          │
│  • Target awal: 15%                                         │
│  • Artinya: 100 trial → 15 paying customers                 │
│                                                              │
│  SECONDARY METRICS (track, bukan focus):                    │
│  ───────────────────────────────────────                    │
│  • Trial signups per bulan                                  │
│  • MRR (Monthly Recurring Revenue)                          │
│  • Churn rate                                               │
│  • ARPU (Average Revenue Per User)                          │
│                                                              │
│  EVALUASI: Setelah 3 bulan, review & adjust target          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Team & Operations

```
┌─────────────────────────────────────────────────────────────┐
│              SOLO FOUNDER OPERATIONS                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  SEMUA DI-HANDLE SENDIRI:                                   │
│  ────────────────────────                                   │
│  • Development (dibantu AI)                                 │
│  • Support ticket                                           │
│  • Bug fixing                                               │
│  • Content/SEO                                              │
│  • Finance/invoice                                          │
│                                                              │
│  STRATEGI EFISIENSI:                                         │
│  ───────────────────                                        │
│  • Self-service harus sangat bagus                          │
│  • Knowledge base & FAQ lengkap                             │
│  • Automation sebanyak mungkin                              │
│  • Fokus tier Starter & Growth dulu                         │
│  • Enterprise nanti jika ada demand & resource              │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Contingency Plan

```
┌─────────────────────────────────────────────────────────────┐
│              CONTINGENCY: MVP LAUNCH                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Jika Maret 2026 tidak 100% ready:                          │
│  → Soft launch dengan fitur terbatas                        │
│                                                              │
│  MUST HAVE (wajib ready untuk launch):                      │
│  ─────────────────────────────────────                      │
│  ✅ POS Core (transaksi, payment, receipt)                  │
│  ✅ Subscription system & Xendit payment                    │
│  ✅ Trial 14 hari + freeze mechanism                        │
│  ✅ Basic onboarding                                        │
│  ✅ Pricing page                                            │
│  ✅ Email verification                                      │
│                                                              │
│  NICE TO HAVE (bisa menyusul):                              │
│  ─────────────────────────────                              │
│  ⏳ Email reminders (H-7, H-3, H-1)                         │
│  ⏳ Advanced reports & export                               │
│  ⏳ Video tutorials lengkap                                 │
│  ⏳ Knowledge base lengkap                                  │
│  ⏳ Referral program                                        │
│                                                              │
│  APPROACH:                                                   │
│  ─────────                                                  │
│  • Launch dengan core, iterate berdasarkan feedback         │
│  • "Coming Soon" untuk fitur yang belum ready               │
│  • Transparently communicate roadmap                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Branding & Infrastructure

```
┌─────────────────────────────────────────────────────────────┐
│              BRANDING & INFRASTRUCTURE                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  BRANDING:                                                   │
│  ─────────                                                  │
│  • Nama: Ultimate POS                                       │
│  • Domain: ultimatepos.com                                  │
│  • Tagline: "Fitur Premium, Harga UMKM"                     │
│                                                              │
│  HOSTING:                                                    │
│  ────────                                                   │
│  • Provider: Biznet Gio Cloud                               │
│  • Location: Indonesia (low latency)                        │
│                                                              │
│  BACKUP:                                                     │
│  ───────                                                    │
│  • Auto backup SQL setiap tengah malam                      │
│  • Retention: sesuai kebijakan hosting                      │
│                                                              │
│  LEGAL:                                                      │
│  ──────                                                     │
│  • Terms & Conditions: /terms                               │
│  • Privacy Policy: /privacy                                 │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 9. Implementasi Teknis

### Database Schema (Update Required)

```sql
-- subscription_plans (UPDATE)
CREATE TABLE subscription_plans (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),           -- Starter, Growth, Professional, Enterprise
    slug VARCHAR(255) UNIQUE,
    description TEXT,
    price_monthly DECIMAL(12,2),
    price_yearly DECIMAL(12,2),
    max_outlets INT DEFAULT 1,   -- -1 = unlimited
    max_users INT DEFAULT 3,     -- -1 = unlimited
    max_products INT DEFAULT 100, -- -1 = unlimited
    features JSON,               -- Feature flags
    add_on_outlet_price DECIMAL(12,2), -- Harga tambahan outlet
    add_on_device_price DECIMAL(12,2), -- Harga tambahan waiter device
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT
);
-- Note: max_transactions DIHAPUS - semua tier unlimited transaksi
```

### Feature Flags (JSON Structure)

```json
{
  "starter": {
    "pos_core": true,
    "cash_drawer": true,
    "held_order": true,
    "multi_payment": true,
    "split_bill": true,
    "product_variants": false,
    "product_combos": false,
    "modifiers": false,
    "discounts": false,
    "table_management": false,
    "inventory_basic": false,
    "inventory_advanced": false,
    "recipe_bom": false,
    "stock_transfer": false,
    "manager_auth": false,
    "waiter_app": false,
    "waiter_devices": 0,
    "qr_order": false,
    "kds": false,
    "api_access": false,
    "custom_branding": false,
    "loyalty_points": false,
    "advanced_reports": false,
    "export_data": false,
    "dedicated_support": false
  },
  "growth": {
    "pos_core": true,
    "cash_drawer": true,
    "held_order": true,
    "multi_payment": true,
    "split_bill": true,
    "product_variants": true,
    "product_combos": true,
    "modifiers": true,
    "discounts": true,
    "table_management": true,
    "inventory_basic": true,
    "inventory_advanced": false,
    "recipe_bom": false,
    "stock_transfer": false,
    "manager_auth": false,
    "waiter_app": false,
    "waiter_devices": 0,
    "qr_order": false,
    "kds": false,
    "api_access": false,
    "custom_branding": false,
    "loyalty_points": true,
    "advanced_reports": true,
    "export_data": true,
    "dedicated_support": false
  },
  "professional": {
    "pos_core": true,
    "cash_drawer": true,
    "held_order": true,
    "multi_payment": true,
    "split_bill": true,
    "product_variants": true,
    "product_combos": true,
    "modifiers": true,
    "discounts": true,
    "table_management": true,
    "inventory_basic": true,
    "inventory_advanced": true,
    "recipe_bom": true,
    "stock_transfer": true,
    "manager_auth": true,
    "waiter_app": true,
    "waiter_devices": 1,
    "qr_order": true,
    "kds": false,
    "api_access": false,
    "custom_branding": false,
    "loyalty_points": true,
    "advanced_reports": true,
    "export_data": true,
    "dedicated_support": false
  },
  "enterprise": {
    "pos_core": true,
    "cash_drawer": true,
    "held_order": true,
    "multi_payment": true,
    "split_bill": true,
    "product_variants": true,
    "product_combos": true,
    "modifiers": true,
    "discounts": true,
    "table_management": true,
    "inventory_basic": true,
    "inventory_advanced": true,
    "recipe_bom": true,
    "stock_transfer": true,
    "manager_auth": true,
    "waiter_app": true,
    "waiter_devices": -1,
    "qr_order": true,
    "kds": true,
    "api_access": true,
    "custom_branding": true,
    "loyalty_points": true,
    "advanced_reports": true,
    "export_data": true,
    "dedicated_support": true
  }
}
```

### POS Core Features (Semua Tier)

```
┌─────────────────────────────────────────────────────────────┐
│              POS CORE - INCLUDED IN ALL TIERS                │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Fitur berikut tersedia di SEMUA tier (termasuk Starter):   │
│                                                              │
│  ✅ Transaksi penjualan                                     │
│  ✅ Cash drawer management                                  │
│  ✅ Held order (simpan order sementara)                     │
│  ✅ Multi-payment method                                    │
│  ✅ Split bill                                              │
│  ✅ Shift/session management                                │
│  ✅ Receipt printing                                        │
│  ✅ Transaction history                                     │
│  ✅ Customer database                                       │
│  ✅ Basic daily sales report                                │
│                                                              │
│  Note: QRIS di payment method adalah MANUAL                 │
│  (user catat sebagai metode pembayaran, bukan integrasi)    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Inventory Feature Matrix

```
┌─────────────────────────────────────────────────────────────┐
│              INVENTORY FEATURES BY TIER                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  STARTER (Rp 99K) - NO INVENTORY                            │
│  ───────────────────────────────                            │
│  ❌ Tidak ada inventory sama sekali                         │
│  → Target: Warung kecil yang jual menu simple               │
│                                                              │
│  GROWTH (Rp 299K) - INVENTORY BASIC                         │
│  ──────────────────────────────────                         │
│  ✅ Stock tracking (jumlah stok)                            │
│  ✅ Low stock alert                                         │
│  ✅ Stock opname / adjustment sederhana                     │
│  ❌ Batch tracking                                          │
│  ❌ Expiry date tracking                                    │
│  ❌ Purchase Order                                          │
│  ❌ Goods Receive                                           │
│  ❌ Stock transfer antar outlet                             │
│  ❌ Recipe/BOM                                              │
│  ❌ Waste logging                                           │
│  ❌ Supplier management                                     │
│  → Target: Cafe yang perlu tahu stok habis atau tidak       │
│                                                              │
│  PROFESSIONAL (Rp 599K) - INVENTORY ADVANCED                │
│  ───────────────────────────────────────────                │
│  ✅ Semua fitur Basic +                                     │
│  ✅ Batch tracking                                          │
│  ✅ Expiry date tracking                                    │
│  ✅ Purchase Order (PO)                                     │
│  ✅ Goods Receive (GR)                                      │
│  ✅ Stock adjustment dengan approval                        │
│  ✅ Stock transfer antar outlet                             │
│  ✅ Recipe/BOM (auto deduct bahan)                          │
│  ✅ Waste logging                                           │
│  ✅ Supplier management                                     │
│  → Target: Resto serius yang perlu kontrol food cost        │
│                                                              │
│  ENTERPRISE (Rp 1.499K) - SAME AS PROFESSIONAL              │
│  ─────────────────────────────────────────────              │
│  ✅ Sama dengan Professional                                │
│                                                              │
└─────────────────────────────────────────────────────────────┘

SUMMARY TABLE:
┌────────────────────────┬─────────┬────────┬─────┬────────────┐
│ Fitur Inventory        │ Starter │ Growth │ Pro │ Enterprise │
├────────────────────────┼─────────┼────────┼─────┼────────────┤
│ Stock tracking         │ ❌      │ ✅     │ ✅  │ ✅         │
│ Low stock alert        │ ❌      │ ✅     │ ✅  │ ✅         │
│ Stock adjustment       │ ❌      │ ✅     │ ✅  │ ✅         │
│ Batch tracking         │ ❌      │ ❌     │ ✅  │ ✅         │
│ Expiry date tracking   │ ❌      │ ❌     │ ✅  │ ✅         │
│ Purchase Order (PO)    │ ❌      │ ❌     │ ✅  │ ✅         │
│ Goods Receive (GR)     │ ❌      │ ❌     │ ✅  │ ✅         │
│ Stock transfer         │ ❌      │ ❌     │ ✅  │ ✅         │
│ Recipe/BOM             │ ❌      │ ❌     │ ✅  │ ✅         │
│ Waste logging          │ ❌      │ ❌     │ ✅  │ ✅         │
│ Supplier management    │ ❌      │ ❌     │ ✅  │ ✅         │
└────────────────────────┴─────────┴────────┴─────┴────────────┘
```

### Middleware untuk Feature Gating

```php
// app/Http/Middleware/CheckSubscriptionFeature.php

public function handle($request, Closure $next, $feature)
{
    $tenant = $request->user()->tenant;
    $subscription = $tenant->activeSubscription;

    if (!$subscription) {
        return response()->json([
            'success' => false,
            'message' => 'No active subscription'
        ], 403);
    }

    if (!$subscription->hasFeature($feature)) {
        return response()->json([
            'success' => false,
            'message' => 'This feature requires plan upgrade',
            'required_plan' => $subscription->getMinimumPlanFor($feature)
        ], 403);
    }

    return $next($request);
}

// Usage in routes:
Route::middleware(['auth:sanctum', 'feature:recipe_bom'])
    ->get('/recipes', [RecipeController::class, 'index']);
```

---

## Summary

### Rekomendasi Final

| Aspek | Keputusan |
|-------|-----------|
| **Model** | Hybrid (Per Tenant base + Per Outlet scaling) |
| **Tiers** | 4 tier (Starter, Growth, Professional, Enterprise) |
| **Entry Price** | Rp 99.000/bulan (JAUH lebih murah dari kompetitor) |
| **Sweet Spot** | Growth @ Rp 299.000 (best value for SMB) |
| **Premium** | Professional @ Rp 599.000 (full features) |
| **Diferensiasi** | Recipe/BOM, Manager Auth, Waiter App, Offline Mode |
| **Upsell** | Add-on outlets, devices |
| **Billing** | Monthly + Yearly (20% discount) |

### Kenapa Harga Ini?

1. **Starter Rp 99K** - Entry point psikologis "di bawah 100rb", 67% lebih murah dari Moka Basic
2. **Growth Rp 299K** - Sama dengan Moka Basic, tapi fitur setara Moka Pro
3. **Professional Rp 599K** - 25% lebih murah dari Moka Enterprise dengan fitur lengkap
4. **Enterprise Rp 1.499K** - Premium pricing untuk fitur exclusive + dedicated support

### Positioning: "Fitur Premium, Harga UMKM"

```
vs Moka POS:
├── Starter Rp 99K   vs Moka Basic Rp 299K    = -67% 🔥
├── Growth Rp 299K   vs Moka Pro Rp 499K      = -40%
├── Pro Rp 599K      vs Moka Ent Rp 799K      = -25%
└── Enterprise       = Premium segment
```

---

## 10. Gap Analysis & Enhancement Required

### Apa yang Sudah Ada

| Component | Status | Catatan |
|-----------|--------|---------|
| `subscription_plans` table | ✅ Ada | Perlu tambah kolom |
| `subscriptions` table | ✅ Ada | OK |
| `subscription_invoices` table | ✅ Ada | OK |
| `SubscriptionPlan` model | ✅ Ada | Perlu tambah method |
| `Subscription` model | ✅ Ada | OK |
| `EnsureActiveSubscription` middleware | ✅ Ada | Cek subscription aktif |
| `SubscriptionController` | ✅ Ada | Subscribe, renew, cancel |
| `XenditService` | ✅ Ada | Payment gateway |
| `Tenant.hasFeature()` | ✅ Ada | Tapi logic salah |

### Apa yang BELUM Ada / Perlu Enhancement

---

### PHASE 0: Trial & Onboarding System (BELUM ADA)

#### 0.1 Public Pricing Page

```
/pricing
├── Comparison table 4 tier
├── Feature matrix
├── FAQ
└── CTA "Coba Gratis 14 Hari" → /register
```

#### 0.2 Update Registration Flow

```php
// RegisterController.php - update
// Saat register:
// - Set status = 'trial'
// - Set trial_ends_at = now()->addDays(14)
// - Set plan = 'professional' (full access during trial)
```

#### 0.3 Email Verification

```php
// Implementasi MustVerifyEmail
// User harus verify email sebelum bisa akses dashboard
```

#### 0.4 Post-Registration Setup Wizard

```
/onboarding
├── Step 1: Business settings (logo, tax, timezone)
├── Step 2: First product
├── Step 3: Payment methods
└── Step 4: Invite staff (optional)
```

#### 0.5 Dashboard Onboarding Checklist

```php
// Komponen di dashboard:
// - Trial countdown banner
// - Setup progress checklist
// - Upgrade CTA
```

#### 0.6 Trial Reminder Emails (Scheduled Jobs)

```php
// Cron jobs:
// - SendTrialReminderH7 (7 hari sebelum expire)
// - SendTrialReminderH3 (3 hari sebelum expire)
// - SendTrialReminderH1 (1 hari sebelum expire)
// - SendTrialExpiredNotice (setelah expire)
```

#### 0.7 Freeze Mode Middleware

```php
// app/Http/Middleware/CheckNotFrozen.php
// Block semua write operations jika frozen
// Allow: login, view data, export, upgrade
// Block: create transaction, add product, etc.
```

---

### PHASE 1: Database & Model Enhancement

#### 1.1 Migration: Tambah kolom di `subscription_plans`

```php
// Kolom baru yang diperlukan:
$table->integer('max_products')->default(100);      // Limit produk
$table->integer('max_waiter_devices')->default(0); // Limit waiter device
// Note: TIDAK ada max_transactions - semua tier unlimited transaksi
```

#### 1.2 Update `SubscriptionPlanSeeder`

Sesuaikan dengan 4 tier baru:
- Starter: Rp 99K, 1 outlet, 3 user, 100 produk
- Growth: Rp 299K, 2 outlet, 10 user, 500 produk
- Professional: Rp 599K, 5 outlet, 25 user, unlimited
- Enterprise: Rp 1.499K, unlimited semua

#### 1.3 Update `features` JSON Structure

```json
{
  "pos_core": true,
  "product_variants": false,
  "product_combos": false,
  "modifiers": false,
  "table_management": false,
  "inventory_basic": false,
  "inventory_advanced": false,
  "recipe_bom": false,
  "stock_transfer": false,
  "manager_auth": false,
  "waiter_app": false,
  "qr_order": false,
  "kds": false,
  "api_access": false,
  "custom_branding": false,
  "loyalty_points": false,
  "advanced_reports": false,
  "export_data": false,
  "dedicated_support": false,
  "training": false,
  "onboarding": false
}
```

---

### PHASE 2: Feature Gating & Menu Visibility

#### 2.1 Buat `CheckFeature` Middleware

```php
// app/Http/Middleware/CheckFeature.php
// Cek apakah tenant punya akses ke fitur tertentu
Route::middleware(['feature:inventory_advanced'])->group(...)
```

#### 2.2 Feature Gate di Controller/Routes

| Fitur | Route Group | Gate |
|-------|-------------|------|
| Inventory Basic | `/inventory/*` | `inventory_basic` |
| Recipe/BOM | `/inventory/recipes/*` | `recipe_bom` |
| Stock Transfer | `/inventory/transfers/*` | `stock_transfer` |
| Table Management | `/tables/*`, `/floors/*` | `table_management` |
| Product Variants | Create variant | `product_variants` |
| Product Combos | `/menu/combos/*` | `product_combos` |
| Modifiers | `/menu/modifiers/*` | `modifiers` |
| Manager Auth | `/pos/authorization/*` | `manager_auth` |
| Advanced Reports | Export, detailed reports | `advanced_reports` |

#### 2.3 Menu Visibility di Sidebar (PENTING!)

Saat ini semua menu tampil untuk semua user. Perlu ditambahkan **plan-based menu visibility**.

**Cara Implementasi:**

```php
// app/Helpers/PlanHelper.php atau di Tenant model
public function canAccess(string $feature): bool
{
    $plan = $this->activeSubscription?->plan;
    if (!$plan) return false;

    $features = $plan->features ?? [];
    return $features[$feature] ?? false;
}
```

**Update Sidebar (`resources/views/partials/sidebar.blade.php`):**

```blade
{{-- Contoh: Inventory Section hanya tampil jika punya fitur inventory --}}
@if(auth()->user()->tenant?->canAccess('inventory_basic'))
<!-- Inventory Section -->
<div class="mb-2">
    ...
</div>
@endif

{{-- Contoh: Recipe menu hanya tampil jika punya fitur recipe_bom --}}
@if(auth()->user()->tenant?->canAccess('recipe_bom'))
<a href="{{ route('inventory.recipes.index') }}">...</a>
@endif
```

#### 2.4 Mapping Menu ke Feature Flag

| Menu Section | Feature Flag | Starter | Growth | Professional | Enterprise |
|--------------|--------------|---------|--------|--------------|------------|
| **Dashboard** | - | ✅ | ✅ | ✅ | ✅ |
| **POS** | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Buka POS | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Sesi Kasir | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Riwayat Transaksi | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Pelanggan | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| **Pricing** | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Harga | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Diskon | `loyalty_points` | ❌ | ✅ | ✅ | ✅ |
| - Metode Pembayaran | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| **Menu** | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Produk | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Kategori Menu | `pos_core` | ✅ | ✅ | ✅ | ✅ |
| - Variant Groups | `product_variants` | ❌ | ✅ | ✅ | ✅ |
| - Modifier Groups | `modifiers` | ❌ | ✅ | ✅ | ✅ |
| - Combos | `product_combos` | ❌ | ✅ | ✅ | ✅ |
| **Inventory** | `inventory_basic` | ❌ | ✅ | ✅ | ✅ |
| - Items | `inventory_basic` | ❌ | ✅ | ✅ | ✅ |
| - Units | `inventory_basic` | ❌ | ✅ | ✅ | ✅ |
| - Kategori | `inventory_basic` | ❌ | ✅ | ✅ | ✅ |
| - Stock | `inventory_basic` | ❌ | ✅ | ✅ | ✅ |
| - Suppliers | `inventory_advanced` | ❌ | ❌ | ✅ | ✅ |
| - Purchase Orders | `inventory_advanced` | ❌ | ❌ | ✅ | ✅ |
| - Goods Receive | `inventory_advanced` | ❌ | ❌ | ✅ | ✅ |
| - Stock Adjustments | `inventory_advanced` | ❌ | ❌ | ✅ | ✅ |
| - Stock Transfers | `stock_transfer` | ❌ | ❌ | ✅ | ✅ |
| - Recipes | `recipe_bom` | ❌ | ❌ | ✅ | ✅ |
| - Waste Logs | `inventory_advanced` | ❌ | ❌ | ✅ | ✅ |
| **Reports** | `advanced_reports` | ❌ | ✅ | ✅ | ✅ |
| **Admin** | - | ✅ | ✅ | ✅ | ✅ |
| - Outlets | - | ✅ | ✅ | ✅ | ✅ |
| - Users | - | ✅ | ✅ | ✅ | ✅ |
| - Roles | - | ✅ | ✅ | ✅ | ✅ |
| - Authorization Settings | `manager_auth` | ❌ | ❌ | ✅ | ✅ |
| - Subscription | - | ✅ | ✅ | ✅ | ✅ |

#### 2.5 Tampilkan Upgrade Prompt

Ketika user mencoba akses fitur yang tidak ada di plan-nya:

```blade
{{-- Contoh: Tampilkan menu dengan lock icon dan upgrade prompt --}}
@if(auth()->user()->tenant?->canAccess('inventory_basic'))
    <a href="{{ route('inventory.items.index') }}">Inventory</a>
@else
    <a href="#" @click="showUpgradeModal('inventory')" class="opacity-50">
        <span>Inventory</span>
        <svg class="w-4 h-4 ml-2"><!-- lock icon --></svg>
    </a>
@endif
```

**Atau hide completely (simpler):**

```blade
@if(auth()->user()->tenant?->canAccess('inventory_basic'))
    <!-- Inventory Section -->
@endif
```

---

### PHASE 3: Limit Enforcement

#### 3.1 Outlet Limit (Sudah ada, perlu review)

```php
// Tenant.php - sudah ada canAddOutlet()
// Perlu enforce di OutletController@store
```

#### 3.2 User Limit (Sudah ada, perlu review)

```php
// Tenant.php - sudah ada canAddUser()
// Perlu enforce di UserController@store
```

#### 3.3 Product Limit (BELUM ADA)

```php
// Perlu buat di Tenant.php
public function canAddProduct(): bool
{
    $limit = $this->activeSubscription?->plan?->max_products ?? 100;
    if ($limit === -1) return true;
    return $this->products()->count() < $limit;
}
```

#### 3.4 Trial & Freeze Check (BELUM ADA)

```php
// Perlu buat di Tenant.php
public function isTrialExpired(): bool
{
    $subscription = $this->activeSubscription;
    if (!$subscription) return true;

    if ($subscription->status === 'trial') {
        return $subscription->trial_ends_at < now();
    }

    return false;
}

public function isFrozen(): bool
{
    $subscription = $this->activeSubscription;
    return !$subscription ||
           $subscription->status === 'expired' ||
           $subscription->status === 'frozen';
}

public function canCreateTransaction(): bool
{
    return !$this->isFrozen();
}
```

---

### PHASE 4: UI Enhancement

#### 4.1 Pricing Page (Public)

- [ ] Landing page dengan pricing table
- [ ] Comparison table antar tier
- [ ] CTA button ke signup/upgrade

#### 4.2 Subscription Dashboard (Tenant)

- [ ] Current plan info
- [ ] Usage stats (outlet, user, product, transaction)
- [ ] Progress bar untuk limit
- [ ] Upgrade button
- [ ] Invoice history

#### 4.3 Upgrade Prompt

- [ ] Modal ketika hit limit
- [ ] Banner ketika mendekati limit (80%)
- [ ] Feature lock screen dengan upgrade CTA

#### 4.4 Self-Service Documentation

- [ ] Knowledge base / Help center
- [ ] Video tutorial per fitur
- [ ] In-app onboarding walkthrough
- [ ] FAQ page

---

### PHASE 5: Backend Services

#### 5.1 Usage Tracking Service

```php
// app/Services/UsageTrackingService.php
class UsageTrackingService
{
    public function getUsageStats(Tenant $tenant): array
    {
        return [
            'outlets' => ['used' => X, 'limit' => Y],
            'users' => ['used' => X, 'limit' => Y],
            'products' => ['used' => X, 'limit' => Y],
            'transactions_this_month' => ['used' => X, 'limit' => Y],
        ];
    }
}
```

#### 5.2 Subscription Lifecycle Service

```php
// app/Services/SubscriptionService.php
- handleUpgrade()
- handleDowngrade()
- handleExpiry()
- sendExpiryReminder()
- sendLimitWarning()
```

#### 5.3 Scheduled Jobs

```php
// Cron jobs yang diperlukan:
- CheckExpiredSubscriptions (daily)
- SendExpiryReminders (daily, 7 hari sebelum expire)
- SendUsageLimitWarnings (daily, ketika > 80% limit)
- ResetMonthlyTransactionCount (monthly)
```

---

### PHASE 6: API Enhancement

#### 6.1 Subscription API untuk Mobile

```
GET  /api/v1/subscription/current
GET  /api/v1/subscription/usage
GET  /api/v1/subscription/features
POST /api/v1/subscription/check-feature
```

#### 6.2 Feature Check di Mobile App

```dart
// Flutter - cek fitur sebelum tampilkan menu
if (subscription.hasFeature('table_management')) {
  showTableManagementMenu();
}
```

---

## Summary: Enhancement Checklist

### PHASE 0: Trial & Onboarding (CRITICAL)

- [x] **Pricing Page**: Public page dengan tier comparison & CTA ✅
- [x] **Registration**: Update flow dengan trial status (14 hari, akses Pro) ✅
- [x] **Email Verification**: Implementasi MustVerifyEmail ✅
- [x] **Setup Wizard**: Post-registration onboarding steps ✅
- [x] **Trial Banner**: Countdown di dashboard ✅
- [x] **Trial Emails**: Reminder H-7, H-3, H-1, H+1 ✅
- [x] **Choose Plan Page**: Setelah trial/frozen, user pilih tier ✅
- [x] **Freeze Middleware**: Block write operations jika frozen ✅
- [x] **Grace Period**: 1 hari sebelum freeze ✅

### PHASE 1: Database & Model (Must Have)

- [x] **Migration**: Tambah kolom `max_products` di `subscription_plans` ✅
- [x] **Seeder**: Update dengan 4 tier baru (Rp 99K, 299K, 599K, 1.499K) ✅
- [x] **Model**: Update `features` JSON structure di seeder ✅
- [x] **Subscription Status**: Add trial, active, expired, frozen states ✅
- [ ] **Proration Logic**: Hitung pro-rata saat upgrade mid-cycle

### PHASE 2: Feature Gating (Must Have)

- [x] **Middleware**: Buat `CheckFeature` middleware ✅
- [x] **Sidebar**: Menu visibility berdasarkan plan ✅
- [x] **Routes**: Apply feature middleware ke route groups ✅
- [x] **Upgrade Prompt**: Modal ketika akses fitur locked ✅

### PHASE 3: Limit Enforcement (Must Have)

- [x] **Tenant**: Tambah `canAddProduct()`, `isFrozen()`, `isTrialExpired()` ✅
- [x] **Controller**: Enforce limit di Outlet, User, Product create ✅
- [x] **POS**: Block transaction jika frozen ✅

### PHASE 4: Billing & Payment (Must Have)

- [x] **Xendit Integration**: VA, QRIS, CC payment ✅
- [x] **Invoice Management**: Create, track, expire ✅
- [x] **Upgrade Flow**: Proration calculation & payment ✅
- [x] **Cancel Flow**: Mark for non-renewal, freeze after period ends ✅

### PHASE 5: Data Lifecycle (Nice to Have)

- [x] **Cron**: Check frozen accounts > 1 year ✅
- [x] **Warning Emails**: H-30, H-7 before data deletion ✅
- [x] **Data Deletion**: Permanent delete after 1 year frozen ✅
- [x] **Expiry Reminder**: Emails for subscription renewal ✅

### Future

- [ ] **Analytics**: Conversion tracking (trial → paid)
- [ ] **A/B Testing**: Pricing page optimization
- [ ] **Referral**: Referral program system

---

*Document Version: 1.2*
*Updated: February 2026*
*Author: AI Assistant + Bahri*
