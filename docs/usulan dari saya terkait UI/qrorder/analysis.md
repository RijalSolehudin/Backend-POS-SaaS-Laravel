# QR Self-Order Analysis

## Daftar Isi
1. [Overview](#1-overview)
2. [User Personas & Use Cases](#2-user-personas--use-cases)
3. [Customer Journey Flow](#3-customer-journey-flow)
4. [Technical Architecture](#4-technical-architecture)
5. [Database Schema Changes](#5-database-schema-changes)
6. [API Endpoints](#6-api-endpoints)
7. [Admin Dashboard Changes](#7-admin-dashboard-changes)
8. [Security Considerations](#8-security-considerations)
9. [Integration Points](#9-integration-points)
10. [UI/UX Prototype Specs](#10-uiux-prototype-specs)
11. [Feature Gating & Pricing](#11-feature-gating--pricing)
12. [Implementation Phases](#12-implementation-phases)

---

## 1. Overview

### Apa itu QR Self-Order?

QR Self-Order adalah fitur yang memungkinkan pelanggan restoran untuk:
- Scan QR code di meja
- Melihat menu digital
- Memesan makanan/minuman sendiri tanpa bantuan waiter
- Melakukan pembayaran (opsional)

### Value Proposition

| Stakeholder | Benefit |
|-------------|---------|
| **Pelanggan** | Tidak perlu menunggu waiter, pesan kapan saja, lihat foto menu jelas |
| **Restoran** | Kurangi beban waiter, tingkatkan table turnover, upselling otomatis |
| **Waiter** | Fokus pada layanan quality, bukan order taking |
| **Kitchen** | Order langsung masuk sistem, tidak ada salah dengar/tulis |

### Scope

**In Scope:**
- QR code generation per meja
- Menu browsing dengan kategori & filter
- Item detail dengan variant & modifier
- Add to cart & manage cart
- Submit order ke kitchen
- Order status tracking
- Basic customer info (nama, no meja)
- Integration dengan existing POS & KDS

**Out of Scope (Future Enhancement):**
- Payment via QR Order (fase berikutnya)
- Customer account & login
- Order history per customer
- Push notifications
- Multi-language support
- Loyalty points integration

---

## 2. User Personas & Use Cases

### Persona 1: Pelanggan Restoran (End User)

**Profile:**
- Pengunjung restoran dine-in
- Semua umur, familiar dengan smartphone
- Tidak perlu download app

**Use Cases:**
1. Scan QR → Lihat menu → Pilih item → Submit order
2. Lihat status order yang sudah disubmit
3. Tambah order baru (additional order)
4. Minta bill ke kasir (tetap bayar di kasir)

### Persona 2: Restaurant Manager

**Profile:**
- Mengelola operasional restoran
- Setup menu & QR codes

**Use Cases:**
1. Generate/regenerate QR code per meja
2. Print QR codes untuk meja
3. Enable/disable QR ordering per outlet
4. Set menu items yang tampil di QR Order
5. Monitor orders masuk dari QR
6. Manage order notifications

### Persona 3: Waiter/Staff

**Profile:**
- Melayani pelanggan
- Terima order dan konfirmasi

**Use Cases:**
1. Lihat order masuk dari QR
2. Konfirmasi/accept order
3. Update status order
4. Assist customer yang kesulitan

### Persona 4: Kitchen Staff

**Profile:**
- Menyiapkan makanan
- Lihat order dari KDS

**Use Cases:**
1. Terima order dari QR (sama seperti order manual)
2. Update status preparation
3. Mark item as ready

---

## 3. Customer Journey Flow

### Flow Diagram

```
┌────────────────────────────────────────────────────────────────────┐
│                        QR SELF-ORDER FLOW                          │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  CUSTOMER                     SYSTEM                    STAFF      │
│  ────────                     ──────                    ─────      │
│                                                                     │
│  1. Duduk di meja                                                  │
│     │                                                               │
│     ▼                                                               │
│  2. Scan QR Code ──────────▶ Validate QR                           │
│     │                        • Check outlet active                 │
│     │                        • Check table exists                  │
│     │                        • Check subscription tier              │
│     │                        │                                      │
│     │                        ▼                                      │
│  3. ◀───────────────────── Load Menu Page                          │
│     │                        • Get categories                      │
│     │                        • Get products                        │
│     │                        • Filter show_in_menu=true            │
│     │                                                               │
│     ▼                                                               │
│  4. Browse Menu                                                     │
│     │                                                               │
│     ▼                                                               │
│  5. Select Item ───────────▶ Show Item Detail                      │
│     │                        • Variants                            │
│     │                        • Modifiers                           │
│     │                        • Notes                               │
│     │                                                               │
│     ▼                                                               │
│  6. Add to Cart                                                     │
│     │                                                               │
│     ▼                                                               │
│  7. Review Cart                                                     │
│     │ (edit qty, remove)                                           │
│     │                                                               │
│     ▼                                                               │
│  8. Input Customer Info                                            │
│     │ • Nama (required)                                            │
│     │ • Notes (optional)                                           │
│     │                                                               │
│     ▼                                                               │
│  9. Submit Order ──────────▶ Create QR Order ─────────▶ Notify     │
│     │                        • Validate items                      │ Staff     │
│     │                        • Check stock                         │           │
│     │                        • Generate order number               │           │
│     │                                                               │
│     ▼                                                               │    │
│  10. Order Confirmation                                             │    ▼
│      │ (order number)                                              │ Accept/
│      │                                                              │ Reject
│      ▼                                                              │    │
│  11. Track Order Status ◀────────────────────────────────────────────────┘
│      • PENDING → CONFIRMED → PREPARING → READY → SERVED                 │
│                                                                          │
│  12. Enjoy food!                                                         │
│      │                                                                   │
│      ▼                                                                   │
│  13. Add more? ──▶ Back to step 4                                       │
│      │                                                                   │
│      ▼                                                                   │
│  14. Request Bill ─────────▶ Waiter brings bill                         │
│      │                       └─▶ Convert to Transaction                 │
│      ▼                                                                   │
│  15. Pay at Cashier                                                     │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

### Order Status Flow

```
┌─────────┐    Staff     ┌───────────┐    Kitchen    ┌───────────┐
│ PENDING │─────────────▶│ CONFIRMED │──────────────▶│ PREPARING │
└─────────┘   accepts    └───────────┘    starts     └───────────┘
     │                                                      │
     │ reject                                              │
     ▼                                                      ▼
┌──────────┐                                          ┌─────────┐
│ REJECTED │                                          │  READY  │
└──────────┘                                          └─────────┘
                                                            │
                                                            ▼
                                                      ┌─────────┐
                                                      │ SERVED  │
                                                      └─────────┘
```

### Per-Item Status (Kitchen Level)

```
┌─────────┐   ┌───────────┐   ┌─────────┐   ┌────────┐
│ QUEUED  │──▶│ PREPARING │──▶│  READY  │──▶│ SERVED │
└─────────┘   └───────────┘   └─────────┘   └────────┘
```

---

## 4. Technical Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                         QR ORDER SYSTEM                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│  ┌──────────────────┐     ┌──────────────────┐     ┌──────────────┐ │
│  │   QR Order PWA   │     │    Admin Web     │     │   POS App    │ │
│  │   (Customer)     │     │   (Dashboard)    │     │   (Cashier)  │ │
│  │                  │     │                  │     │              │ │
│  │  • Scan QR       │     │  • QR Management │     │  • Accept    │ │
│  │  • Browse Menu   │     │  • Order Monitor │     │    orders    │ │
│  │  • Order         │     │  • Settings      │     │  • Payment   │ │
│  │  • Track Status  │     │                  │     │              │ │
│  └────────┬─────────┘     └────────┬─────────┘     └──────┬───────┘ │
│           │                        │                       │        │
│           └────────────────────────┼───────────────────────┘        │
│                                    │                                 │
│                                    ▼                                 │
│  ┌─────────────────────────────────────────────────────────────────┐│
│  │                         REST API                                 ││
│  │                    (Laravel Backend)                             ││
│  │                                                                  ││
│  │  • /api/qr-order/*  (Customer endpoints - public)               ││
│  │  • /api/v2/qr-orders/*  (Staff endpoints - authenticated)       ││
│  │                                                                  ││
│  └─────────────────────────────────────────────────────────────────┘│
│                                    │                                 │
│                    ┌───────────────┼───────────────┐                │
│                    ▼               ▼               ▼                │
│              ┌──────────┐   ┌──────────┐   ┌──────────────┐        │
│              │  MySQL   │   │  Redis   │   │  WebSocket   │        │
│              │          │   │ (Cache/  │   │  (Pusher/    │        │
│              │  • Data  │   │  Queue)  │   │  Reverb)     │        │
│              │          │   │          │   │              │        │
│              └──────────┘   └──────────┘   └──────────────┘        │
│                                                                      │
└──────────────────────────────────────────────────────────────────────┘
```

### Tech Stack for QR Order PWA

| Component | Technology |
|-----------|------------|
| Frontend | Vue 3 + Vite (PWA) |
| Styling | Tailwind CSS v4 |
| State | Pinia |
| Icons | Heroicons |
| HTTP | Axios |
| Real-time | Laravel Echo + Pusher/Reverb |

### QR Code Strategy

**QR Code Content:**
```
https://{tenant-slug}.ultimatepos.id/qr/{outlet_code}/{table_code}

Example:
https://warung-bahari.ultimatepos.id/qr/WB01/T05
```

**QR Code Generation:**
- Library: `chillerlan/php-qrcode` atau `endroid/qr-code`
- Format: SVG (scalable) atau PNG
- Size: 200x200px default, configurable
- Error Correction: Medium (M)
- Include outlet logo di tengah (optional)

---

## 5. Database Schema Changes

### New Tables

#### `qr_orders` - Main QR Order Record

```sql
CREATE TABLE qr_orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    uuid VARCHAR(36) NOT NULL UNIQUE,          -- Public identifier

    -- Relations
    tenant_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    table_id BIGINT UNSIGNED NOT NULL,
    table_session_id BIGINT UNSIGNED NULL,     -- Linked when converted
    transaction_id BIGINT UNSIGNED NULL,       -- Linked when paid

    -- Customer Info
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NULL,
    customer_notes TEXT NULL,

    -- Order Info
    order_number VARCHAR(50) NOT NULL,         -- QR-{OUTLET}-{YYYYMMDD}-{SEQ}
    status ENUM('pending', 'confirmed', 'preparing', 'ready', 'served',
                'rejected', 'cancelled', 'converted') NOT NULL DEFAULT 'pending',
    rejection_reason TEXT NULL,

    -- Amounts
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    tax_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    service_charge_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    grand_total DECIMAL(15,2) NOT NULL DEFAULT 0,

    -- Staff Actions
    confirmed_by BIGINT UNSIGNED NULL,
    confirmed_at TIMESTAMP NULL,
    prepared_by BIGINT UNSIGNED NULL,
    prepared_at TIMESTAMP NULL,
    served_by BIGINT UNSIGNED NULL,
    served_at TIMESTAMP NULL,
    rejected_by BIGINT UNSIGNED NULL,
    rejected_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    converted_at TIMESTAMP NULL,

    -- Session tracking
    session_token VARCHAR(64) NOT NULL,        -- Browser session for add orders
    device_fingerprint VARCHAR(64) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (table_id) REFERENCES tables(id),
    FOREIGN KEY (table_session_id) REFERENCES table_sessions(id),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id),
    FOREIGN KEY (confirmed_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id),

    INDEX idx_outlet_status (outlet_id, status),
    INDEX idx_table_session (table_id, session_token),
    INDEX idx_order_number (order_number)
);
```

#### `qr_order_items` - Order Line Items

```sql
CREATE TABLE qr_order_items (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    qr_order_id BIGINT UNSIGNED NOT NULL,

    -- Product Info
    product_id BIGINT UNSIGNED NOT NULL,
    product_variant_id BIGINT UNSIGNED NULL,
    product_name VARCHAR(255) NOT NULL,        -- Snapshot
    variant_name VARCHAR(255) NULL,            -- Snapshot

    -- Quantity & Price
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    modifiers JSON NULL,                       -- [{id, name, price}]
    modifier_total DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,

    -- Notes
    item_notes TEXT NULL,

    -- Kitchen Status (per item)
    kitchen_status ENUM('queued', 'preparing', 'ready', 'served')
                   NOT NULL DEFAULT 'queued',
    kitchen_station_id BIGINT UNSIGNED NULL,
    prepared_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    served_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (qr_order_id) REFERENCES qr_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (product_variant_id) REFERENCES product_variants(id),
    FOREIGN KEY (kitchen_station_id) REFERENCES kitchen_stations(id)
);
```

#### `qr_codes` - QR Code Management

```sql
CREATE TABLE qr_codes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    outlet_id BIGINT UNSIGNED NOT NULL,
    table_id BIGINT UNSIGNED NOT NULL,

    -- QR Info
    code VARCHAR(20) NOT NULL,                 -- Unique code in URL
    url VARCHAR(500) NOT NULL,                 -- Full URL
    qr_image_path VARCHAR(500) NULL,           -- Stored QR image path

    -- Settings
    is_active BOOLEAN DEFAULT TRUE,
    valid_from TIMESTAMP NULL,
    valid_until TIMESTAMP NULL,

    -- Stats
    scan_count INT DEFAULT 0,
    last_scanned_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (outlet_id) REFERENCES outlets(id),
    FOREIGN KEY (table_id) REFERENCES tables(id),

    UNIQUE KEY unique_outlet_table (outlet_id, table_id),
    INDEX idx_code (code)
);
```

### Existing Table Modifications

#### `tables` - Add QR-related fields

```sql
ALTER TABLE tables
ADD COLUMN qr_code_id BIGINT UNSIGNED NULL AFTER position_height,
ADD COLUMN qr_ordering_enabled BOOLEAN DEFAULT TRUE AFTER qr_code_id,
ADD FOREIGN KEY (qr_code_id) REFERENCES qr_codes(id);
```

#### `outlets` - Add QR Order settings

```sql
ALTER TABLE outlets
ADD COLUMN qr_order_enabled BOOLEAN DEFAULT FALSE AFTER receipt_footer,
ADD COLUMN qr_order_auto_accept BOOLEAN DEFAULT FALSE AFTER qr_order_enabled,
ADD COLUMN qr_order_require_phone BOOLEAN DEFAULT FALSE AFTER qr_order_auto_accept,
ADD COLUMN qr_order_welcome_message TEXT NULL AFTER qr_order_require_phone,
ADD COLUMN qr_order_theme JSON NULL AFTER qr_order_welcome_message;
```

#### `products` - Already has `show_in_menu` flag

```sql
-- Sudah ada: show_in_menu BOOLEAN DEFAULT TRUE
-- Gunakan flag ini untuk filter produk yang tampil di QR Order
```

---

## 6. API Endpoints

### Public Endpoints (No Auth - QR Order PWA)

Base: `/api/qr-order`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/validate/{outlet_code}/{table_code}` | Validate QR code & get outlet info |
| GET | `/menu/{outlet_code}` | Get menu categories & products |
| GET | `/product/{outlet_code}/{product_id}` | Get product detail with variants & modifiers |
| POST | `/order` | Submit new order |
| GET | `/order/{uuid}` | Get order detail & status |
| GET | `/orders/{session_token}` | Get all orders for this session |
| POST | `/order/{uuid}/add-items` | Add items to existing order |
| POST | `/order/{uuid}/cancel` | Cancel pending order (before confirmed) |

### Staff Endpoints (Authenticated)

Base: `/api/v2/qr-orders`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | List QR orders (filterable by status) |
| GET | `/{id}` | Get order detail |
| POST | `/{id}/confirm` | Confirm pending order |
| POST | `/{id}/reject` | Reject order with reason |
| POST | `/{id}/preparing` | Mark as preparing |
| POST | `/{id}/ready` | Mark as ready |
| POST | `/{id}/served` | Mark as served |
| POST | `/{id}/convert` | Convert to transaction (for payment) |
| GET | `/stats` | Get QR order statistics |

### QR Code Management Endpoints

Base: `/api/v2/qr-codes`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | List all QR codes for outlet |
| POST | `/` | Generate QR code for table |
| POST | `/bulk-generate` | Generate for multiple tables |
| GET | `/{id}` | Get QR code detail |
| PUT | `/{id}` | Update QR code settings |
| DELETE | `/{id}` | Deactivate QR code |
| POST | `/{id}/regenerate` | Regenerate QR code (new URL) |
| GET | `/{id}/download` | Download QR code image |
| GET | `/print-all` | Get printable PDF of all QR codes |

---

## 7. Admin Dashboard Changes

### Menu Structure Changes

```
Dashboard
├── POS
├── Orders
│   ├── Transactions (existing)
│   ├── Held Orders (existing)
│   └── QR Orders (NEW) ────────────────────────┐
│       ├── Live Orders (real-time monitor)     │
│       ├── Order History                       │
│       └── Analytics                           │
├── Tables                                       │
│   ├── Floor Plan (existing)                   │
│   └── QR Codes (NEW) ─────────────────────────┤
│       ├── Manage QR Codes                     │
│       ├── Print QR Codes                      │
│       └── QR Settings                         │
├── Menu
│   └── Products (add: show_in_menu toggle) ────┤
├── Settings                                     │
│   └── QR Order Settings (NEW) ────────────────┘
│       ├── Enable/Disable QR Order
│       ├── Auto-accept orders
│       ├── Require phone number
│       ├── Welcome message
│       └── Theme customization
└── Reports
    └── QR Order Report (NEW)
```

### New Pages & Components

#### 1. QR Orders - Live Monitor (`/orders/qr-orders`)

**Purpose:** Real-time dashboard untuk monitor dan manage QR orders

**Layout:**
```
┌────────────────────────────────────────────────────────────────────┐
│  QR Orders                                            [Filter] [v] │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────┐  │
│  │  PENDING    │ │  CONFIRMED  │ │  PREPARING  │ │    READY    │  │
│  │     3       │ │      2      │ │      5      │ │      1      │  │
│  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────┘  │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ Pending Orders (3)                                           │  │
│  ├──────────────────────────────────────────────────────────────┤  │
│  │                                                               │  │
│  │  ┌─────────────────────────────────────┐  ┌─────────────────┐│  │
│  │  │ QR-WB01-20250219-001                │  │ QR-WB01-002     ││  │
│  │  │ Table: T05 - Outdoor                │  │ Table: T12      ││  │
│  │  │ Customer: Budi                      │  │ Customer: Ani   ││  │
│  │  │ Items: 4 items - Rp 125.000         │  │ 2 items - Rp 45K││  │
│  │  │ Time: 2 min ago                     │  │ 5 min ago       ││  │
│  │  │                                     │  │                 ││  │
│  │  │ [View] [Accept] [Reject]            │  │ [View] [Accept] ││  │
│  │  └─────────────────────────────────────┘  └─────────────────┘│  │
│  │                                                               │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │ In Progress (7)                                              │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
└────────────────────────────────────────────────────────────────────┘
```

**Features:**
- Real-time updates via WebSocket
- Sound notification for new orders
- Quick actions (accept/reject/mark ready)
- Filter by status, table, time range
- Order detail modal with items
- Bulk actions

#### 2. QR Codes Management (`/tables/qr-codes`)

**Purpose:** Generate dan manage QR codes untuk meja

**Layout:**
```
┌────────────────────────────────────────────────────────────────────┐
│  QR Code Management                    [Generate All] [Print All] │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Floor: [All Floors ▼]  Status: [All ▼]          Search: [______] │
│                                                                     │
│  ┌────────────────────────────────────────────────────────────────┐│
│  │ Floor 1 - Indoor (8 tables)                                    ││
│  ├────────────────────────────────────────────────────────────────┤│
│  │                                                                 ││
│  │  ┌───────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐      ││
│  │  │  ┌─────┐  │ │  ┌─────┐  │ │  ┌─────┐  │ │  ┌─────┐  │      ││
│  │  │  │ QR  │  │ │  │ QR  │  │ │  │ QR  │  │ │  │  ?  │  │      ││
│  │  │  │Code │  │ │  │Code │  │ │  │Code │  │ │  │     │  │      ││
│  │  │  └─────┘  │ │  └─────┘  │ │  └─────┘  │ │  └─────┘  │      ││
│  │  │   T01     │ │   T02     │ │   T03     │ │   T04     │      ││
│  │  │  Active   │ │  Active   │ │ Inactive  │ │ No QR     │      ││
│  │  │ 125 scans │ │  89 scans │ │   0 scans │ │           │      ││
│  │  │           │ │           │ │           │ │           │      ││
│  │  │[Download] │ │[Download] │ │[Activate] │ │[Generate] │      ││
│  │  │ [Edit]    │ │ [Edit]    │ │ [Edit]    │ │           │      ││
│  │  └───────────┘ └───────────┘ └───────────┘ └───────────┘      ││
│  │                                                                 ││
│  └────────────────────────────────────────────────────────────────┘│
│                                                                     │
│  ┌────────────────────────────────────────────────────────────────┐│
│  │ Floor 2 - Outdoor (4 tables)                                   ││
│  └────────────────────────────────────────────────────────────────┘│
│                                                                     │
└────────────────────────────────────────────────────────────────────┘
```

**Features:**
- Generate QR code per table
- Bulk generate for all tables
- Download individual QR as PNG/SVG
- Print all QR codes as PDF (dengan template: table name, logo)
- Enable/disable per table
- Scan statistics
- Regenerate (new URL) if needed

#### 3. QR Order Settings (`/settings/qr-order`)

**Layout:**
```
┌────────────────────────────────────────────────────────────────────┐
│  QR Order Settings                                        [Save]  │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  General                                                           │
│  ─────────────────────────────────────────────────────────────────│
│                                                                     │
│  [x] Enable QR Self-Order                                          │
│      Allow customers to order via QR code scan                     │
│                                                                     │
│  [ ] Auto-accept orders                                            │
│      Orders will be automatically confirmed                        │
│                                                                     │
│  [ ] Require phone number                                          │
│      Customer must enter phone number to order                     │
│                                                                     │
│  Welcome Message                                                    │
│  ┌────────────────────────────────────────────────────────────┐   │
│  │ Selamat datang di {outlet_name}!                           │   │
│  │ Silakan pilih menu favorit Anda.                           │   │
│  └────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  Theme & Appearance                                                │
│  ─────────────────────────────────────────────────────────────────│
│                                                                     │
│  Primary Color: [#3B82F6] [■]                                      │
│  Logo:          [Upload Logo]                                      │
│                                                                     │
│  Notifications                                                      │
│  ─────────────────────────────────────────────────────────────────│
│                                                                     │
│  [x] Play sound for new orders                                     │
│  [x] Show desktop notification                                     │
│  [ ] Send to Telegram bot                                          │
│                                                                     │
└────────────────────────────────────────────────────────────────────┘
```

#### 4. Product Edit - Menu Visibility Toggle

**Add to existing product form:**
```
┌────────────────────────────────────────────────────────────────────┐
│  Visibility                                                        │
│  ─────────────────────────────────────────────────────────────────│
│                                                                     │
│  [x] Show in POS                                                   │
│      Display this product in the POS app                           │
│                                                                     │
│  [x] Show in Menu (QR Order)    ← NEW                              │
│      Display this product in QR self-order menu                    │
│                                                                     │
│  [ ] Track Stock                                                   │
│      Enable stock tracking for this product                        │
│                                                                     │
└────────────────────────────────────────────────────────────────────┘
```

### Existing Page Modifications

#### 1. Floor Plan - Add QR Status Indicator

Show QR order status on table in floor plan:
- Badge indicator if table has pending QR orders
- Quick link to QR orders for that table

#### 2. POS - QR Order Integration

- Tab or notification for incoming QR orders
- Accept QR order → auto-create/append to table transaction
- Show QR order items differently (labeled "QR Order")

#### 3. Dashboard - QR Order Widget

New widget on dashboard:
- Today's QR orders count
- Revenue from QR orders
- Average order value
- Peak hours chart

---

## 8. Security Considerations

### Authentication & Authorization

| Endpoint Type | Auth Method | Rate Limit |
|---------------|-------------|------------|
| QR Order PWA (Public) | Session token + outlet validation | 100 req/min per IP |
| Staff Endpoints | Laravel Sanctum (Bearer token) | 1000 req/min |
| QR Code Management | Sanctum + permission check | 100 req/min |

### Input Validation

- Sanitize customer name (strip HTML/JS)
- Validate product IDs exist and belong to outlet
- Validate quantities (positive integers, max 99)
- Validate modifiers belong to product
- Rate limit order submissions per session (max 5 per 5 minutes)

### Session Security

```php
// Session token generation
$sessionToken = hash('sha256', Str::uuid() . $tableId . time());

// Store in qr_orders
// Validate on subsequent requests (add items, cancel)
```

### QR Code Security

- UUID-based order IDs (not sequential)
- Short-lived session tokens
- Outlet/table validation on every request
- Optional: QR code expiration date
- IP-based rate limiting

### Data Privacy

- Minimal customer data collection (name only required)
- No password/account creation
- Session data cleared after 24 hours
- GDPR-friendly: can delete on request

---

## 9. Integration Points

### 1. POS Integration

```
QR Order → Confirm → Convert to Transaction
                          ↓
                    TransactionService::create()
                          ↓
                    Same flow as manual POS order
```

**Conversion Process:**
1. Staff confirms QR order
2. Staff clicks "Convert to Transaction"
3. System creates Transaction with:
   - `order_type = 'QR_ORDER'`
   - `qr_order_id = {original QR order}`
   - All items copied
4. Normal payment flow at cashier

### 2. KDS Integration

```
QR Order Confirmed → Send to KDS
                          ↓
                    Kitchen sees order (same as POS order)
                          ↓
                    Kitchen updates item status
                          ↓
                    QR Order status updated via WebSocket
                          ↓
                    Customer sees status on phone
```

### 3. Table Management Integration

```
QR Order Created → Auto-create TableSession (if not exists)
                          ↓
                    Table status = OCCUPIED
                          ↓
                    Orders linked to TableSession
                          ↓
                    Payment → Close TableSession
                          ↓
                    Table status = DIRTY → AVAILABLE
```

### 4. Inventory Integration

```
QR Order Confirmed → Stock Reservation (optional)
                          ↓
                    Transaction Completed
                          ↓
                    Stock Deduction (via StockService)
```

### 5. Notification Integration

```
New QR Order → Broadcast Event
                    ↓
        ┌──────────┼──────────┐
        ↓          ↓          ↓
    WebSocket   Sound     Telegram
    (POS/Admin) Alert     Bot (opt)
```

---

## 10. UI/UX Prototype Specs

### QR Order PWA - Mobile-First Design

#### Screen 1: Landing (Post-Scan)

```
┌────────────────────────────────┐
│ ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │ ← Status bar
├────────────────────────────────┤
│                                │
│         [Outlet Logo]          │
│                                │
│      Warung Bahari Seafood     │
│         📍 Outlet Senayan      │
│                                │
│    ─────────────────────────   │
│                                │
│     Selamat datang! 👋         │
│     Anda di Meja T05           │
│                                │
│    ─────────────────────────   │
│                                │
│     Silakan pilih menu         │
│     favorit Anda               │
│                                │
│                                │
│  ┌────────────────────────┐    │
│  │                        │    │
│  │    Lihat Menu          │    │
│  │                        │    │
│  └────────────────────────┘    │
│                                │
│   Atau lihat pesanan aktif     │
│                                │
└────────────────────────────────┘
```

#### Screen 2: Menu - Category View

```
┌────────────────────────────────┐
│ ← Menu          🔍     🛒 (3)  │
├────────────────────────────────┤
│                                │
│ ┌──────────────────────────┐   │
│ │ 🔎 Cari menu...          │   │
│ └──────────────────────────┘   │
│                                │
│ ┌────┐ ┌────┐ ┌────┐ ┌────┐   │
│ │All │ │Food│ │Drnk│ │Dssr│   │
│ └────┘ └────┘ └────┘ └────┘   │
│  ────                          │
│                                │
│ Makanan Utama                  │
│ ─────────────────────────────  │
│                                │
│ ┌──────────────────────────┐   │
│ │ [IMG]  Nasi Goreng       │   │
│ │        Seafood           │   │
│ │        Rp 35.000    [+]  │   │
│ └──────────────────────────┘   │
│                                │
│ ┌──────────────────────────┐   │
│ │ [IMG]  Mie Goreng        │   │
│ │        Special           │   │
│ │        Rp 32.000    [+]  │   │
│ └──────────────────────────┘   │
│                                │
│ ┌──────────────────────────┐   │
│ │ [IMG]  Ikan Bakar        │   │
│ │        Jimbaran          │   │
│ │        Rp 75.000    [+]  │   │
│ └──────────────────────────┘   │
│                                │
│ Minuman                        │
│ ─────────────────────────────  │
│                                │
└────────────────────────────────┘
```

#### Screen 3: Product Detail

```
┌────────────────────────────────┐
│ ←                    🛒 (3)    │
├────────────────────────────────┤
│                                │
│ ┌──────────────────────────┐   │
│ │                          │   │
│ │       [Product Image]    │   │
│ │                          │   │
│ └──────────────────────────┘   │
│                                │
│ Nasi Goreng Seafood            │
│ Rp 35.000                      │
│                                │
│ Nasi goreng dengan udang,      │
│ cumi, dan sayuran segar        │
│                                │
│ ─────────────────────────────  │
│                                │
│ Pilih Variant *                │
│ ○ Regular           Rp 35.000  │
│ ● Jumbo             Rp 45.000  │
│                                │
│ ─────────────────────────────  │
│                                │
│ Tambahan (opsional)            │
│ ☐ Extra Udang       + Rp 10.000│
│ ☑ Extra Telur       + Rp 5.000 │
│ ☐ Sambal Matah      + Rp 3.000 │
│                                │
│ ─────────────────────────────  │
│                                │
│ Catatan untuk dapur            │
│ ┌──────────────────────────┐   │
│ │ Tidak pakai kecap...     │   │
│ └──────────────────────────┘   │
│                                │
├────────────────────────────────┤
│                                │
│  [-]    1    [+]               │
│                                │
│ ┌────────────────────────────┐ │
│ │ Tambah ke Keranjang        │ │
│ │      Rp 50.000             │ │
│ └────────────────────────────┘ │
│                                │
└────────────────────────────────┘
```

#### Screen 4: Cart

```
┌────────────────────────────────┐
│ ← Keranjang                    │
├────────────────────────────────┤
│                                │
│ Meja T05                       │
│                                │
│ ─────────────────────────────  │
│                                │
│ ┌──────────────────────────┐   │
│ │ Nasi Goreng Seafood      │   │
│ │ Jumbo                    │   │
│ │ + Extra Telur            │   │
│ │ "Tidak pakai kecap"      │   │
│ │                          │   │
│ │ [-] 1 [+]      Rp 50.000 │   │
│ │                    [🗑️]  │   │
│ └──────────────────────────┘   │
│                                │
│ ┌──────────────────────────┐   │
│ │ Es Teh Manis             │   │
│ │                          │   │
│ │ [-] 2 [+]      Rp 12.000 │   │
│ │                    [🗑️]  │   │
│ └──────────────────────────┘   │
│                                │
│ ┌──────────────────────────┐   │
│ │ + Tambah menu lain       │   │
│ └──────────────────────────┘   │
│                                │
│ ─────────────────────────────  │
│                                │
│ Subtotal            Rp 62.000  │
│ Pajak (10%)          Rp 6.200  │
│ Service (5%)         Rp 3.100  │
│                    ──────────  │
│ Total               Rp 71.300  │
│                                │
├────────────────────────────────┤
│                                │
│ ┌────────────────────────────┐ │
│ │       Pesan Sekarang       │ │
│ └────────────────────────────┘ │
│                                │
└────────────────────────────────┘
```

#### Screen 5: Customer Info Input

```
┌────────────────────────────────┐
│ ← Konfirmasi Pesanan           │
├────────────────────────────────┤
│                                │
│ Siapa yang memesan?            │
│                                │
│ Nama *                         │
│ ┌──────────────────────────┐   │
│ │ Budi                     │   │
│ └──────────────────────────┘   │
│                                │
│ No. HP (opsional)              │
│ ┌──────────────────────────┐   │
│ │ 0812-3456-7890           │   │
│ └──────────────────────────┘   │
│                                │
│ Catatan tambahan               │
│ ┌──────────────────────────┐   │
│ │ Tolong cepat ya, lapar   │   │
│ └──────────────────────────┘   │
│                                │
│ ─────────────────────────────  │
│                                │
│ Ringkasan Pesanan              │
│                                │
│ 3 item              Rp 71.300  │
│                                │
│ Pesanan akan dikirim ke        │
│ dapur setelah konfirmasi       │
│                                │
│                                │
├────────────────────────────────┤
│                                │
│ ┌────────────────────────────┐ │
│ │       Kirim Pesanan        │ │
│ └────────────────────────────┘ │
│                                │
└────────────────────────────────┘
```

#### Screen 6: Order Confirmation

```
┌────────────────────────────────┐
│                                │
├────────────────────────────────┤
│                                │
│                                │
│           ✅                   │
│                                │
│    Pesanan Terkirim!           │
│                                │
│    No. Pesanan:                │
│    QR-WB01-20250219-001        │
│                                │
│    ─────────────────────────   │
│                                │
│    Pesanan Anda sedang         │
│    menunggu konfirmasi         │
│    dari restoran               │
│                                │
│    Status:                     │
│    ┌────────────────────────┐  │
│    │   ⏳ Menunggu          │  │
│    │   Konfirmasi           │  │
│    └────────────────────────┘  │
│                                │
│    ─────────────────────────   │
│                                │
│    Detail Pesanan              │
│    • Nasi Goreng (1)           │
│    • Es Teh Manis (2)          │
│                                │
│    Total: Rp 71.300            │
│                                │
│                                │
│  ┌────────────────────────┐    │
│  │   Lihat Status Order   │    │
│  └────────────────────────┘    │
│                                │
│  ┌────────────────────────┐    │
│  │   Pesan Menu Lain      │    │
│  └────────────────────────┘    │
│                                │
└────────────────────────────────┘
```

#### Screen 7: Order Tracking

```
┌────────────────────────────────┐
│ ← Status Pesanan               │
├────────────────────────────────┤
│                                │
│ QR-WB01-20250219-001           │
│ Meja T05 • 3 item • Rp 71.300  │
│                                │
│ ─────────────────────────────  │
│                                │
│ ● ─── ○ ─── ○ ─── ○ ─── ○     │
│ Diterima                       │
│                                │
│  ✅ Pesanan Diterima           │
│     10:30 AM                   │
│                                │
│  ⏳ Sedang Dimasak             │
│     Estimasi 15 menit          │
│                                │
│  ○ Siap Disajikan              │
│                                │
│  ○ Selesai                     │
│                                │
│ ─────────────────────────────  │
│                                │
│ Detail Item                    │
│                                │
│ ┌──────────────────────────┐   │
│ │ 🍚 Nasi Goreng Seafood   │   │
│ │    ⏳ Sedang dimasak     │   │
│ └──────────────────────────┘   │
│                                │
│ ┌──────────────────────────┐   │
│ │ 🍹 Es Teh Manis (2)      │   │
│ │    ✅ Siap               │   │
│ └──────────────────────────┘   │
│                                │
│ ─────────────────────────────  │
│                                │
│  ┌────────────────────────┐    │
│  │   Pesan Menu Lain      │    │
│  └────────────────────────┘    │
│                                │
└────────────────────────────────┘
```

### Color Scheme & Design Tokens

```css
/* Default Theme - Can be customized per outlet */

:root {
  /* Primary Colors */
  --color-primary: #3B82F6;        /* Blue */
  --color-primary-dark: #2563EB;
  --color-primary-light: #93C5FD;

  /* Status Colors */
  --color-success: #10B981;        /* Green */
  --color-warning: #F59E0B;        /* Amber */
  --color-error: #EF4444;          /* Red */
  --color-info: #3B82F6;           /* Blue */

  /* Neutral Colors */
  --color-bg: #F9FAFB;
  --color-surface: #FFFFFF;
  --color-text: #111827;
  --color-text-secondary: #6B7280;
  --color-border: #E5E7EB;

  /* Typography */
  --font-family: 'Inter', system-ui, sans-serif;
  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --font-size-2xl: 1.5rem;

  /* Spacing */
  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 0.75rem;
  --space-4: 1rem;
  --space-6: 1.5rem;
  --space-8: 2rem;

  /* Border Radius */
  --radius-sm: 0.375rem;
  --radius-md: 0.5rem;
  --radius-lg: 0.75rem;
  --radius-full: 9999px;
}
```

---

## 11. Feature Gating & Pricing

### Tier Availability

| Feature | Starter | Growth | Professional | Enterprise |
|---------|---------|--------|--------------|------------|
| QR Order | ❌ | ❌ | ✅ | ✅ |
| QR Code Generation | ❌ | ❌ | ✅ | ✅ |
| Custom QR Theme | ❌ | ❌ | ❌ | ✅ |
| QR Order Analytics | ❌ | ❌ | Basic | Advanced |
| Auto-accept Orders | ❌ | ❌ | ✅ | ✅ |
| Telegram Notification | ❌ | ❌ | ❌ | ✅ |

### Feature Check Implementation

```php
// Middleware check
public function handle(Request $request, Closure $next)
{
    $tenant = $request->tenant();

    if (!$tenant->hasFeature('qr_order')) {
        return response()->json([
            'error' => 'QR Order feature not available in your plan',
            'upgrade_url' => route('subscription.upgrade')
        ], 403);
    }

    return $next($request);
}

// In SubscriptionPlan features array
'professional' => [
    'qr_order' => true,
    'qr_order_custom_theme' => false,
    'qr_order_analytics' => 'basic',
],
'enterprise' => [
    'qr_order' => true,
    'qr_order_custom_theme' => true,
    'qr_order_analytics' => 'advanced',
],
```

---

## 12. Implementation Phases

### Phase 1: Core QR Order (MVP) - 2-3 weeks

**Backend:**
- [ ] Create migrations (qr_orders, qr_order_items, qr_codes)
- [ ] Create Models & Relationships
- [ ] Create QR Code generation service
- [ ] Create public API endpoints (validate, menu, order)
- [ ] Create staff API endpoints (list, confirm, reject, update status)
- [ ] Add feature gate middleware

**Frontend PWA:**
- [ ] Setup Vue 3 + Vite PWA project
- [ ] Landing page (post-scan)
- [ ] Menu browsing (categories, products)
- [ ] Product detail with variants/modifiers
- [ ] Cart management
- [ ] Customer info & submit order
- [ ] Order confirmation & basic status

**Admin Dashboard:**
- [ ] QR Codes management page (generate, download)
- [ ] QR Orders list page (basic)
- [ ] QR Order settings page

### Phase 2: Real-time & Integration - 1-2 weeks

**Backend:**
- [ ] WebSocket events (new order, status update)
- [ ] POS integration (convert to transaction)
- [ ] Table session auto-create
- [ ] Sound notification system

**Frontend PWA:**
- [ ] Real-time status tracking
- [ ] Add items to existing order

**Admin Dashboard:**
- [ ] Real-time order monitor
- [ ] Notification center
- [ ] Quick actions (accept/reject/ready)

### Phase 3: Polish & Analytics - 1 week

**Backend:**
- [ ] QR Order analytics endpoints
- [ ] Report generation

**Frontend:**
- [ ] UI polish & animations
- [ ] Offline handling (PWA)
- [ ] Error states & edge cases

**Admin Dashboard:**
- [ ] QR Order dashboard widget
- [ ] QR Order reports
- [ ] Print QR codes as PDF

### Phase 4: Advanced Features (Future)

- [ ] Payment integration via QR Order
- [ ] Customer accounts & history
- [ ] Multi-language support
- [ ] Loyalty points integration
- [ ] Push notifications
- [ ] KDS deep integration
- [ ] Telegram bot notifications

---

## Summary

QR Self-Order adalah fitur yang akan memberikan value signifikan untuk restoran tier Professional dan Enterprise. Implementasi dilakukan secara bertahap dengan fokus pada:

1. **Core functionality first** - Order flow yang berjalan smooth
2. **Real-time experience** - Customer tahu status ordernya
3. **Integration** - Seamless dengan POS dan KDS existing
4. **Analytics** - Data-driven insights untuk restoran

Estimasi development: **4-6 weeks** untuk full implementation dengan 1-2 developer.
