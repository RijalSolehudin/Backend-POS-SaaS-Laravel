# Ultimate POS - Flutter Mobile App Documentation

## Overview

Dokumentasi lengkap untuk pengembangan Flutter Mobile App Ultimate POS. Dokumen ini mencakup design system, fitur yang akan diimplementasi, daftar API endpoints, dan flow setiap fitur.

---

## Daftar Isi

1. [Design System](#1-design-system)
2. [Fitur Mobile App](#2-fitur-mobile-app)
3. [API Endpoints](#3-api-endpoints)
4. [Feature Flows](#4-feature-flows)
5. [Technical Specifications](#5-technical-specifications)
6. [Offline Architecture](#6-offline-architecture)

---

## 1. Design System

### 1.1 Color Palette

#### Primary Colors - Royal Purple (Premium)
```dart
class AppColors {
  // Primary - Royal Purple
  static const Color primary50 = Color(0xFFFAF5FF);
  static const Color primary100 = Color(0xFFF3E8FF);
  static const Color primary200 = Color(0xFFE9D5FF);
  static const Color primary300 = Color(0xFFD8B4FE);
  static const Color primary400 = Color(0xFFC084FC);
  static const Color primary500 = Color(0xFFA855F7);
  static const Color primary600 = Color(0xFF9333EA);
  static const Color primary700 = Color(0xFF7C3AED);  // Main Primary
  static const Color primary800 = Color(0xFF6B21A8);
  static const Color primary900 = Color(0xFF581C87);

  // Secondary - Charcoal
  static const Color secondary50 = Color(0xFFF9FAFB);
  static const Color secondary100 = Color(0xFFF3F4F6);
  static const Color secondary200 = Color(0xFFE5E7EB);
  static const Color secondary300 = Color(0xFFD1D5DB);
  static const Color secondary400 = Color(0xFF9CA3AF);
  static const Color secondary500 = Color(0xFF6B7280);
  static const Color secondary600 = Color(0xFF4B5563);
  static const Color secondary700 = Color(0xFF374151);
  static const Color secondary800 = Color(0xFF1F2937);  // Main Secondary
  static const Color secondary900 = Color(0xFF111827);

  // Accent - Amber
  static const Color accent50 = Color(0xFFFFFBEB);
  static const Color accent100 = Color(0xFFFEF3C7);
  static const Color accent200 = Color(0xFFFDE68A);
  static const Color accent300 = Color(0xFFFCD34D);
  static const Color accent400 = Color(0xFFFBBF24);
  static const Color accent500 = Color(0xFFF59E0B);  // Main Accent
  static const Color accent600 = Color(0xFFD97706);
  static const Color accent700 = Color(0xFFB45309);
  static const Color accent800 = Color(0xFF92400E);
  static const Color accent900 = Color(0xFF78350F);

  // Status Colors
  static const Color success = Color(0xFF10B981);
  static const Color warning = Color(0xFFF59E0B);
  static const Color danger = Color(0xFFEF4444);
  static const Color info = Color(0xFFA855F7);

  // Surface & Background
  static const Color surface = Color(0xFFFFFFFF);
  static const Color background = Color(0xFFFAF5FF);
  static const Color border = Color(0xFFE9D5FF);
  static const Color textPrimary = Color(0xFF111827);
  static const Color textSecondary = Color(0xFF6B7280);
}
```

#### Dark Mode Colors
```dart
class AppColorsDark {
  static const Color surface = Color(0xFF1F2937);
  static const Color background = Color(0xFF111827);
  static const Color border = Color(0xFF374151);
  static const Color textPrimary = Color(0xFFF9FAFB);
  static const Color textSecondary = Color(0xFF9CA3AF);
}
```

### 1.2 Typography

```dart
class AppTypography {
  static const String fontFamily = 'Inter';

  // Headings
  static const TextStyle h1 = TextStyle(
    fontFamily: fontFamily,
    fontSize: 36,
    fontWeight: FontWeight.bold,
    height: 1.2,
  );

  static const TextStyle h2 = TextStyle(
    fontFamily: fontFamily,
    fontSize: 30,
    fontWeight: FontWeight.bold,
    height: 1.25,
  );

  static const TextStyle h3 = TextStyle(
    fontFamily: fontFamily,
    fontSize: 24,
    fontWeight: FontWeight.w600,
    height: 1.3,
  );

  static const TextStyle h4 = TextStyle(
    fontFamily: fontFamily,
    fontSize: 20,
    fontWeight: FontWeight.w600,
    height: 1.35,
  );

  // Body
  static const TextStyle bodyLarge = TextStyle(
    fontFamily: fontFamily,
    fontSize: 18,
    fontWeight: FontWeight.normal,
    height: 1.5,
  );

  static const TextStyle bodyMedium = TextStyle(
    fontFamily: fontFamily,
    fontSize: 16,
    fontWeight: FontWeight.normal,
    height: 1.5,
  );

  static const TextStyle bodySmall = TextStyle(
    fontFamily: fontFamily,
    fontSize: 14,
    fontWeight: FontWeight.normal,
    height: 1.5,
  );

  // Caption & Labels
  static const TextStyle caption = TextStyle(
    fontFamily: fontFamily,
    fontSize: 12,
    fontWeight: FontWeight.normal,
    height: 1.4,
  );

  static const TextStyle label = TextStyle(
    fontFamily: fontFamily,
    fontSize: 14,
    fontWeight: FontWeight.w500,
    height: 1.4,
  );

  // Button
  static const TextStyle button = TextStyle(
    fontFamily: fontFamily,
    fontSize: 16,
    fontWeight: FontWeight.w600,
    height: 1.25,
  );
}
```

### 1.3 Spacing

```dart
class AppSpacing {
  static const double xs = 4;
  static const double sm = 8;
  static const double md = 12;
  static const double base = 16;
  static const double lg = 24;
  static const double xl = 32;
  static const double xxl = 48;
  static const double xxxl = 64;
}
```

### 1.4 Border Radius

```dart
class AppRadius {
  static const double sm = 6;
  static const double base = 8;
  static const double md = 10;
  static const double lg = 14;
  static const double xl = 20;
  static const double xxl = 28;
  static const double full = 9999;
}
```

### 1.5 Shadows

```dart
class AppShadows {
  static const BoxShadow sm = BoxShadow(
    color: Color(0x0D7C3AED),
    blurRadius: 2,
    offset: Offset(0, 1),
  );

  static const BoxShadow base = BoxShadow(
    color: Color(0x1A7C3AED),
    blurRadius: 3,
    offset: Offset(0, 1),
  );

  static const BoxShadow md = BoxShadow(
    color: Color(0x1A7C3AED),
    blurRadius: 6,
    offset: Offset(0, 4),
  );

  static const BoxShadow lg = BoxShadow(
    color: Color(0x1A7C3AED),
    blurRadius: 15,
    offset: Offset(0, 10),
  );

  static const BoxShadow xl = BoxShadow(
    color: Color(0x1A7C3AED),
    blurRadius: 25,
    offset: Offset(0, 20),
  );

  static const BoxShadow premium = BoxShadow(
    color: Color(0x4D7C3AED),
    blurRadius: 40,
    offset: Offset(0, 10),
  );
}
```

### 1.6 Gradients

```dart
class AppGradients {
  static const LinearGradient primary = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [
      Color(0xFF7C3AED),
      Color(0xFFA855F7),
      Color(0xFFC084FC),
    ],
  );

  static const LinearGradient hero = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [
      Color(0xFF1F2937),
      Color(0xFF374151),
      Color(0xFF7C3AED),
    ],
  );

  static const LinearGradient accent = LinearGradient(
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
    colors: [
      Color(0xFF7C3AED),
      Color(0xFFF59E0B),
    ],
  );
}
```

---

## 2. Fitur Mobile App

### 2.1 Fitur Core (Semua Tier)

| Fitur | Deskripsi | Priority |
|-------|-----------|----------|
| **Login** | Email/password atau PIN login | P0 |
| **Session Management** | Open/close shift dengan cash count | P0 |
| **POS Transaction** | Create order, add items, checkout | P0 |
| **Product Browsing** | Browse categories & products | P0 |
| **Cart Management** | Add/edit/remove items, variants, modifiers | P0 |
| **Payment Processing** | Cash, multi-payment | P0 |
| **Receipt** | Display & print receipt | P0 |
| **Offline Mode** | Transaksi offline dengan sync | P0 |
| **Customer Management** | Add/search customer | P1 |
| **Held Orders** | Pause & resume orders | P1 |
| **Cash Drawer** | Cash in/out tracking | P1 |
| **Barcode Scanner** | Scan product barcode | P1 |

### 2.2 Fitur Tier-Dependent

| Fitur | Starter | Growth | Professional | Enterprise |
|-------|:-------:|:------:|:------------:|:----------:|
| Basic POS | ✅ | ✅ | ✅ | ✅ |
| Product Variants | ❌ | ✅ | ✅ | ✅ |
| Combo Products | ❌ | ✅ | ✅ | ✅ |
| Discounts & Promo | ❌ | ✅ | ✅ | ✅ |
| Table Management | ❌ | ✅ | ✅ | ✅ |
| Inventory Basic | ❌ | ✅ | ✅ | ✅ |
| Inventory Advanced | ❌ | ❌ | ✅ | ✅ |
| Recipe/BOM | ❌ | ❌ | ✅ | ✅ |
| Manager Authorization | ❌ | ❌ | ✅ | ✅ |
| Waiter App Mode | ❌ | ❌ | 1 device | Unlimited |
| Split Payment | ❌ | ❌ | ✅ | ✅ |
| API Access | ❌ | ❌ | ❌ | ✅ |
| KDS Integration | ❌ | ❌ | ❌ | ✅ |

### 2.3 App Modes

#### 2.3.1 Cashier Mode (Default)
- Full POS functionality
- Single session per user
- Process payments
- Manage cash drawer

#### 2.3.2 Waiter Mode (Professional+)
- Create orders for tables
- Limited POS access
- No payment processing
- Multi-device support

#### 2.3.3 Kitchen Display Mode (Enterprise)
- View incoming orders
- Mark items as prepared
- No transaction access

---

## 3. API Endpoints

### 3.1 Base Configuration

```
Base URL (Production): https://api.ultimatepos.id
Base URL (Development): http://127.0.0.1:8000

API Version: /api/v1 (legacy), /api/v2 (enhanced)

Headers:
- Authorization: Bearer {token}
- Accept: application/json
- Content-Type: application/json
- X-Outlet-Id: {outlet_uuid}  // Required for outlet-specific operations
```

### 3.2 Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Login dengan email/password |
| POST | `/api/v1/auth/pin-login` | Login dengan outlet + PIN |
| POST | `/api/v1/auth/logout` | Logout (revoke token) |
| GET | `/api/v1/auth/me` | Get current user profile |
| PUT | `/api/v1/auth/profile` | Update profile |
| PUT | `/api/v1/auth/pin` | Update PIN |

#### Login Request
```json
POST /api/v1/auth/login
{
  "email": "cashier@demo.com",
  "password": "password",
  "device_name": "Samsung Galaxy Tab A"
}
```

#### PIN Login Request
```json
POST /api/v1/auth/pin-login
{
  "outlet_id": "uuid-here",
  "pin": "123456",
  "device_name": "Samsung Galaxy Tab A"
}
```

#### Login Response
```json
{
  "user": {
    "id": "uuid",
    "name": "John Cashier",
    "email": "cashier@demo.com",
    "phone": "08123456789",
    "locale": "id",
    "roles": ["cashier"],
    "permissions": ["pos.access", "pos.transaction"],
    "outlets": [
      {
        "id": "uuid",
        "name": "Outlet Pusat",
        "code": "OUT001"
      }
    ],
    "current_outlet": {
      "id": "uuid",
      "name": "Outlet Pusat"
    }
  },
  "token": "1|abc123xyz...",
  "token_type": "Bearer"
}
```

### 3.3 Outlets

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/outlets` | List accessible outlets |
| GET | `/api/v1/outlets/{id}` | Get outlet detail |
| POST | `/api/v1/outlets/switch` | Switch to another outlet |

#### Outlet Detail Response
```json
{
  "id": "uuid",
  "name": "Outlet Pusat",
  "code": "OUT001",
  "address": "Jl. Sudirman No. 1",
  "phone": "021-1234567",
  "tax_rate": 11,
  "tax_name": "PPN",
  "tax_enabled": true,
  "tax_mode": "exclusive",
  "service_charge_rate": 5,
  "service_charge_enabled": true,
  "opening_time": "08:00",
  "closing_time": "22:00",
  "timezone": "Asia/Jakarta"
}
```

### 3.4 Products & Categories

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/categories` | List categories with product count |
| GET | `/api/v1/categories/{id}` | Get category detail |
| GET | `/api/v1/categories/{id}/products` | Get products in category |
| GET | `/api/v1/products` | List all active products |
| GET | `/api/v1/products/search?q={query}` | Search products |
| GET | `/api/v1/products/barcode/{barcode}` | Get product by barcode |
| GET | `/api/v1/products/{id}` | Get product detail |

#### Product Response
```json
{
  "id": "uuid",
  "name": "Nasi Goreng Spesial",
  "description": "Nasi goreng dengan telur dan ayam",
  "sku": "NG001",
  "barcode": "8991234567890",
  "price": 35000,
  "category": {
    "id": "uuid",
    "name": "Makanan"
  },
  "image_url": "https://...",
  "has_variants": true,
  "variants": [
    {
      "id": "uuid",
      "name": "Porsi",
      "options": [
        {"id": "uuid", "name": "Regular", "price_adjustment": 0},
        {"id": "uuid", "name": "Large", "price_adjustment": 10000}
      ]
    }
  ],
  "modifiers": [
    {
      "id": "uuid",
      "name": "Tambahan",
      "required": false,
      "max_selections": 3,
      "options": [
        {"id": "uuid", "name": "Telur Ceplok", "price": 5000},
        {"id": "uuid", "name": "Kerupuk", "price": 3000}
      ]
    }
  ],
  "stock_tracking": true,
  "current_stock": 50,
  "low_stock_threshold": 10
}
```

### 3.5 Sessions (Shifts)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2/sessions/current` | Get current active session |
| GET | `/api/v2/sessions/history` | Get past sessions |
| GET | `/api/v2/sessions/active-any` | Check if any session active |
| POST | `/api/v2/sessions/open` | Open new shift |
| POST | `/api/v2/sessions/close` | Close current shift |
| GET | `/api/v2/sessions/{id}/report` | Get session report |

#### Open Session Request
```json
POST /api/v2/sessions/open
{
  "opening_cash": 500000,
  "notes": "Shift pagi"
}
```

#### Open Session Response
```json
{
  "id": "uuid",
  "user_id": "uuid",
  "outlet_id": "uuid",
  "status": "active",
  "opening_cash": 500000,
  "opened_at": "2026-02-13T08:00:00+07:00",
  "notes": "Shift pagi"
}
```

#### Close Session Request
```json
POST /api/v2/sessions/close
{
  "closing_cash": 2500000,
  "notes": "Shift selesai normal"
}
```

#### Session Report Response
```json
{
  "session_id": "uuid",
  "opened_at": "2026-02-13T08:00:00+07:00",
  "closed_at": "2026-02-13T16:00:00+07:00",
  "opening_cash": 500000,
  "closing_cash": 2500000,
  "summary": {
    "total_transactions": 45,
    "total_sales": 4500000,
    "total_tax": 450000,
    "total_service_charge": 225000,
    "total_discount": 150000,
    "net_sales": 4125000
  },
  "payments": {
    "cash": 2800000,
    "debit_card": 1200000,
    "qris": 500000
  },
  "expected_cash": 3300000,
  "cash_difference": -800000
}
```

### 3.6 Transactions

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v2/orders/calculate` | Calculate cart totals |
| POST | `/api/v2/orders/checkout` | Create transaction |
| GET | `/api/v1/transactions` | List transactions |
| GET | `/api/v1/transactions/{id}` | Get transaction detail |
| POST | `/api/v1/transactions/{id}/void` | Void transaction |
| POST | `/api/v1/transactions/{id}/refund` | Create refund |
| GET | `/api/v1/transactions/{id}/receipt` | Get receipt data |

#### Calculate Request
```json
POST /api/v2/orders/calculate
{
  "items": [
    {
      "product_id": "uuid",
      "quantity": 2,
      "variant_options": ["uuid-regular"],
      "modifiers": ["uuid-telur"],
      "notes": "Tidak pedas"
    }
  ],
  "discount_type": "percentage",
  "discount_value": 10,
  "customer_id": "uuid"
}
```

#### Calculate Response
```json
{
  "items": [
    {
      "product_id": "uuid",
      "product_name": "Nasi Goreng Spesial",
      "quantity": 2,
      "unit_price": 35000,
      "variant_price": 0,
      "modifier_price": 5000,
      "item_total": 80000
    }
  ],
  "subtotal": 80000,
  "discount_amount": 8000,
  "subtotal_after_discount": 72000,
  "tax_amount": 7920,
  "service_charge_amount": 3600,
  "grand_total": 83520,
  "tax_mode": "exclusive",
  "tax_rate": 11,
  "service_charge_rate": 5
}
```

#### Checkout Request
```json
POST /api/v2/orders/checkout
{
  "order_type": "dine_in",
  "table_id": "uuid",
  "items": [...],
  "discount_type": "percentage",
  "discount_value": 10,
  "customer_id": "uuid",
  "payments": [
    {
      "payment_method_id": "uuid-cash",
      "amount": 50000
    },
    {
      "payment_method_id": "uuid-qris",
      "amount": 33520
    }
  ],
  "notes": "Minta tissue extra"
}
```

#### Checkout Response
```json
{
  "id": "uuid",
  "transaction_number": "OUT001-20260213-00045",
  "status": "completed",
  "order_type": "dine_in",
  "subtotal": 80000,
  "discount_amount": 8000,
  "tax_amount": 7920,
  "service_charge_amount": 3600,
  "grand_total": 83520,
  "total_paid": 83520,
  "change": 0,
  "items": [...],
  "payments": [...],
  "customer": {...},
  "created_at": "2026-02-13T14:30:00+07:00",
  "receipt_url": "https://..."
}
```

### 3.7 Payment Methods

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/payment-methods` | List payment methods |
| GET | `/api/v1/payment-methods/types` | Get payment types |
| POST | `/api/v1/payment-methods/calculate-charge` | Calculate gateway charge |

#### Payment Methods Response
```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Cash",
      "type": "CASH",
      "is_active": true,
      "charge_percentage": 0,
      "charge_fixed": 0
    },
    {
      "id": "uuid",
      "name": "QRIS",
      "type": "QRIS",
      "is_active": true,
      "charge_percentage": 0.7,
      "charge_fixed": 0
    },
    {
      "id": "uuid",
      "name": "Credit Card",
      "type": "CREDIT_CARD",
      "is_active": true,
      "charge_percentage": 2.5,
      "charge_fixed": 0
    }
  ]
}
```

### 3.8 Customers

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/customers` | List customers |
| POST | `/api/v1/customers` | Create customer |
| GET | `/api/v1/customers/search?q={query}` | Search customers |
| GET | `/api/v1/customers/{id}` | Get customer detail |
| PUT | `/api/v1/customers/{id}` | Update customer |
| GET | `/api/v1/customers/{id}/transactions` | Customer transactions |

### 3.9 Held Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/held-orders` | List held orders |
| POST | `/api/v1/held-orders` | Create held order |
| GET | `/api/v1/held-orders/{id}` | Get held order |
| DELETE | `/api/v1/held-orders/{id}` | Delete held order |
| POST | `/api/v1/held-orders/{id}/restore` | Restore to cart |

### 3.10 Tables & Floors (Growth+)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/floors` | List floors |
| GET | `/api/v1/floors/{id}/tables` | Get tables in floor |
| GET | `/api/v1/tables` | List all tables |
| PATCH | `/api/v1/tables/{id}/status` | Update table status |
| POST | `/api/v1/tables/{id}/open` | Open table session |
| POST | `/api/v1/tables/{id}/close` | Close table session |
| POST | `/api/v1/tables/{id}/move` | Move order to another table |

### 3.11 Cash Drawer

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2/cash-drawer/status` | Get drawer status |
| GET | `/api/v2/cash-drawer/balance` | Get current balance |
| GET | `/api/v2/cash-drawer/logs` | Get cash logs |
| POST | `/api/v2/cash-drawer/cash-in` | Record cash in |
| POST | `/api/v2/cash-drawer/cash-out` | Record cash out |
| POST | `/api/v2/cash-drawer/open` | Open physical drawer |

### 3.12 Manager Authorization (Professional+)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/authorize` | Verify manager PIN |
| GET | `/api/v1/authorize/check` | Check if action needs auth |
| GET | `/api/v1/authorize/managers` | List available managers |
| GET | `/api/v1/authorize/settings` | Get auth settings |
| GET | `/api/v1/authorize/logs` | Get auth logs |

#### Authorization Request
```json
POST /api/v1/authorize
{
  "action_type": "void",
  "pin": "123456",
  "transaction_id": "uuid",
  "reason": "Customer complaint"
}
```

### 3.13 Mobile Sync (Offline)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2/sync/master` | Full master data sync |
| GET | `/api/v2/sync/delta?since={timestamp}` | Incremental sync |
| POST | `/api/v2/sync/transactions` | Upload offline transactions |
| POST | `/api/v2/sync/sessions` | Sync session status |

#### Master Sync Response
```json
{
  "synced_at": "2026-02-13T08:00:00+07:00",
  "outlet": {...},
  "categories": [...],
  "products": [...],
  "payment_methods": [...],
  "floors": [...],
  "tables": [...],
  "customers": [...],
  "settings": {
    "tax_rate": 11,
    "tax_mode": "exclusive",
    "service_charge_rate": 5,
    "authorization_settings": {...}
  }
}
```

### 3.14 Discounts

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/discounts` | List available discounts |
| POST | `/api/v1/discounts/validate` | Validate discount code |

### 3.15 Reports

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2/reports/sales/summary` | Sales summary |
| GET | `/api/v2/reports/sales/by-payment-method` | By payment method |
| GET | `/api/v2/reports/sales/by-category` | By category |
| GET | `/api/v2/reports/sales/by-product` | By product |
| GET | `/api/v2/reports/sales/hourly` | Hourly trend |
| GET | `/api/v2/reports/sales/daily` | Daily trend |

### 3.16 Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v2/settings` | Get all settings |
| GET | `/api/v2/settings/outlet` | Outlet settings |
| GET | `/api/v2/settings/pos` | POS settings |
| GET | `/api/v2/settings/receipt` | Receipt settings |
| GET | `/api/v2/settings/features` | Feature flags |
| GET | `/api/v2/settings/features/{name}` | Check specific feature |
| GET | `/api/v2/settings/subscription` | Subscription status |

---

## 4. Feature Flows

### 4.1 Login Flow

```
┌─────────────────┐
│   App Launch    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌─────────────────┐
│  Has Token?     │──No─▶│  Login Screen   │
└────────┬────────┘     └────────┬────────┘
         │Yes                    │
         ▼                       ▼
┌─────────────────┐     ┌─────────────────┐
│  Validate Token │     │ Email/Password  │
│  GET /auth/me   │     │   or PIN Login  │
└────────┬────────┘     └────────┬────────┘
         │                       │
    ┌────┴────┐                  │
    │         │                  │
  Valid    Invalid               │
    │         │                  │
    │         └──────────────────┤
    ▼                            ▼
┌─────────────────┐     ┌─────────────────┐
│  Check Session  │◀────│  Store Token    │
│  Active?        │     └─────────────────┘
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
   Yes        No
    │         │
    ▼         ▼
┌───────┐  ┌─────────────────┐
│  POS  │  │  Open Session   │
│ Screen│  │     Screen      │
└───────┘  └─────────────────┘
```

### 4.2 Open Shift Flow

```
┌─────────────────┐
│  Open Session   │
│     Screen      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Input Opening   │
│     Cash        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  POST /sessions │
│     /open       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Master Sync    │
│ GET /sync/master│
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Store Local    │
│  (SQLite/Hive)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   POS Screen    │
│    (Ready)      │
└─────────────────┘
```

### 4.3 Transaction Flow

```
┌─────────────────┐
│   POS Screen    │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────────────────┐
│                    Browse Products                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐          │
│  │ Category │  │  Search  │  │ Barcode  │          │
│  │   List   │  │   Box    │  │ Scanner  │          │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘          │
│       └─────────────┴─────────────┘                 │
└────────────────────────┬────────────────────────────┘
                         │
                         ▼
                ┌─────────────────┐
                │  Select Product │
                └────────┬────────┘
                         │
            ┌────────────┴────────────┐
            │                         │
      Has Variants?              No Variants
            │                         │
            ▼                         │
   ┌─────────────────┐               │
   │ Select Variant  │               │
   │   & Modifiers   │               │
   └────────┬────────┘               │
            │                         │
            └────────────┬────────────┘
                         │
                         ▼
                ┌─────────────────┐
                │   Add to Cart   │
                └────────┬────────┘
                         │
                         ▼
                ┌─────────────────┐
                │  Cart Summary   │
                │ - Items list    │
                │ - Quantities    │
                │ - Subtotal      │
                └────────┬────────┘
                         │
         ┌───────────────┼───────────────┐
         │               │               │
         ▼               ▼               ▼
   ┌───────────┐   ┌───────────┐   ┌───────────┐
   │  Add More │   │   Apply   │   │ Checkout  │
   │   Items   │   │ Discount  │   │           │
   └─────┬─────┘   └─────┬─────┘   └─────┬─────┘
         │               │               │
         └───────────────┘               │
                                         ▼
                                ┌─────────────────┐
                                │    Calculate    │
                                │ POST /calculate │
                                └────────┬────────┘
                                         │
                                         ▼
                                ┌─────────────────┐
                                │ Payment Screen  │
                                │ - Grand total   │
                                │ - Select method │
                                │ - Input amount  │
                                └────────┬────────┘
                                         │
                          ┌──────────────┴──────────────┐
                          │                             │
                    Single Payment              Multi Payment
                          │                             │
                          ▼                             ▼
                 ┌─────────────────┐         ┌─────────────────┐
                 │  Input Amount   │         │  Split Payment  │
                 │                 │         │  Multiple times │
                 └────────┬────────┘         └────────┬────────┘
                          │                           │
                          └─────────────┬─────────────┘
                                        │
                                        ▼
                               ┌─────────────────┐
                               │    Checkout     │
                               │ POST /checkout  │
                               └────────┬────────┘
                                        │
                                        ▼
                               ┌─────────────────┐
                               │ Receipt Screen  │
                               │ - Print option  │
                               │ - Share option  │
                               └────────┬────────┘
                                        │
                                        ▼
                               ┌─────────────────┐
                               │   POS Screen    │
                               │    (Reset)      │
                               └─────────────────┘
```

### 4.4 Offline Transaction Flow

```
┌─────────────────┐
│ Session Opened  │
│ Master Synced   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Network Lost   │
│ (No Internet)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Transaction     │
│ Created Offline │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Store Locally  │
│  pending_queue  │
│  SQLite/Hive    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│   Continue      │
│  Transactions   │
└────────┬────────┘
         │
         │ (Network restored OR close shift)
         ▼
┌─────────────────┐
│    Sync Queue   │
│ POST /sync/     │
│  transactions   │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
  Success   Failed
    │         │
    ▼         ▼
┌────────┐  ┌─────────────────┐
│ Clear  │  │ Retry / Manual  │
│ Queue  │  │   Resolution    │
└────────┘  └─────────────────┘
```

### 4.5 Close Shift Flow

```
┌─────────────────┐
│  Close Shift    │
│    Button       │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Check Pending   │
│  Transactions   │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
Has Pending  None
    │         │
    ▼         │
┌─────────────────┐    │
│  Sync Pending   │    │
│  Transactions   │    │
└────────┬────────┘    │
         │             │
         └──────┬──────┘
                │
                ▼
       ┌─────────────────┐
       │ Input Closing   │
       │     Cash        │
       └────────┬────────┘
                │
                ▼
       ┌─────────────────┐
       │  POST /sessions │
       │     /close      │
       └────────┬────────┘
                │
                ▼
       ┌─────────────────┐
       │ Session Report  │
       │ - Total sales   │
       │ - Cash summary  │
       │ - Difference    │
       └────────┬────────┘
                │
                ▼
       ┌─────────────────┐
       │   Clear Local   │
       │      Data       │
       └────────┬────────┘
                │
                ▼
       ┌─────────────────┐
       │  Login Screen   │
       │   or New Shift  │
       └─────────────────┘
```

### 4.6 Manager Authorization Flow

```
┌─────────────────┐
│ Sensitive Action│
│ (Void/Refund/   │
│  Big Discount)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Check Auth      │
│ Required?       │
│ GET /authorize/ │
│    check        │
└────────┬────────┘
         │
    ┌────┴────┐
    │         │
Required   Not Required
    │         │
    ▼         └──────────────────┐
┌─────────────────┐              │
│ Show PIN Dialog │              │
│ Select Manager  │              │
└────────┬────────┘              │
         │                       │
         ▼                       │
┌─────────────────┐              │
│ Input PIN       │              │
└────────┬────────┘              │
         │                       │
         ▼                       │
┌─────────────────┐              │
│ POST /authorize │              │
└────────┬────────┘              │
         │                       │
    ┌────┴────┐                  │
    │         │                  │
 Success    Failed               │
    │         │                  │
    │         ▼                  │
    │   ┌─────────────────┐      │
    │   │ Show Error      │      │
    │   │ Retry or Cancel │      │
    │   └─────────────────┘      │
    │                            │
    └────────────┬───────────────┘
                 │
                 ▼
        ┌─────────────────┐
        │ Proceed Action  │
        └─────────────────┘
```

### 4.7 Table Management Flow (Growth+)

```
┌─────────────────┐
│  Floor/Table    │
│     View        │
└────────┬────────┘
         │
         ▼
┌─────────────────────────────────────────┐
│              Floor Layout               │
│  ┌────┐  ┌────┐  ┌────┐  ┌────┐        │
│  │ T1 │  │ T2 │  │ T3 │  │ T4 │        │
│  │Free│  │Busy│  │Free│  │Busy│        │
│  └────┘  └────┘  └────┘  └────┘        │
│  🟢      🔴      🟢      🔴            │
└────────────────────┬────────────────────┘
                     │
        ┌────────────┴────────────┐
        │                         │
   Select Free               Select Occupied
   Table                     Table
        │                         │
        ▼                         ▼
┌─────────────────┐     ┌─────────────────┐
│ POST /tables/   │     │ Show Current    │
│   {id}/open     │     │    Order        │
└────────┬────────┘     └────────┬────────┘
         │                       │
         ▼              ┌────────┴────────┐
┌─────────────────┐     │                 │
│  Start Order    │   Add Items      Checkout
│  (Same as POS)  │     │                 │
└─────────────────┘     ▼                 ▼
                   ┌─────────┐     ┌─────────────┐
                   │ Update  │     │  Payment    │
                   │ Order   │     │   Flow      │
                   └─────────┘     └──────┬──────┘
                                          │
                                          ▼
                                  ┌─────────────────┐
                                  │ POST /tables/   │
                                  │   {id}/close    │
                                  └────────┬────────┘
                                           │
                                           ▼
                                  ┌─────────────────┐
                                  │ Table Available │
                                  └─────────────────┘
```

---

## 5. Technical Specifications

### 5.1 Authentication

| Property | Value |
|----------|-------|
| Method | Laravel Sanctum (Token-based) |
| Token Type | Bearer token (plain text) |
| Storage | Secure storage (flutter_secure_storage) |
| Session Timeout | No auto-timeout (manual logout) |

### 5.2 Session Rules by Role

| Role | Session Type | Max Devices |
|------|--------------|-------------|
| Owner | Single | 1 |
| Manager | Single | 1 |
| Cashier | Single | 1 |
| Waiter | Multi | Unlimited |
| Kitchen | Multi | Unlimited |

### 5.3 Rate Limiting

| Endpoint | Limit |
|----------|-------|
| Login | 5 requests/minute/IP |
| General API | 60 requests/minute/user |
| Burst | 10 requests/second max |

### 5.4 Transaction Number Format

```
Format: {OUTLET_CODE}-{YYYYMMDD}-{SEQ}
Example: OUT001-20260213-00045

Components:
- OUTLET_CODE: 6 karakter outlet code
- YYYYMMDD: Tanggal transaksi
- SEQ: 5 digit sequence number per hari per outlet
```

### 5.5 Tax Modes

| Mode | Description |
|------|-------------|
| `exclusive` | Harga belum termasuk pajak (pajak ditambahkan) |
| `inclusive` | Harga sudah termasuk pajak |

### 5.6 Order Types

| Type | Description |
|------|-------------|
| `dine_in` | Makan di tempat |
| `takeaway` | Bawa pulang |
| `delivery` | Pesan antar |

### 5.7 Transaction Status

| Status | Description |
|--------|-------------|
| `pending` | Belum selesai |
| `completed` | Selesai & dibayar |
| `voided` | Dibatalkan |

### 5.8 Payment Method Types

| Type | Description |
|------|-------------|
| `CASH` | Tunai |
| `DEBIT_CARD` | Kartu debit |
| `CREDIT_CARD` | Kartu kredit |
| `QRIS` | QRIS |
| `BANK_TRANSFER` | Transfer bank |
| `XENDIT_INVOICE` | Xendit invoice |

---

## 6. Offline Architecture

### 6.1 Local Storage Schema

```dart
// Menggunakan Hive atau SQLite

// Master Data (sync saat open shift)
class LocalProduct {
  String id;
  String name;
  String? sku;
  String? barcode;
  double price;
  String categoryId;
  String? imageUrl;
  bool hasVariants;
  List<LocalVariant> variants;
  List<LocalModifier> modifiers;
  int? currentStock;
  DateTime syncedAt;
}

class LocalCategory {
  String id;
  String name;
  String? parentId;
  int sortOrder;
  DateTime syncedAt;
}

class LocalPaymentMethod {
  String id;
  String name;
  String type;
  double chargePercentage;
  double chargeFixed;
  bool isActive;
}

class LocalCustomer {
  String id;
  String name;
  String? phone;
  String? email;
  int? loyaltyPoints;
}

// Pending Transactions (created offline)
class PendingTransaction {
  String localId; // UUID generated locally
  String? serverId; // null until synced
  String orderType;
  String? tableId;
  List<PendingItem> items;
  double subtotal;
  double discountAmount;
  double taxAmount;
  double serviceChargeAmount;
  double grandTotal;
  List<PendingPayment> payments;
  String? customerId;
  String? notes;
  String status; // 'pending_sync', 'synced', 'failed'
  DateTime createdAt;
  DateTime? syncedAt;
  String? syncError;
}

class PendingItem {
  String productId;
  String productName;
  int quantity;
  double unitPrice;
  List<String> variantOptionIds;
  List<String> modifierIds;
  double itemTotal;
  String? notes;
}

class PendingPayment {
  String paymentMethodId;
  String paymentMethodName;
  double amount;
}

// Current Session
class LocalSession {
  String id;
  String status; // 'active', 'closed'
  double openingCash;
  double? closingCash;
  DateTime openedAt;
  DateTime? closedAt;
}

// Settings Cache
class LocalSettings {
  double taxRate;
  String taxMode;
  double serviceChargeRate;
  bool taxEnabled;
  bool serviceChargeEnabled;
  Map<String, dynamic> authorizationSettings;
  DateTime syncedAt;
}
```

### 6.2 Sync Strategy

```
1. Open Shift → Full Master Sync
   - Download semua categories, products, payment methods
   - Download tables & floors (jika dine-in enabled)
   - Download customers (recent 1000)
   - Store ke local database
   - Mark sync timestamp

2. During Shift → Incremental Sync (setiap 5 menit jika online)
   - GET /sync/delta?since={last_sync_timestamp}
   - Update changed products, prices, stock
   - Merge dengan local data

3. Transaction → Immediate Sync (jika online)
   - POST /orders/checkout
   - Jika gagal → store ke pending_queue

4. Transaction → Offline Mode (jika offline)
   - Generate local transaction ID
   - Store ke pending_queue
   - Continue tanpa server

5. Close Shift → Bulk Sync
   - Upload semua pending transactions
   - POST /sync/transactions dengan array
   - Handle conflicts (price changed, product deleted)
   - Clear local pending queue
   - Close session
```

### 6.3 Conflict Resolution

```
Scenario 1: Product Price Changed
- Server price berbeda dari local price
- Resolution: Use server price, update local, notify user

Scenario 2: Product Deleted/Deactivated
- Product sudah tidak ada di server
- Resolution: Mark transaction item sebagai "legacy", proceed dengan sync

Scenario 3: Stock Not Sufficient
- Local stock OK, server stock insufficient
- Resolution: Allow transaction, flag untuk review

Scenario 4: Session Already Closed
- Another device closed session
- Resolution: Reject new transactions, force sync pending, close local
```

### 6.4 Data Retention

| Data Type | Retention |
|-----------|-----------|
| Master data | Until next shift open |
| Pending transactions | Until synced |
| Synced transactions | 7 days (for receipt reprint) |
| Session history | 30 days |

---

## Appendix A: Error Codes

| Code | Message | Action |
|------|---------|--------|
| 401 | Unauthorized | Redirect to login |
| 403 | Forbidden | Show permission error |
| 404 | Not Found | Show not found error |
| 422 | Validation Error | Show field errors |
| 429 | Too Many Requests | Show rate limit error |
| 500 | Server Error | Show generic error, retry |
| `OFFLINE` | No Connection | Continue offline mode |
| `SYNC_FAILED` | Sync Failed | Retry sync |
| `SESSION_EXPIRED` | Session Expired | Close shift, re-login |

---

## Appendix B: Feature Flags

```dart
enum Feature {
  posCore,
  productVariants,
  comboProducts,
  discounts,
  tableManagement,
  inventoryBasic,
  inventoryAdvanced,
  recipeBom,
  managerAuthorization,
  waiterApp,
  splitPayment,
  apiAccess,
  kdsIntegration,
  customBranding,
}

// Check feature availability
final isEnabled = await FeatureService.check(Feature.tableManagement);
```

---

## Appendix C: Recommended Packages

```yaml
dependencies:
  # State Management
  flutter_bloc: ^8.x
  # atau
  riverpod: ^2.x

  # API & Networking
  dio: ^5.x
  retrofit: ^4.x

  # Local Storage
  hive: ^2.x
  hive_flutter: ^1.x
  # atau
  sqflite: ^2.x

  # Secure Storage
  flutter_secure_storage: ^9.x

  # Barcode Scanner
  mobile_scanner: ^3.x

  # Printing
  esc_pos_utils: ^1.x
  esc_pos_bluetooth: ^0.x

  # UI Components
  flutter_slidable: ^3.x
  shimmer: ^3.x
  cached_network_image: ^3.x

  # Utils
  intl: ^0.x
  uuid: ^4.x
  connectivity_plus: ^5.x
```

---

*Dokumen ini akan diupdate seiring pengembangan aplikasi.*

*Last updated: 2026-02-13*
