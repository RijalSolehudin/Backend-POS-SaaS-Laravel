# POS F&B Development Plan
## Complete System Architecture & Phase Breakdown

---

## Project Overview

### Scope
- **Platform**: Multi-outlet POS F&B System
- **Target**: Restaurant, Cafe, Food Court
- **Devices**: Android Phone, Android Tablet, Web Browser

### Deliverables
1. **Dashboard Admin** (Web) - Super Admin & Outlet Management
2. **POS Web** - Cashier & Order Management
3. **POS Android** - Mobile Cashier App
4. **Kitchen Display System** (Web/Tablet)
5. **QR Order** (Web) - Customer Self-Order
6. **Kitchen Printer** Integration

### Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12 (API) |
| Database | PostgreSQL / MySQL |
| Admin Dashboard | Laravel + Inertia + Vue 3 / React |
| POS Web | Vue 3 / React (SPA) |
| POS Android | Flutter |
| KDS | Vue 3 / React (PWA) |
| QR Order Web | Vue 3 / React (PWA) |
| Real-time | Laravel Reverb / Pusher |
| Queue | Laravel Horizon + Redis |
| Storage | S3 / Local |
| Payment Gateway | Xendit (Optional Integration) |

---

## Phase Breakdown

```
┌─────────────────────────────────────────────────────────────────┐
│  PHASE 1: Foundation & Multi-tenant Setup (3-4 minggu)         │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 2: Inventory, Stock & Recipe (3-4 minggu)               │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 3: Product & Menu Management (2-3 minggu)               │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 4: POS Core - Order & Transaction (3-4 minggu)          │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 5: Table Management & Floor Plan (2 minggu)             │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 6: Kitchen System - KDS & Printer (2-3 minggu)          │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 7: QR Order (Customer Self-Order) (2 minggu)            │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 8: Waiter App - Flutter (2-3 minggu)                    │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 9: Reporting & Analytics (2 minggu)                     │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 10: Payment Gateway Integration (1-2 minggu)            │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 11: POS Android App (3-4 minggu)                        │
├─────────────────────────────────────────────────────────────────┤
│  PHASE 12: Polish, Testing & Deployment (2 minggu)             │
└─────────────────────────────────────────────────────────────────┘

Total Estimasi: 27-37 minggu (7-9 bulan)
```

### Dependency Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                    CORRECT DEPENDENCY FLOW                       │
└─────────────────────────────────────────────────────────────────┘

Phase 1: Foundation
    │
    ▼
Phase 2: Inventory & Recipe
    │   • Units, Suppliers
    │   • Inventory Items (Raw Material, Ingredients)
    │   • Recipe / BOM
    │   • Stock Management
    │
    ▼
Phase 3: Product & Menu ◄─────── Link to Recipe
    │   • Categories
    │   • Products (linked to recipe_id)
    │   • Variants & Modifiers
    │   • Calculate food cost from recipe
    │
    ▼
Phase 4: POS Core ◄─────── Auto Deduct Stock
    │   • Create Order
    │   • Order triggers recipe lookup
    │   • Auto deduct inventory based on BOM
    │
    ▼
Phase 5-12: Other features
```

---

## PHASE 1: Foundation & Multi-tenant Setup
**Durasi: 3-4 minggu**

### 1.1 Database Architecture (Multi-tenant)

```
┌─────────────────────────────────────────────────────────────┐
│                    TENANT / BRAND                           │
│  (Satu brand bisa punya banyak outlet)                     │
└─────────────────────────┬───────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          ▼               ▼               ▼
    ┌──────────┐    ┌──────────┐    ┌──────────┐
    │ Outlet 1 │    │ Outlet 2 │    │ Outlet 3 │
    │ (Cabang) │    │ (Cabang) │    │ (Cabang) │
    └──────────┘    └──────────┘    └──────────┘
```

### 1.2 Core Tables

```sql
-- =====================================================
-- TENANT & OUTLET MANAGEMENT
-- =====================================================

CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    
    -- Contact
    email VARCHAR(255),
    phone VARCHAR(20),
    
    -- Settings
    currency VARCHAR(10) DEFAULT 'IDR',
    timezone VARCHAR(50) DEFAULT 'Asia/Jakarta',
    tax_percentage DECIMAL(5,2) DEFAULT 11.00,
    service_charge_percentage DECIMAL(5,2) DEFAULT 0,
    
    -- Subscription
    subscription_plan VARCHAR(50) DEFAULT 'free',
    subscription_expires_at TIMESTAMP,
    max_outlets INT DEFAULT 1,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE outlets (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    
    -- Location
    address TEXT,
    city VARCHAR(100),
    province VARCHAR(100),
    postal_code VARCHAR(10),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    
    -- Contact
    phone VARCHAR(20),
    email VARCHAR(255),
    
    -- Operational
    opening_time TIME DEFAULT '08:00',
    closing_time TIME DEFAULT '22:00',
    
    -- Settings (override tenant)
    tax_percentage DECIMAL(5,2),
    service_charge_percentage DECIMAL(5,2),
    
    -- Receipt Settings
    receipt_header TEXT,
    receipt_footer TEXT,
    receipt_show_logo BOOLEAN DEFAULT TRUE,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, code)
);

-- =====================================================
-- USER & ROLE MANAGEMENT
-- =====================================================

CREATE TABLE roles (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID REFERENCES tenants(id), -- NULL = system role
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    is_system BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, slug)
);

CREATE TABLE permissions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    module VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    role_id UUID REFERENCES roles(id) ON DELETE CASCADE,
    permission_id UUID REFERENCES permissions(id) ON DELETE CASCADE,
    PRIMARY KEY (role_id, permission_id)
);

CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID REFERENCES tenants(id),
    
    -- Auth
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    pin VARCHAR(6), -- untuk quick login di POS
    
    -- Profile
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    avatar VARCHAR(255),
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    email_verified_at TIMESTAMP,
    last_login_at TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    role_id UUID REFERENCES roles(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, role_id)
);

CREATE TABLE user_outlets (
    user_id UUID REFERENCES users(id) ON DELETE CASCADE,
    outlet_id UUID REFERENCES outlets(id) ON DELETE CASCADE,
    is_default BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (user_id, outlet_id)
);

-- =====================================================
-- INDEXES
-- =====================================================

CREATE INDEX idx_outlets_tenant ON outlets(tenant_id);
CREATE INDEX idx_users_tenant ON users(tenant_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_user_outlets_user ON user_outlets(user_id);
CREATE INDEX idx_user_outlets_outlet ON user_outlets(outlet_id);
```

### 1.3 Default Roles & Permissions

```sql
-- System Roles
INSERT INTO roles (name, slug, is_system) VALUES
('Super Admin', 'super-admin', TRUE),
('Tenant Owner', 'tenant-owner', TRUE),
('Outlet Manager', 'outlet-manager', TRUE),
('Cashier', 'cashier', TRUE),
('Waiter', 'waiter', TRUE),
('Kitchen Staff', 'kitchen-staff', TRUE);

-- Permissions
INSERT INTO permissions (name, slug, module) VALUES
-- Dashboard
('View Dashboard', 'dashboard.view', 'dashboard'),
('View Analytics', 'analytics.view', 'dashboard'),

-- User Management
('View Users', 'users.view', 'users'),
('Create Users', 'users.create', 'users'),
('Edit Users', 'users.edit', 'users'),
('Delete Users', 'users.delete', 'users'),

-- Outlet Management
('View Outlets', 'outlets.view', 'outlets'),
('Create Outlets', 'outlets.create', 'outlets'),
('Edit Outlets', 'outlets.edit', 'outlets'),
('Delete Outlets', 'outlets.delete', 'outlets'),

-- Products
('View Products', 'products.view', 'products'),
('Create Products', 'products.create', 'products'),
('Edit Products', 'products.edit', 'products'),
('Delete Products', 'products.delete', 'products'),
('Manage Categories', 'categories.manage', 'products'),

-- Orders
('Create Orders', 'orders.create', 'orders'),
('View Orders', 'orders.view', 'orders'),
('Edit Orders', 'orders.edit', 'orders'),
('Cancel Orders', 'orders.cancel', 'orders'),
('Apply Discounts', 'orders.discount', 'orders'),
('Void Orders', 'orders.void', 'orders'),

-- Payments
('Process Payments', 'payments.process', 'payments'),
('View Payments', 'payments.view', 'payments'),
('Refund Payments', 'payments.refund', 'payments'),

-- Tables
('Manage Tables', 'tables.manage', 'tables'),
('View Tables', 'tables.view', 'tables'),

-- Kitchen
('View KDS', 'kds.view', 'kitchen'),
('Update KDS', 'kds.update', 'kitchen'),

-- Reports
('View Reports', 'reports.view', 'reports'),
('Export Reports', 'reports.export', 'reports'),

-- Settings
('Manage Settings', 'settings.manage', 'settings');
```

### 1.4 Admin Dashboard Features (Phase 1)

| Feature | Description |
|---------|-------------|
| Login/Register | Multi-tenant aware authentication |
| Tenant Management | CRUD tenant (Super Admin only) |
| Outlet Management | CRUD outlet per tenant |
| User Management | CRUD users with role assignment |
| Role & Permission | Manage roles and permissions |
| Profile Settings | Update profile, change password |

### 1.5 API Endpoints (Phase 1)

```
Auth
├── POST   /api/auth/register
├── POST   /api/auth/login
├── POST   /api/auth/logout
├── POST   /api/auth/refresh
├── GET    /api/auth/me
└── POST   /api/auth/pin-login

Tenants (Super Admin)
├── GET    /api/tenants
├── POST   /api/tenants
├── GET    /api/tenants/{id}
├── PUT    /api/tenants/{id}
└── DELETE /api/tenants/{id}

Outlets
├── GET    /api/outlets
├── POST   /api/outlets
├── GET    /api/outlets/{id}
├── PUT    /api/outlets/{id}
└── DELETE /api/outlets/{id}

Users
├── GET    /api/users
├── POST   /api/users
├── GET    /api/users/{id}
├── PUT    /api/users/{id}
├── DELETE /api/users/{id}
└── POST   /api/users/{id}/assign-outlets

Roles
├── GET    /api/roles
├── POST   /api/roles
├── GET    /api/roles/{id}
├── PUT    /api/roles/{id}
├── DELETE /api/roles/{id}
└── POST   /api/roles/{id}/permissions
```

---

## PHASE 3: Product & Menu Management
**Durasi: 2-3 minggu**

### 3.1 Database Schema

```sql
-- =====================================================
-- PRODUCT MANAGEMENT
-- =====================================================

CREATE TABLE categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    parent_id UUID REFERENCES categories(id),
    
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    color VARCHAR(7), -- untuk display di POS
    icon VARCHAR(50),
    sort_order INT DEFAULT 0,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, slug)
);

CREATE TABLE products (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    category_id UUID REFERENCES categories(id),
    
    -- Basic Info
    sku VARCHAR(100),
    barcode VARCHAR(100),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    
    -- Pricing
    price DECIMAL(15,2) NOT NULL DEFAULT 0,
    cost_price DECIMAL(15,2) DEFAULT 0,
    
    -- Tax & Service
    is_taxable BOOLEAN DEFAULT TRUE,
    is_service_charge BOOLEAN DEFAULT TRUE,
    
    -- Type
    product_type VARCHAR(20) DEFAULT 'single', -- single, variant, combo
    
    -- Kitchen
    kitchen_station_id UUID, -- akan di-reference nanti
    preparation_time INT DEFAULT 0, -- dalam menit
    
    -- Display
    color VARCHAR(7),
    sort_order INT DEFAULT 0,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_available BOOLEAN DEFAULT TRUE, -- bisa di-toggle kalau habis
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, sku)
);

-- Produk availability per outlet
CREATE TABLE product_outlets (
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    outlet_id UUID REFERENCES outlets(id) ON DELETE CASCADE,
    
    price DECIMAL(15,2), -- override price per outlet (NULL = use default)
    is_available BOOLEAN DEFAULT TRUE,
    
    PRIMARY KEY (product_id, outlet_id)
);

-- =====================================================
-- VARIANT MANAGEMENT
-- =====================================================

-- Variant Groups (e.g., Size, Ice Level)
CREATE TABLE variant_groups (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100),
    is_required BOOLEAN DEFAULT FALSE,
    allow_multiple BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Variant Options (e.g., Small, Medium, Large)
CREATE TABLE variant_options (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    variant_group_id UUID NOT NULL REFERENCES variant_groups(id) ON DELETE CASCADE,
    
    name VARCHAR(100) NOT NULL,
    price_adjustment DECIMAL(15,2) DEFAULT 0, -- + atau -
    sort_order INT DEFAULT 0,
    is_default BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Link Product to Variant Group
CREATE TABLE product_variant_groups (
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    variant_group_id UUID REFERENCES variant_groups(id) ON DELETE CASCADE,
    sort_order INT DEFAULT 0,
    PRIMARY KEY (product_id, variant_group_id)
);

-- =====================================================
-- MODIFIER / ADD-ON MANAGEMENT
-- =====================================================

CREATE TABLE modifier_groups (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(100),
    
    is_required BOOLEAN DEFAULT FALSE,
    allow_multiple BOOLEAN DEFAULT TRUE,
    min_selection INT DEFAULT 0,
    max_selection INT, -- NULL = unlimited
    
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE modifiers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    modifier_group_id UUID NOT NULL REFERENCES modifier_groups(id) ON DELETE CASCADE,
    
    name VARCHAR(100) NOT NULL,
    price DECIMAL(15,2) DEFAULT 0,
    
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Link Product to Modifier Group
CREATE TABLE product_modifier_groups (
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    modifier_group_id UUID REFERENCES modifier_groups(id) ON DELETE CASCADE,
    sort_order INT DEFAULT 0,
    PRIMARY KEY (product_id, modifier_group_id)
);

-- =====================================================
-- COMBO / BUNDLE
-- =====================================================

CREATE TABLE combo_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    combo_product_id UUID NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    product_id UUID REFERENCES products(id) ON DELETE CASCADE,
    category_id UUID REFERENCES categories(id), -- pilih dari kategori
    
    quantity INT DEFAULT 1,
    price_adjustment DECIMAL(15,2) DEFAULT 0,
    is_required BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- INDEXES
-- =====================================================

CREATE INDEX idx_categories_tenant ON categories(tenant_id);
CREATE INDEX idx_categories_parent ON categories(parent_id);
CREATE INDEX idx_products_tenant ON products(tenant_id);
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_sku ON products(tenant_id, sku);
CREATE INDEX idx_product_outlets_outlet ON product_outlets(outlet_id);
CREATE INDEX idx_modifiers_group ON modifiers(modifier_group_id);
```

### 3.2 Admin Dashboard Features (Phase 3)

| Feature | Description |
|---------|-------------|
| Category Management | CRUD kategori dengan parent-child |
| Product Management | CRUD produk dengan image upload |
| Variant Groups | Setup variant (Size, Level, etc) |
| Modifier Groups | Setup modifier/addon |
| Product Variants | Assign variant ke produk |
| Product Modifiers | Assign modifier ke produk |
| Outlet Availability | Set produk per outlet |
| Bulk Import/Export | Import produk via Excel |

### 3.3 API Endpoints (Phase 3)

```
Categories
├── GET    /api/categories
├── POST   /api/categories
├── GET    /api/categories/{id}
├── PUT    /api/categories/{id}
├── DELETE /api/categories/{id}
└── POST   /api/categories/reorder

Products
├── GET    /api/products
├── POST   /api/products
├── GET    /api/products/{id}
├── PUT    /api/products/{id}
├── DELETE /api/products/{id}
├── POST   /api/products/{id}/variants
├── POST   /api/products/{id}/modifiers
├── POST   /api/products/{id}/outlets
├── PATCH  /api/products/{id}/toggle-availability
└── POST   /api/products/import

Variant Groups
├── GET    /api/variant-groups
├── POST   /api/variant-groups
├── GET    /api/variant-groups/{id}
├── PUT    /api/variant-groups/{id}
└── DELETE /api/variant-groups/{id}

Modifier Groups
├── GET    /api/modifier-groups
├── POST   /api/modifier-groups
├── GET    /api/modifier-groups/{id}
├── PUT    /api/modifier-groups/{id}
└── DELETE /api/modifier-groups/{id}
```

---

## PHASE 4: POS Core - Order & Transaction
**Durasi: 3-4 minggu**

### 4.1 Database Schema

```sql
-- =====================================================
-- SHIFT & CASH DRAWER MANAGEMENT
-- =====================================================

CREATE TABLE shifts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    user_id UUID NOT NULL REFERENCES users(id),
    
    -- Shift Info
    shift_number VARCHAR(50) NOT NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP,
    
    -- Cash Drawer
    opening_cash DECIMAL(15,2) NOT NULL DEFAULT 0,
    closing_cash DECIMAL(15,2),
    
    -- Calculated
    expected_cash DECIMAL(15,2),
    actual_cash DECIMAL(15,2),
    difference DECIMAL(15,2),
    
    -- Summary
    total_sales DECIMAL(15,2) DEFAULT 0,
    total_orders INT DEFAULT 0,
    total_void INT DEFAULT 0,
    total_discount DECIMAL(15,2) DEFAULT 0,
    
    notes TEXT,
    status VARCHAR(20) DEFAULT 'open', -- open, closed
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cash_drawer_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    shift_id UUID NOT NULL REFERENCES shifts(id),
    user_id UUID NOT NULL REFERENCES users(id),
    
    type VARCHAR(20) NOT NULL, -- cash_in, cash_out, adjustment
    amount DECIMAL(15,2) NOT NULL,
    reason TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- ORDER MANAGEMENT
-- =====================================================

CREATE TABLE orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    shift_id UUID REFERENCES shifts(id),
    
    -- Order Info
    order_number VARCHAR(50) NOT NULL,
    order_type VARCHAR(20) NOT NULL DEFAULT 'dine_in', -- dine_in, takeaway, delivery, qr_order
    
    -- Customer (optional)
    customer_id UUID,
    customer_name VARCHAR(255),
    customer_phone VARCHAR(20),
    
    -- Table (for dine_in)
    table_id UUID,
    table_name VARCHAR(50),
    guests INT DEFAULT 1,
    
    -- Pricing
    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    discount_type VARCHAR(20), -- percentage, fixed
    discount_value DECIMAL(15,2),
    discount_reason TEXT,
    
    tax_amount DECIMAL(15,2) DEFAULT 0,
    service_charge_amount DECIMAL(15,2) DEFAULT 0,
    
    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    
    -- Rounding
    rounding_amount DECIMAL(15,2) DEFAULT 0,
    grand_total DECIMAL(15,2) NOT NULL DEFAULT 0,
    
    -- Status
    status VARCHAR(20) DEFAULT 'pending', 
    -- pending, confirmed, preparing, ready, completed, cancelled, void
    
    kitchen_status VARCHAR(20) DEFAULT 'waiting',
    -- waiting, cooking, partial, completed
    
    payment_status VARCHAR(20) DEFAULT 'unpaid',
    -- unpaid, partial, paid, refunded
    
    -- Source
    source VARCHAR(20) DEFAULT 'pos', -- pos, qr_order, waiter_app
    
    -- Staff
    created_by UUID REFERENCES users(id),
    served_by UUID REFERENCES users(id),
    cancelled_by UUID REFERENCES users(id),
    cancelled_reason TEXT,
    
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP
);

CREATE TABLE order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id UUID NOT NULL REFERENCES products(id),
    
    -- Product Snapshot
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    
    -- Quantity & Price
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(15,2) NOT NULL,
    
    -- Variant
    variant_info JSONB, -- snapshot variant yang dipilih
    variant_price_adjustment DECIMAL(15,2) DEFAULT 0,
    
    -- Modifiers
    modifiers JSONB, -- snapshot modifiers yang dipilih
    modifiers_total DECIMAL(15,2) DEFAULT 0,
    
    -- Calculated
    subtotal DECIMAL(15,2) NOT NULL,
    
    -- Discount (item level)
    discount_amount DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) NOT NULL,
    
    -- Kitchen
    kitchen_status VARCHAR(20) DEFAULT 'waiting',
    -- waiting, cooking, ready, served, cancelled
    
    kitchen_station_id UUID,
    sent_to_kitchen_at TIMESTAMP,
    ready_at TIMESTAMP,
    served_at TIMESTAMP,
    
    -- Notes
    notes TEXT,
    is_priority BOOLEAN DEFAULT FALSE,
    
    -- Void
    is_void BOOLEAN DEFAULT FALSE,
    void_reason TEXT,
    void_by UUID REFERENCES users(id),
    void_at TIMESTAMP,
    
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- PAYMENT MANAGEMENT
-- =====================================================

CREATE TABLE payment_methods (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL,
    type VARCHAR(20) NOT NULL, -- cash, card, ewallet, qris, transfer, other
    
    -- Settings
    is_active BOOLEAN DEFAULT TRUE,
    requires_reference BOOLEAN DEFAULT FALSE, -- untuk EDC, transfer
    
    -- Gateway Integration
    gateway_provider VARCHAR(50), -- xendit, midtrans, etc
    gateway_config JSONB,
    
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, code)
);

CREATE TABLE payments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(id),
    payment_method_id UUID NOT NULL REFERENCES payment_methods(id),
    shift_id UUID REFERENCES shifts(id),
    
    -- Amount
    amount DECIMAL(15,2) NOT NULL,
    change_amount DECIMAL(15,2) DEFAULT 0, -- kembalian untuk cash
    
    -- Reference
    reference_number VARCHAR(100),
    
    -- Gateway
    gateway_transaction_id VARCHAR(255),
    gateway_response JSONB,
    
    -- Status
    status VARCHAR(20) DEFAULT 'pending', -- pending, success, failed, refunded
    
    paid_at TIMESTAMP,
    created_by UUID REFERENCES users(id),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- DISCOUNT & PROMO
-- =====================================================

CREATE TABLE discounts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50),
    
    type VARCHAR(20) NOT NULL, -- percentage, fixed
    value DECIMAL(15,2) NOT NULL,
    
    -- Scope
    applies_to VARCHAR(20) DEFAULT 'order', -- order, category, product
    applicable_ids UUID[], -- product_ids atau category_ids
    
    -- Conditions
    min_order_amount DECIMAL(15,2),
    max_discount_amount DECIMAL(15,2),
    
    -- Validity
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    
    -- Limits
    usage_limit INT,
    usage_count INT DEFAULT 0,
    per_customer_limit INT,
    
    -- Days & Hours
    valid_days INT[], -- 0=Sunday, 6=Saturday
    valid_start_time TIME,
    valid_end_time TIME,
    
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- INDEXES
-- =====================================================

CREATE INDEX idx_shifts_outlet ON shifts(outlet_id);
CREATE INDEX idx_shifts_user ON shifts(user_id);
CREATE INDEX idx_shifts_status ON shifts(status);

CREATE INDEX idx_orders_outlet ON orders(outlet_id);
CREATE INDEX idx_orders_shift ON orders(shift_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_orders_number ON orders(order_number);

CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_order_items_product ON order_items(product_id);
CREATE INDEX idx_order_items_kitchen ON order_items(kitchen_status);

CREATE INDEX idx_payments_order ON payments(order_id);
CREATE INDEX idx_payments_method ON payments(payment_method_id);
```

### 4.2 POS Web Features (Phase 4)

| Feature | Description |
|---------|-------------|
| Shift Management | Open/close shift, cash drawer |
| Menu Display | Grid view dengan kategori |
| Cart Management | Add, edit, remove items |
| Variant Selection | Popup variant selection |
| Modifier Selection | Popup modifier selection |
| Order Notes | Add notes per item |
| Discount | Apply discount (order/item level) |
| Hold Order | Save order for later |
| Payment | Multi-payment support |
| Receipt | Print / WhatsApp receipt |
| Order History | View orders in shift |

### 4.3 API Endpoints (Phase 4)

```
Shifts
├── POST   /api/shifts/open
├── POST   /api/shifts/close
├── GET    /api/shifts/current
├── GET    /api/shifts/{id}
├── POST   /api/shifts/{id}/cash-in
├── POST   /api/shifts/{id}/cash-out
└── GET    /api/shifts/{id}/summary

Orders
├── GET    /api/orders
├── POST   /api/orders
├── GET    /api/orders/{id}
├── PUT    /api/orders/{id}
├── POST   /api/orders/{id}/items
├── PUT    /api/orders/{id}/items/{itemId}
├── DELETE /api/orders/{id}/items/{itemId}
├── POST   /api/orders/{id}/discount
├── POST   /api/orders/{id}/confirm
├── POST   /api/orders/{id}/cancel
├── POST   /api/orders/{id}/void
└── POST   /api/orders/{id}/complete

Payments
├── POST   /api/orders/{id}/payments
├── GET    /api/orders/{id}/payments
└── POST   /api/orders/{id}/payments/{paymentId}/refund

Held Orders
├── GET    /api/held-orders
├── POST   /api/held-orders
├── GET    /api/held-orders/{id}
└── DELETE /api/held-orders/{id}

POS Data
├── GET    /api/pos/menu
├── GET    /api/pos/payment-methods
└── GET    /api/pos/discounts
```

### 4.4 Order Flow

```
┌─────────────────────────────────────────────────────────────┐
│                      ORDER LIFECYCLE                         │
└─────────────────────────────────────────────────────────────┘

[PENDING] ─────► [CONFIRMED] ─────► [PREPARING] ─────► [READY]
    │                │                   │                │
    │                │                   │                │
    ▼                ▼                   ▼                ▼
[CANCELLED]     Send to Kitchen     KDS Update      [COMPLETED]
                                                         │
                                                         ▼
                                                    [PAID] ✓

Payment Status:
[UNPAID] ─────► [PARTIAL] ─────► [PAID] ─────► [REFUNDED]
```

---

## PHASE 5: Table Management & Floor Plan
**Durasi: 2 minggu**

### 5.1 Database Schema

```sql
-- =====================================================
-- TABLE & FLOOR MANAGEMENT
-- =====================================================

CREATE TABLE floors (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    
    name VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE tables (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    floor_id UUID REFERENCES floors(id),
    
    name VARCHAR(50) NOT NULL,
    capacity INT DEFAULT 4,
    
    -- Position (for floor plan)
    position_x INT DEFAULT 0,
    position_y INT DEFAULT 0,
    width INT DEFAULT 100,
    height INT DEFAULT 100,
    shape VARCHAR(20) DEFAULT 'square', -- square, rectangle, circle
    rotation INT DEFAULT 0,
    
    -- Status
    status VARCHAR(20) DEFAULT 'available',
    -- available, occupied, reserved, billing, cleaning
    
    -- Current Order
    current_order_id UUID,
    occupied_at TIMESTAMP,
    
    -- QR Code
    qr_code VARCHAR(100) UNIQUE,
    
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE table_reservations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    table_id UUID NOT NULL REFERENCES tables(id),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    
    -- Customer Info
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(20),
    customer_email VARCHAR(255),
    
    -- Reservation Details
    reservation_date DATE NOT NULL,
    reservation_time TIME NOT NULL,
    duration_minutes INT DEFAULT 120,
    guests INT DEFAULT 2,
    
    -- Status
    status VARCHAR(20) DEFAULT 'pending',
    -- pending, confirmed, seated, completed, cancelled, no_show
    
    notes TEXT,
    
    -- Staff
    created_by UUID REFERENCES users(id),
    confirmed_by UUID REFERENCES users(id),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table Merge Session
CREATE TABLE table_merges (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    primary_table_id UUID NOT NULL REFERENCES tables(id),
    order_id UUID REFERENCES orders(id),
    
    merged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unmerged_at TIMESTAMP,
    
    created_by UUID REFERENCES users(id)
);

CREATE TABLE table_merge_items (
    merge_id UUID REFERENCES table_merges(id) ON DELETE CASCADE,
    table_id UUID REFERENCES tables(id),
    PRIMARY KEY (merge_id, table_id)
);

-- =====================================================
-- INDEXES
-- =====================================================

CREATE INDEX idx_floors_outlet ON floors(outlet_id);
CREATE INDEX idx_tables_outlet ON tables(outlet_id);
CREATE INDEX idx_tables_floor ON tables(floor_id);
CREATE INDEX idx_tables_status ON tables(status);
CREATE INDEX idx_reservations_outlet ON table_reservations(outlet_id);
CREATE INDEX idx_reservations_date ON table_reservations(reservation_date);
```

### 5.2 POS Features (Phase 5)

| Feature | Description |
|---------|-------------|
| Floor Plan View | Visual drag-drop layout |
| Table Status | Color-coded status |
| Merge Tables | Gabung beberapa meja |
| Split Tables | Pisahkan meja yang di-merge |
| Transfer Table | Pindah order ke meja lain |
| Table Timer | Durasi occupy |
| Reservation | Basic reservation system |
| Quick Actions | Change status, info |

### 5.3 API Endpoints (Phase 5)

```
Floors
├── GET    /api/floors
├── POST   /api/floors
├── PUT    /api/floors/{id}
└── DELETE /api/floors/{id}

Tables
├── GET    /api/tables
├── POST   /api/tables
├── GET    /api/tables/{id}
├── PUT    /api/tables/{id}
├── DELETE /api/tables/{id}
├── PATCH  /api/tables/{id}/status
├── POST   /api/tables/{id}/assign-order
├── POST   /api/tables/{id}/transfer
├── POST   /api/tables/merge
├── POST   /api/tables/unmerge/{mergeId}
└── POST   /api/tables/layout/save

Reservations
├── GET    /api/reservations
├── POST   /api/reservations
├── GET    /api/reservations/{id}
├── PUT    /api/reservations/{id}
├── DELETE /api/reservations/{id}
├── PATCH  /api/reservations/{id}/status
└── GET    /api/reservations/available-slots
```

---

## PHASE 6: Kitchen System - KDS & Printer
**Durasi: 2-3 minggu**

### 6.1 Database Schema

```sql
-- =====================================================
-- KITCHEN STATION MANAGEMENT
-- =====================================================

CREATE TABLE kitchen_stations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL,
    description TEXT,
    color VARCHAR(7),
    
    -- Display Settings
    display_mode VARCHAR(20) DEFAULT 'queue', -- queue, grid
    auto_accept BOOLEAN DEFAULT FALSE,
    show_item_notes BOOLEAN DEFAULT TRUE,
    
    -- Alert Settings
    warning_minutes INT DEFAULT 10, -- kuning
    critical_minutes INT DEFAULT 15, -- merah
    
    -- Printer
    printer_id UUID,
    print_on_receive BOOLEAN DEFAULT TRUE,
    
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Assign categories to station
CREATE TABLE station_categories (
    station_id UUID REFERENCES kitchen_stations(id) ON DELETE CASCADE,
    category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
    PRIMARY KEY (station_id, category_id)
);

-- =====================================================
-- KITCHEN DISPLAY QUEUE
-- =====================================================

CREATE TABLE kitchen_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(id),
    station_id UUID NOT NULL REFERENCES kitchen_stations(id),
    
    -- Order Info
    order_number VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    order_type VARCHAR(20),
    
    -- Status
    status VARCHAR(20) DEFAULT 'waiting',
    -- waiting, cooking, ready, completed, cancelled
    
    -- Timing
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP,
    ready_at TIMESTAMP,
    completed_at TIMESTAMP,
    
    -- Duration
    wait_seconds INT, -- calculated
    cook_seconds INT, -- calculated
    
    is_priority BOOLEAN DEFAULT FALSE,
    is_recalled BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE kitchen_order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kitchen_order_id UUID NOT NULL REFERENCES kitchen_orders(id) ON DELETE CASCADE,
    order_item_id UUID NOT NULL REFERENCES order_items(id),
    
    -- Item Info (snapshot)
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    variant_info TEXT,
    modifiers_text TEXT,
    notes TEXT,
    
    -- Status
    status VARCHAR(20) DEFAULT 'waiting',
    -- waiting, cooking, ready, served, cancelled
    
    is_priority BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- PRINTER MANAGEMENT
-- =====================================================

CREATE TABLE printers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    
    name VARCHAR(100) NOT NULL,
    type VARCHAR(20) NOT NULL, -- thermal, receipt, label
    connection_type VARCHAR(20) NOT NULL, -- network, usb, bluetooth
    
    -- Network
    ip_address VARCHAR(50),
    port INT DEFAULT 9100,
    
    -- USB/Bluetooth
    device_id VARCHAR(255),
    
    -- Settings
    paper_width INT DEFAULT 80, -- mm
    auto_cut BOOLEAN DEFAULT TRUE,
    
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Link station to printer
ALTER TABLE kitchen_stations 
ADD CONSTRAINT fk_station_printer 
FOREIGN KEY (printer_id) REFERENCES printers(id);

-- =====================================================
-- INDEXES
-- =====================================================

CREATE INDEX idx_stations_outlet ON kitchen_stations(outlet_id);
CREATE INDEX idx_kitchen_orders_station ON kitchen_orders(station_id);
CREATE INDEX idx_kitchen_orders_status ON kitchen_orders(status);
CREATE INDEX idx_kitchen_order_items_ko ON kitchen_order_items(kitchen_order_id);
```

### 6.2 KDS Features

| Feature | Description |
|---------|-------------|
| Order Queue | Real-time order display |
| Multi-Station | Pisah per station (Drink, Food, etc) |
| Color Coding | Status + timing warning |
| Bump Order | Mark item as done |
| Recall | Bring back bumped order |
| All Done | Complete entire order |
| Priority Flag | Mark urgent |
| Item Details | Variant, modifier, notes |
| Sound Alert | New order notification |

### 6.3 Printer Features

| Feature | Description |
|---------|-------------|
| Kitchen Chit | Print ke station printer |
| Receipt Print | Struk pembayaran |
| Bill Print | Pre-payment bill |
| Network Printer | ESC/POS over TCP |
| Multi-Printer | Per station assignment |

### 6.4 API Endpoints (Phase 6)

```
Kitchen Stations
├── GET    /api/kitchen-stations
├── POST   /api/kitchen-stations
├── PUT    /api/kitchen-stations/{id}
├── DELETE /api/kitchen-stations/{id}
└── POST   /api/kitchen-stations/{id}/categories

Kitchen Display (Real-time via WebSocket)
├── GET    /api/kds/orders
├── GET    /api/kds/orders/{id}
├── POST   /api/kds/orders/{id}/start
├── POST   /api/kds/orders/{id}/ready
├── POST   /api/kds/orders/{id}/complete
├── POST   /api/kds/orders/{id}/recall
├── POST   /api/kds/items/{id}/ready
└── POST   /api/kds/items/{id}/priority

Printers
├── GET    /api/printers
├── POST   /api/printers
├── PUT    /api/printers/{id}
├── DELETE /api/printers/{id}
├── POST   /api/printers/{id}/test
└── POST   /api/print/kitchen-chit
└── POST   /api/print/receipt
└── POST   /api/print/bill

WebSocket Events
├── kitchen.order.new
├── kitchen.order.updated
├── kitchen.order.completed
├── kitchen.item.updated
└── kitchen.order.recalled
```

### 6.5 Kitchen Flow

```
┌─────────────────────────────────────────────────────────────┐
│                     KITCHEN FLOW                             │
└─────────────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │   POS/QR     │
                    │  New Order   │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │ Order Split  │
                    │ by Station   │
                    └──────┬───────┘
                           │
        ┌──────────────────┼──────────────────┐
        │                  │                  │
        ▼                  ▼                  ▼
┌───────────────┐  ┌───────────────┐  ┌───────────────┐
│  Bar Station  │  │ Food Station  │  │Dessert Station│
│    (KDS 1)    │  │    (KDS 2)    │  │    (KDS 3)    │
└───────┬───────┘  └───────┬───────┘  └───────┬───────┘
        │                  │                  │
        │ Bump             │ Bump             │ Bump
        ▼                  ▼                  ▼
┌─────────────────────────────────────────────────────────────┐
│              All Items Ready → Notify Waiter                │
└─────────────────────────────────────────────────────────────┘
```

---

## PHASE 7: QR Order (Customer Self-Order)
**Durasi: 2 minggu**

### 7.1 Features

| Feature | Description |
|---------|-------------|
| Scan QR | Auto-detect outlet & table |
| Browse Menu | Category, search, filter |
| Product Detail | Image, description, variant |
| Cart | Add, remove, quantity |
| Variant/Modifier | Selection modal |
| Notes | Per item notes |
| Submit Order | Confirm & send to kitchen |
| Order Status | Track order real-time |
| Call Waiter | Request assistance |
| Bill Request | Request bill |

### 7.2 QR Code Format

```
https://qrorder.{domain}/{outlet_code}/{table_code}

Example:
https://qrorder.myresto.com/outlet-001/T05
```

### 7.3 Database Additions

```sql
-- Add QR session tracking
CREATE TABLE qr_sessions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    table_id UUID NOT NULL REFERENCES tables(id),
    
    session_token VARCHAR(100) UNIQUE NOT NULL,
    
    -- Customer (optional)
    customer_name VARCHAR(255),
    customer_phone VARCHAR(20),
    
    -- Current Order
    order_id UUID REFERENCES orders(id),
    
    -- Session
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP,
    
    status VARCHAR(20) DEFAULT 'active' -- active, closed
);

-- Call waiter requests
CREATE TABLE waiter_calls (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    table_id UUID NOT NULL REFERENCES tables(id),
    qr_session_id UUID REFERENCES qr_sessions(id),
    
    type VARCHAR(20) NOT NULL, -- assistance, bill, other
    message TEXT,
    
    status VARCHAR(20) DEFAULT 'pending', -- pending, acknowledged, completed
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP,
    acknowledged_by UUID REFERENCES users(id)
);
```

### 7.4 API Endpoints (Phase 7)

```
QR Order (Public - No Auth)
├── GET    /api/qr/{outlet_code}/{table_code}
├── POST   /api/qr/session
├── GET    /api/qr/menu
├── GET    /api/qr/menu/categories
├── GET    /api/qr/menu/products/{id}
├── POST   /api/qr/cart
├── GET    /api/qr/cart
├── PUT    /api/qr/cart/{itemId}
├── DELETE /api/qr/cart/{itemId}
├── POST   /api/qr/order/submit
├── GET    /api/qr/order/status
├── POST   /api/qr/call-waiter
└── POST   /api/qr/request-bill

WebSocket Events (QR → POS/KDS)
├── qr.order.submitted
├── qr.order.updated
└── qr.waiter.called
```

### 7.5 QR Order Flow

```
┌─────────────────────────────────────────────────────────────┐
│                     QR ORDER FLOW                            │
└─────────────────────────────────────────────────────────────┘

Customer                 Server                 POS/KDS
   │                        │                      │
   │ Scan QR                │                      │
   ├───────────────────────►│                      │
   │                        │                      │
   │◄──────────────────────┤│                      │
   │ Menu + Session         │                      │
   │                        │                      │
   │ Browse & Add to Cart   │                      │
   ├───────────────────────►│                      │
   │                        │                      │
   │ Submit Order           │                      │
   ├───────────────────────►│                      │
   │                        │ WebSocket Notify     │
   │                        ├─────────────────────►│
   │                        │                      │
   │◄──────────────────────┤│                      │
   │ Order Confirmed        │      New Order!      │
   │                        │                      │
   │ Track Status           │                      │
   ├───────────────────────►│◄─────────────────────┤
   │                        │ Status Update        │
   │◄──────────────────────┤│                      │
   │ Ready to Serve!        │                      │
```

---

## PHASE 8: Waiter App (Flutter)
**Durasi: 2-3 minggu**

### 8.1 Features Overview

| Feature | Description |
|---------|-------------|
| Login | PIN-based quick login |
| Table View | Lihat semua meja & status |
| Take Order | Input order langsung dari meja |
| Order Status | Lihat status pesanan real-time |
| Add Items | Tambah item ke order existing |
| Transfer Table | Pindah order ke meja lain |
| Split Bill | Pisah bill per item/person |
| Request Bill | Generate bill untuk meja |
| Call Kitchen | Notify kitchen untuk rush order |
| Notifications | Alert order ready, customer call |
| Quick Actions | Mark served, clear table |

### 8.2 Database Additions

```sql
-- =====================================================
-- WAITER ASSIGNMENT & ACTIVITY
-- =====================================================

-- Assign waiter to tables/sections
CREATE TABLE waiter_sections (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    user_id UUID NOT NULL REFERENCES users(id),
    shift_id UUID REFERENCES shifts(id),
    
    -- Assignment
    assigned_tables UUID[], -- array of table_ids
    assigned_floors UUID[], -- atau assign per floor
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Waiter Activity Log
CREATE TABLE waiter_activities (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    user_id UUID NOT NULL REFERENCES users(id),
    shift_id UUID REFERENCES shifts(id),
    
    -- Activity
    activity_type VARCHAR(50) NOT NULL,
    -- take_order, add_item, transfer_table, serve_item, 
    -- request_bill, clear_table, call_kitchen
    
    -- References
    order_id UUID REFERENCES orders(id),
    table_id UUID REFERENCES tables(id),
    order_item_id UUID REFERENCES order_items(id),
    
    -- Details
    details JSONB,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Split Bill Sessions
CREATE TABLE split_bills (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    order_id UUID NOT NULL REFERENCES orders(id),
    
    split_type VARCHAR(20) NOT NULL, -- equal, by_item, by_person, custom
    split_count INT DEFAULT 2,
    
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE split_bill_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    split_bill_id UUID NOT NULL REFERENCES split_bills(id) ON DELETE CASCADE,
    
    split_number INT NOT NULL, -- Bill 1, Bill 2, etc
    order_item_id UUID REFERENCES order_items(id),
    
    -- Custom amount (for custom split)
    custom_amount DECIMAL(15,2),
    
    -- Payment
    is_paid BOOLEAN DEFAULT FALSE,
    payment_id UUID REFERENCES payments(id)
);

-- =====================================================
-- INDEXES
-- =====================================================

CREATE INDEX idx_waiter_sections_outlet ON waiter_sections(outlet_id);
CREATE INDEX idx_waiter_sections_user ON waiter_sections(user_id);
CREATE INDEX idx_waiter_activities_user ON waiter_activities(user_id);
CREATE INDEX idx_waiter_activities_order ON waiter_activities(order_id);
CREATE INDEX idx_split_bills_order ON split_bills(order_id);
```

### 8.3 API Endpoints (Waiter App)

```
Waiter Auth
├── POST   /api/waiter/login          # PIN login
├── POST   /api/waiter/logout
└── GET    /api/waiter/me

Tables (Waiter View)
├── GET    /api/waiter/tables         # Tables assigned to waiter
├── GET    /api/waiter/tables/{id}    # Table detail with current order
├── PATCH  /api/waiter/tables/{id}/status
└── POST   /api/waiter/tables/{id}/clear

Orders (Waiter)
├── GET    /api/waiter/orders                    # Active orders
├── POST   /api/waiter/orders                    # Create new order
├── GET    /api/waiter/orders/{id}               # Order detail
├── POST   /api/waiter/orders/{id}/items         # Add items
├── PUT    /api/waiter/orders/{id}/items/{itemId}
├── DELETE /api/waiter/orders/{id}/items/{itemId}
├── POST   /api/waiter/orders/{id}/send-kitchen  # Send/resend to kitchen
├── POST   /api/waiter/orders/{id}/rush          # Mark as rush/priority
├── POST   /api/waiter/orders/{id}/transfer      # Transfer to another table
└── POST   /api/waiter/orders/{id}/serve/{itemId} # Mark item as served

Split Bill
├── POST   /api/waiter/orders/{id}/split         # Create split bill
├── GET    /api/waiter/orders/{id}/split         # Get split details
├── PUT    /api/waiter/orders/{id}/split         # Update split
└── DELETE /api/waiter/orders/{id}/split         # Cancel split

Bill & Payment
├── POST   /api/waiter/orders/{id}/print-bill    # Print/generate bill
├── POST   /api/waiter/orders/{id}/request-payment # Notify cashier
└── GET    /api/waiter/orders/{id}/bill-preview  # Preview bill

Notifications
├── GET    /api/waiter/notifications
├── PATCH  /api/waiter/notifications/{id}/read
└── POST   /api/waiter/notifications/mark-all-read

WebSocket Events (Real-time)
├── waiter.order.ready          # Kitchen completed
├── waiter.order.item_ready     # Specific item ready
├── waiter.table.customer_call  # Customer request via QR
├── waiter.table.bill_request   # Customer request bill
└── waiter.order.updated        # Order changed by others
```

### 8.4 Flutter Project Structure (Waiter App)

```
lib/
├── core/
│   ├── config/
│   ├── network/
│   ├── storage/
│   └── theme/
│
├── data/
│   ├── models/
│   │   ├── user.dart
│   │   ├── table.dart
│   │   ├── order.dart
│   │   ├── order_item.dart
│   │   ├── product.dart
│   │   ├── notification.dart
│   │   └── split_bill.dart
│   ├── repositories/
│   │   ├── auth_repository.dart
│   │   ├── table_repository.dart
│   │   ├── order_repository.dart
│   │   └── notification_repository.dart
│   └── providers/
│       ├── auth_provider.dart
│       ├── table_provider.dart
│       ├── order_provider.dart
│       ├── cart_provider.dart
│       └── notification_provider.dart
│
├── features/
│   ├── auth/
│   │   └── screens/
│   │       ├── pin_login_screen.dart
│   │       └── outlet_select_screen.dart
│   │
│   ├── home/
│   │   └── screens/
│   │       └── home_screen.dart       # Main dashboard
│   │
│   ├── tables/
│   │   ├── screens/
│   │   │   ├── tables_screen.dart     # Grid/list view
│   │   │   └── table_detail_screen.dart
│   │   └── widgets/
│   │       ├── table_card.dart
│   │       ├── table_status_badge.dart
│   │       └── floor_selector.dart
│   │
│   ├── orders/
│   │   ├── screens/
│   │   │   ├── take_order_screen.dart
│   │   │   ├── order_detail_screen.dart
│   │   │   ├── add_items_screen.dart
│   │   │   └── order_history_screen.dart
│   │   └── widgets/
│   │       ├── menu_grid.dart
│   │       ├── category_tabs.dart
│   │       ├── product_card.dart
│   │       ├── cart_summary.dart
│   │       ├── order_item_card.dart
│   │       ├── variant_bottom_sheet.dart
│   │       └── modifier_bottom_sheet.dart
│   │
│   ├── bill/
│   │   ├── screens/
│   │   │   ├── bill_preview_screen.dart
│   │   │   └── split_bill_screen.dart
│   │   └── widgets/
│   │       ├── bill_item_row.dart
│   │       ├── split_type_selector.dart
│   │       └── split_assignment.dart
│   │
│   ├── kitchen/
│   │   └── screens/
│   │       └── kitchen_status_screen.dart  # View order status
│   │
│   └── notifications/
│       ├── screens/
│       │   └── notifications_screen.dart
│       └── widgets/
│           └── notification_card.dart
│
├── services/
│   ├── websocket_service.dart
│   ├── notification_service.dart
│   ├── printer_service.dart          # Optional: portable printer
│   └── sound_service.dart            # Alert sounds
│
├── widgets/
│   ├── app_bottom_nav.dart
│   ├── notification_badge.dart
│   ├── quick_action_button.dart
│   ├── status_indicator.dart
│   └── empty_state.dart
│
└── main.dart
```

### 8.5 Key Screens Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    WAITER APP FLOW                           │
└─────────────────────────────────────────────────────────────┘

                    ┌──────────────┐
                    │  PIN Login   │
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │   Home /     │
                    │ Table View   │◄─────────────────────┐
                    └──────┬───────┘                      │
                           │                              │
         ┌─────────────────┼─────────────────┐           │
         │                 │                 │           │
         ▼                 ▼                 ▼           │
┌─────────────┐    ┌─────────────┐    ┌───────────┐     │
│ Empty Table │    │  Occupied   │    │  Notif    │     │
│ Take Order  │    │  Add Items  │    │  Center   │     │
└──────┬──────┘    └──────┬──────┘    └───────────┘     │
       │                  │                              │
       └────────┬─────────┘                              │
                │                                        │
         ┌──────▼───────┐                               │
         │  Order Cart  │                               │
         │   Screen     │                               │
         └──────┬───────┘                               │
                │                                        │
         ┌──────▼───────┐      ┌──────────────┐        │
         │ Send Kitchen │─────►│ Order Active │────────┘
         └──────────────┘      └──────┬───────┘
                                      │
                    ┌─────────────────┼─────────────────┐
                    │                 │                 │
                    ▼                 ▼                 ▼
             ┌───────────┐    ┌───────────┐    ┌───────────┐
             │  Serve    │    │  Request  │    │   Split   │
             │  Items    │    │   Bill    │    │   Bill    │
             └───────────┘    └─────┬─────┘    └───────────┘
                                    │
                             ┌──────▼───────┐
                             │   Payment    │
                             │  (Cashier)   │
                             └──────┬───────┘
                                    │
                             ┌──────▼───────┐
                             │ Clear Table  │
                             └──────────────┘
```

### 8.6 Key Features Detail

#### A. Table View
```
┌─────────────────────────────────────────┐
│  FLOOR: Main Hall    [Floor 2 ▼]        │
├─────────────────────────────────────────┤
│                                         │
│  ┌─────┐  ┌─────┐  ┌─────┐  ┌─────┐   │
│  │ T1  │  │ T2  │  │ T3  │  │ T4  │   │
│  │ 🟢  │  │ 🟡  │  │ 🔴  │  │ 🟢  │   │
│  │     │  │ 2👤 │  │ 4👤 │  │     │   │
│  └─────┘  └─────┘  └─────┘  └─────┘   │
│                                         │
│  ┌─────┐  ┌─────┐  ┌─────┐  ┌─────┐   │
│  │ T5  │  │ T6  │  │ T7  │  │ T8  │   │
│  │ 🟡  │  │ 🔵  │  │ 🟢  │  │ 🟡  │   │
│  │ 3👤 │  │ RSV │  │     │  │ 2👤 │   │
│  └─────┘  └─────┘  └─────┘  └─────┘   │
│                                         │
├─────────────────────────────────────────┤
│ 🟢 Available  🟡 Occupied  🔴 Billing   │
│ 🔵 Reserved   ⚫ Cleaning               │
└─────────────────────────────────────────┘

Legend:
- 👤 = Guest count
- Tap table = Open detail/action
```

#### B. Take Order Screen
```
┌─────────────────────────────────────────┐
│ ← Table T3 (4 guests)        🛒 Cart(3) │
├─────────────────────────────────────────┤
│ [All] [Food] [Beverage] [Dessert]       │
├─────────────────────────────────────────┤
│                                         │
│  ┌────────┐  ┌────────┐  ┌────────┐   │
│  │  🍔    │  │  🍕    │  │  🍝    │   │
│  │Burger  │  │ Pizza  │  │ Pasta  │   │
│  │ 45.000 │  │ 65.000 │  │ 55.000 │   │
│  └────────┘  └────────┘  └────────┘   │
│                                         │
│  ┌────────┐  ┌────────┐  ┌────────┐   │
│  │  🥤    │  │  ☕    │  │  🧃    │   │
│  │  Cola  │  │ Coffee │  │ Juice  │   │
│  │ 15.000 │  │ 25.000 │  │ 20.000 │   │
│  └────────┘  └────────┘  └────────┘   │
│                                         │
├─────────────────────────────────────────┤
│ ┌─────────────────────────────────────┐ │
│ │ 🛒 3 items          Total: 125.000  │ │
│ │         [ Send to Kitchen 🔔 ]      │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

#### C. Order Detail / Table Detail
```
┌─────────────────────────────────────────┐
│ ← Table T3                    ⏱️ 45min  │
├─────────────────────────────────────────┤
│ Order #ORD-0042        Status: Cooking  │
│ Guests: 4              Waiter: John     │
├─────────────────────────────────────────┤
│                                         │
│ 🍔 Beef Burger          x2    🟡 Cook   │
│    + Extra Cheese                       │
│    + No Onion                           │
│                                         │
│ 🍕 Pepperoni Pizza      x1    🟢 Ready  │
│    Size: Large                          │
│                                         │
│ 🥤 Coca Cola            x3    ✅ Served │
│                                         │
├─────────────────────────────────────────┤
│ Subtotal                      245.000   │
│ Tax (11%)                      26.950   │
│ ─────────────────────────────────────   │
│ Total                         271.950   │
├─────────────────────────────────────────┤
│ [+ Add Items] [Split Bill] [Print Bill] │
│                                         │
│      [ 💳 Request Payment ]             │
└─────────────────────────────────────────┘
```

#### D. Split Bill Screen
```
┌─────────────────────────────────────────┐
│ ← Split Bill                            │
├─────────────────────────────────────────┤
│ Split Type:                             │
│ ○ Equal (÷ by person)                   │
│ ● By Item (assign items)                │
│ ○ Custom Amount                         │
├─────────────────────────────────────────┤
│                                         │
│ Bill 1 (135.975)           Bill 2       │
│ ┌─────────────────┐  ┌─────────────────┐│
│ │ 🍔 Burger   x1  │  │ 🍔 Burger   x1  ││
│ │ 🍕 Pizza    x1  │  │ 🥤 Cola     x3  ││
│ │                 │  │                 ││
│ │ + Tax           │  │ + Tax           ││
│ │ ═══════════════ │  │ ═══════════════ ││
│ │ Total: 135.975  │  │ Total: 135.975  ││
│ └─────────────────┘  └─────────────────┘│
│                                         │
│ Drag items between bills to reassign    │
│                                         │
├─────────────────────────────────────────┤
│        [ Confirm Split ]                │
└─────────────────────────────────────────┘
```

### 8.7 Notification Types

| Type | Trigger | Action |
|------|---------|--------|
| `order_ready` | All items ready | Go to table, serve |
| `item_ready` | Specific item ready | Pick up from kitchen |
| `customer_call` | QR app assistance | Go to table |
| `bill_request` | QR app request bill | Generate bill |
| `rush_reminder` | Priority order pending | Check kitchen |
| `table_timeout` | Table occupied > X hrs | Check on customer |

### 8.8 Offline Capability

```
┌─────────────────────────────────────────┐
│            OFFLINE MODE                 │
├─────────────────────────────────────────┤
│                                         │
│  ✓ View assigned tables                 │
│  ✓ Take new orders (queue)              │
│  ✓ View menu & prices                   │
│  ✓ Add items to order (queue)           │
│                                         │
│  ✗ Real-time kitchen status             │
│  ✗ Notifications                        │
│  ✗ Payment processing                   │
│                                         │
│  Auto-sync when connection restored     │
│                                         │
└─────────────────────────────────────────┘
```

---

## PHASE 9: Reporting & Analytics
**Durasi: 2 minggu**

### 9.1 Dashboard Reports

| Report | Description |
|--------|-------------|
| Sales Summary | Daily/weekly/monthly sales |
| Sales by Category | Category performance |
| Sales by Product | Top selling products |
| Sales by Payment | Payment method breakdown |
| Sales by Hour | Peak hour analysis |
| Sales by Staff | Staff performance |
| Shift Reports | Per shift summary |
| Discount Report | Discount usage |
| Void/Cancel Report | Cancelled orders |
| Table Turnover | Table efficiency |

### 9.2 Database Views/Tables

```sql
-- =====================================================
-- REPORTING VIEWS
-- =====================================================

-- Daily Sales Summary
CREATE VIEW v_daily_sales AS
SELECT 
    outlet_id,
    DATE(created_at) as sale_date,
    COUNT(*) as total_orders,
    SUM(subtotal) as gross_sales,
    SUM(discount_amount) as total_discounts,
    SUM(tax_amount) as total_tax,
    SUM(service_charge_amount) as total_service,
    SUM(grand_total) as net_sales,
    COUNT(CASE WHEN status = 'void' THEN 1 END) as void_count,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancel_count
FROM orders
WHERE status IN ('completed', 'void', 'cancelled')
GROUP BY outlet_id, DATE(created_at);

-- Product Performance
CREATE VIEW v_product_performance AS
SELECT 
    o.outlet_id,
    DATE(o.created_at) as sale_date,
    oi.product_id,
    oi.product_name,
    p.category_id,
    c.name as category_name,
    SUM(oi.quantity) as qty_sold,
    SUM(oi.total) as total_sales
FROM order_items oi
JOIN orders o ON o.id = oi.order_id
JOIN products p ON p.id = oi.product_id
LEFT JOIN categories c ON c.id = p.category_id
WHERE o.status = 'completed'
AND oi.is_void = FALSE
GROUP BY o.outlet_id, DATE(o.created_at), oi.product_id, oi.product_name, 
         p.category_id, c.name;

-- Hourly Sales
CREATE VIEW v_hourly_sales AS
SELECT 
    outlet_id,
    DATE(created_at) as sale_date,
    EXTRACT(HOUR FROM created_at) as sale_hour,
    COUNT(*) as total_orders,
    SUM(grand_total) as total_sales
FROM orders
WHERE status = 'completed'
GROUP BY outlet_id, DATE(created_at), EXTRACT(HOUR FROM created_at);

-- Payment Method Summary
CREATE VIEW v_payment_summary AS
SELECT 
    o.outlet_id,
    DATE(o.created_at) as sale_date,
    pm.name as payment_method,
    pm.type as payment_type,
    COUNT(p.id) as transaction_count,
    SUM(p.amount) as total_amount
FROM payments p
JOIN orders o ON o.id = p.order_id
JOIN payment_methods pm ON pm.id = p.payment_method_id
WHERE p.status = 'success'
GROUP BY o.outlet_id, DATE(o.created_at), pm.name, pm.type;
```

### 9.3 API Endpoints (Phase 9)

```
Reports
├── GET    /api/reports/dashboard
├── GET    /api/reports/sales-summary
├── GET    /api/reports/sales-by-category
├── GET    /api/reports/sales-by-product
├── GET    /api/reports/sales-by-payment
├── GET    /api/reports/sales-by-hour
├── GET    /api/reports/sales-by-staff
├── GET    /api/reports/shifts
├── GET    /api/reports/discounts
├── GET    /api/reports/void-cancel
├── GET    /api/reports/table-turnover
└── GET    /api/reports/export/{type}

Query Parameters:
- outlet_id: Filter by outlet
- start_date: Start date range
- end_date: End date range
- group_by: day, week, month
```

---

## PHASE 2: Inventory, Stock & Recipe
**Durasi: 3-4 minggu**

> **📍 Note:** This section should be implemented BEFORE Phase 3 (Product & Menu Management) because Products need to link to Recipes.

### 2.1 Inventory Concept untuk F&B

```
┌─────────────────────────────────────────────────────────────────┐
│                    INVENTORY HIERARCHY                          │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────┐
│  RAW MATERIALS  │  Bahan mentah yang dibeli dari supplier
│  (Bahan Baku)   │  Contoh: Daging sapi, Tepung, Minyak
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  INGREDIENTS    │  Bahan yang sudah diproses / siap pakai
│  (Bahan Jadi)   │  Contoh: Daging giling, Saus tomat, Bumbu jadi
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    RECIPES      │  Resep / BOM (Bill of Materials)
│  (Resep Menu)   │  Contoh: 1 Burger = 150g daging + 1 roti + 30g saus
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│    PRODUCTS     │  Menu yang dijual ke customer
│  (Menu Jual)    │  Contoh: Beef Burger, Cheese Burger
└─────────────────┘


Flow:
┌──────────┐    ┌──────────┐    ┌──────────┐    ┌──────────┐
│ Purchase │───►│  Stock   │───►│  Recipe  │───►│  Sales   │
│  Order   │    │   In     │    │  Deduct  │    │  Order   │
└──────────┘    └──────────┘    └──────────┘    └──────────┘
                     │                               │
                     │         Auto Deduct           │
                     │◄──────────────────────────────┘
```

### 2.2 Database Schema

```sql
-- =====================================================
-- UNIT OF MEASUREMENT
-- =====================================================

CREATE TABLE units (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    
    name VARCHAR(50) NOT NULL,           -- Kilogram, Gram, Liter, dll
    symbol VARCHAR(10) NOT NULL,         -- kg, g, L, ml, pcs
    
    -- Base unit conversion
    base_unit_id UUID REFERENCES units(id),
    conversion_factor DECIMAL(15,6) DEFAULT 1,  -- 1 kg = 1000 g
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, symbol)
);

-- Default units
-- kg, g, mg (weight)
-- L, ml (volume)
-- pcs, pack, box, dozen (quantity)

-- =====================================================
-- SUPPLIER MANAGEMENT
-- =====================================================

CREATE TABLE suppliers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    
    code VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    
    -- Contact
    contact_person VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(255),
    
    -- Address
    address TEXT,
    city VARCHAR(100),
    
    -- Payment
    payment_terms INT DEFAULT 0,          -- Days (0 = COD)
    credit_limit DECIMAL(15,2),
    
    -- Bank
    bank_name VARCHAR(100),
    bank_account VARCHAR(50),
    bank_holder VARCHAR(255),
    
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, code)
);

-- =====================================================
-- RAW MATERIALS & INGREDIENTS
-- =====================================================

CREATE TABLE inventory_categories (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    parent_id UUID REFERENCES inventory_categories(id),
    
    name VARCHAR(255) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inventory_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    category_id UUID REFERENCES inventory_categories(id),
    
    -- Basic Info
    code VARCHAR(100) NOT NULL,
    barcode VARCHAR(100),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    
    -- Type
    item_type VARCHAR(20) NOT NULL DEFAULT 'raw_material',
    -- raw_material, ingredient, semi_finished, packaging, other
    
    -- Unit
    unit_id UUID NOT NULL REFERENCES units(id),
    
    -- Pricing
    cost_price DECIMAL(15,2) DEFAULT 0,       -- Harga beli terakhir
    average_cost DECIMAL(15,2) DEFAULT 0,     -- Harga rata-rata (moving avg)
    
    -- Stock Settings
    min_stock DECIMAL(15,3) DEFAULT 0,        -- Minimum stock (alert)
    max_stock DECIMAL(15,3),                  -- Maximum stock
    reorder_point DECIMAL(15,3),              -- When to reorder
    reorder_quantity DECIMAL(15,3),           -- How much to order
    
    -- Tracking
    track_stock BOOLEAN DEFAULT TRUE,
    track_expiry BOOLEAN DEFAULT FALSE,
    default_expiry_days INT,                  -- Auto-set expiry
    
    -- Storage
    storage_location VARCHAR(100),
    storage_temp VARCHAR(50),                 -- Frozen, Chilled, Room temp
    
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, code)
);

-- Supplier items (harga per supplier)
CREATE TABLE supplier_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    supplier_id UUID NOT NULL REFERENCES suppliers(id),
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    
    supplier_code VARCHAR(100),               -- Kode di supplier
    supplier_name VARCHAR(255),               -- Nama di supplier
    
    -- Pricing
    unit_id UUID NOT NULL REFERENCES units(id),
    price DECIMAL(15,2) NOT NULL,
    min_order_qty DECIMAL(15,3) DEFAULT 1,
    
    -- Lead time
    lead_time_days INT DEFAULT 1,
    
    is_preferred BOOLEAN DEFAULT FALSE,       -- Supplier utama
    is_active BOOLEAN DEFAULT TRUE,
    
    last_purchase_date DATE,
    last_purchase_price DECIMAL(15,2),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(supplier_id, inventory_item_id)
);

-- =====================================================
-- STOCK MANAGEMENT (Per Outlet)
-- =====================================================

CREATE TABLE inventory_stocks (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    
    -- Current Stock
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
    reserved_quantity DECIMAL(15,3) DEFAULT 0,  -- Reserved for orders
    available_quantity DECIMAL(15,3) GENERATED ALWAYS AS (quantity - reserved_quantity) STORED,
    
    -- Value
    total_value DECIMAL(15,2) DEFAULT 0,
    average_cost DECIMAL(15,2) DEFAULT 0,
    
    -- Last Activity
    last_stock_in DATE,
    last_stock_out DATE,
    last_count_date DATE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(outlet_id, inventory_item_id)
);

-- Stock batches (for FIFO/FEFO tracking)
CREATE TABLE stock_batches (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    
    -- Batch Info
    batch_number VARCHAR(100),
    
    -- Quantity
    initial_quantity DECIMAL(15,3) NOT NULL,
    current_quantity DECIMAL(15,3) NOT NULL,
    
    -- Cost
    unit_cost DECIMAL(15,2) NOT NULL,
    
    -- Dates
    received_date DATE NOT NULL,
    expiry_date DATE,
    
    -- Source
    source_type VARCHAR(20),                  -- purchase, production, transfer
    source_id UUID,                           -- PO ID, Production ID, etc
    
    status VARCHAR(20) DEFAULT 'available',   -- available, expired, depleted
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- STOCK MOVEMENTS
-- =====================================================

CREATE TABLE stock_movements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    batch_id UUID REFERENCES stock_batches(id),
    
    -- Movement
    movement_type VARCHAR(30) NOT NULL,
    -- purchase_in, sales_out, adjustment_in, adjustment_out,
    -- transfer_in, transfer_out, waste, production_in, production_out,
    -- return_in, return_out, opening_stock
    
    -- Quantity
    quantity DECIMAL(15,3) NOT NULL,          -- Always positive
    direction VARCHAR(3) NOT NULL,            -- 'in' or 'out'
    
    -- Balance
    before_quantity DECIMAL(15,3) NOT NULL,
    after_quantity DECIMAL(15,3) NOT NULL,
    
    -- Cost
    unit_cost DECIMAL(15,2),
    total_cost DECIMAL(15,2),
    
    -- Reference
    reference_type VARCHAR(50),               -- order, purchase_order, adjustment, etc
    reference_id UUID,
    reference_number VARCHAR(100),
    
    notes TEXT,
    created_by UUID REFERENCES users(id),
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- RECIPE / BILL OF MATERIALS (BOM)
-- =====================================================

CREATE TABLE recipes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    product_id UUID REFERENCES products(id),  -- Link ke menu
    
    -- Recipe Info
    code VARCHAR(100),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Output
    output_quantity DECIMAL(15,3) NOT NULL DEFAULT 1,
    output_unit_id UUID REFERENCES units(id),
    
    -- Cost
    total_cost DECIMAL(15,2) DEFAULT 0,       -- Calculated
    cost_per_unit DECIMAL(15,2) DEFAULT 0,    -- Calculated
    
    -- Type
    recipe_type VARCHAR(20) DEFAULT 'product',
    -- product (menu), semi_finished, batch_production
    
    -- Yield
    expected_yield_percentage DECIMAL(5,2) DEFAULT 100,
    
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE recipe_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    recipe_id UUID NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    
    -- Can be inventory item OR another recipe (sub-recipe)
    inventory_item_id UUID REFERENCES inventory_items(id),
    sub_recipe_id UUID REFERENCES recipes(id),
    
    -- Quantity
    quantity DECIMAL(15,6) NOT NULL,
    unit_id UUID NOT NULL REFERENCES units(id),
    
    -- Cost (snapshot)
    unit_cost DECIMAL(15,2) DEFAULT 0,
    total_cost DECIMAL(15,2) DEFAULT 0,
    
    -- Waste allowance
    waste_percentage DECIMAL(5,2) DEFAULT 0,
    
    is_optional BOOLEAN DEFAULT FALSE,
    sort_order INT DEFAULT 0,
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Either inventory_item_id OR sub_recipe_id must be set
    CONSTRAINT recipe_item_source CHECK (
        (inventory_item_id IS NOT NULL AND sub_recipe_id IS NULL) OR
        (inventory_item_id IS NULL AND sub_recipe_id IS NOT NULL)
    )
);

-- Recipe per variant (jika harga variant beda bahan)
CREATE TABLE recipe_variants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    recipe_id UUID NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    variant_option_id UUID NOT NULL REFERENCES variant_options(id),
    
    -- Adjustment to base recipe
    quantity_multiplier DECIMAL(5,2) DEFAULT 1,  -- 1.5x for Large
    
    -- Or additional items
    additional_items JSONB,  -- [{inventory_item_id, quantity, unit_id}]
    
    cost_adjustment DECIMAL(15,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- PURCHASE ORDER
-- =====================================================

CREATE TABLE purchase_orders (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    supplier_id UUID NOT NULL REFERENCES suppliers(id),
    
    -- PO Info
    po_number VARCHAR(50) NOT NULL,
    po_date DATE NOT NULL DEFAULT CURRENT_DATE,
    expected_date DATE,
    
    -- Status
    status VARCHAR(20) DEFAULT 'draft',
    -- draft, submitted, approved, partial, received, cancelled
    
    -- Totals
    subtotal DECIMAL(15,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    
    -- Payment
    payment_terms INT DEFAULT 0,
    payment_status VARCHAR(20) DEFAULT 'unpaid',
    -- unpaid, partial, paid
    
    notes TEXT,
    
    -- Approval
    approved_by UUID REFERENCES users(id),
    approved_at TIMESTAMP,
    
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE purchase_order_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    purchase_order_id UUID NOT NULL REFERENCES purchase_orders(id) ON DELETE CASCADE,
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    
    -- Quantity
    quantity DECIMAL(15,3) NOT NULL,
    unit_id UUID NOT NULL REFERENCES units(id),
    received_quantity DECIMAL(15,3) DEFAULT 0,
    
    -- Pricing
    unit_price DECIMAL(15,2) NOT NULL,
    discount_percentage DECIMAL(5,2) DEFAULT 0,
    tax_percentage DECIMAL(5,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- GOODS RECEIVE
-- =====================================================

CREATE TABLE goods_receives (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    purchase_order_id UUID REFERENCES purchase_orders(id),
    supplier_id UUID NOT NULL REFERENCES suppliers(id),
    
    -- GR Info
    gr_number VARCHAR(50) NOT NULL,
    gr_date DATE NOT NULL DEFAULT CURRENT_DATE,
    
    -- Reference
    supplier_invoice VARCHAR(100),
    supplier_do VARCHAR(100),                 -- Delivery Order
    
    -- Status
    status VARCHAR(20) DEFAULT 'draft',       -- draft, confirmed, cancelled
    
    -- Totals
    total_amount DECIMAL(15,2) DEFAULT 0,
    
    notes TEXT,
    
    received_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE goods_receive_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    goods_receive_id UUID NOT NULL REFERENCES goods_receives(id) ON DELETE CASCADE,
    purchase_order_item_id UUID REFERENCES purchase_order_items(id),
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    
    -- Quantity
    quantity DECIMAL(15,3) NOT NULL,
    unit_id UUID NOT NULL REFERENCES units(id),
    
    -- Batch
    batch_number VARCHAR(100),
    expiry_date DATE,
    
    -- Cost
    unit_cost DECIMAL(15,2) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    
    -- Quality
    quality_status VARCHAR(20) DEFAULT 'accepted',  -- accepted, rejected, partial
    rejected_quantity DECIMAL(15,3) DEFAULT 0,
    rejection_reason TEXT,
    
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- STOCK ADJUSTMENT & OPNAME
-- =====================================================

CREATE TABLE stock_adjustments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    
    -- Adjustment Info
    adjustment_number VARCHAR(50) NOT NULL,
    adjustment_date DATE NOT NULL DEFAULT CURRENT_DATE,
    adjustment_type VARCHAR(30) NOT NULL,
    -- stock_opname, damage, expired, theft, correction, opening
    
    -- Status
    status VARCHAR(20) DEFAULT 'draft',       -- draft, approved, cancelled
    
    -- Summary
    total_items INT DEFAULT 0,
    total_variance_value DECIMAL(15,2) DEFAULT 0,
    
    reason TEXT,
    notes TEXT,
    
    -- Approval
    approved_by UUID REFERENCES users(id),
    approved_at TIMESTAMP,
    
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE stock_adjustment_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    stock_adjustment_id UUID NOT NULL REFERENCES stock_adjustments(id) ON DELETE CASCADE,
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    batch_id UUID REFERENCES stock_batches(id),
    
    -- Quantities
    system_quantity DECIMAL(15,3) NOT NULL,   -- Qty di sistem
    actual_quantity DECIMAL(15,3) NOT NULL,   -- Qty aktual (count)
    variance_quantity DECIMAL(15,3) GENERATED ALWAYS AS (actual_quantity - system_quantity) STORED,
    
    -- Value
    unit_cost DECIMAL(15,2) NOT NULL,
    variance_value DECIMAL(15,2) GENERATED ALWAYS AS ((actual_quantity - system_quantity) * unit_cost) STORED,
    
    reason TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- WASTE MANAGEMENT
-- =====================================================

CREATE TABLE waste_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    outlet_id UUID NOT NULL REFERENCES outlets(id),
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    batch_id UUID REFERENCES stock_batches(id),
    
    -- Waste Info
    waste_date DATE NOT NULL DEFAULT CURRENT_DATE,
    quantity DECIMAL(15,3) NOT NULL,
    unit_id UUID NOT NULL REFERENCES units(id),
    
    -- Cost
    unit_cost DECIMAL(15,2) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    
    -- Reason
    waste_type VARCHAR(30) NOT NULL,
    -- expired, damaged, spoiled, overproduction, preparation, other
    
    reason TEXT,
    
    -- Approval (optional)
    requires_approval BOOLEAN DEFAULT FALSE,
    approved_by UUID REFERENCES users(id),
    approved_at TIMESTAMP,
    
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- STOCK TRANSFER (Between Outlets)
-- =====================================================

CREATE TABLE stock_transfers (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    
    -- Transfer Info
    transfer_number VARCHAR(50) NOT NULL,
    transfer_date DATE NOT NULL DEFAULT CURRENT_DATE,
    
    -- Outlets
    from_outlet_id UUID NOT NULL REFERENCES outlets(id),
    to_outlet_id UUID NOT NULL REFERENCES outlets(id),
    
    -- Status
    status VARCHAR(20) DEFAULT 'draft',
    -- draft, in_transit, received, cancelled
    
    -- Totals
    total_items INT DEFAULT 0,
    total_value DECIMAL(15,2) DEFAULT 0,
    
    notes TEXT,
    
    -- Timestamps
    sent_at TIMESTAMP,
    sent_by UUID REFERENCES users(id),
    received_at TIMESTAMP,
    received_by UUID REFERENCES users(id),
    
    created_by UUID REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE stock_transfer_items (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    stock_transfer_id UUID NOT NULL REFERENCES stock_transfers(id) ON DELETE CASCADE,
    inventory_item_id UUID NOT NULL REFERENCES inventory_items(id),
    batch_id UUID REFERENCES stock_batches(id),
    
    -- Quantity
    quantity DECIMAL(15,3) NOT NULL,
    received_quantity DECIMAL(15,3),
    unit_id UUID NOT NULL REFERENCES units(id),
    
    -- Cost
    unit_cost DECIMAL(15,2) NOT NULL,
    total_cost DECIMAL(15,2) NOT NULL,
    
    notes TEXT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- INDEXES
-- =====================================================

CREATE INDEX idx_inventory_items_tenant ON inventory_items(tenant_id);
CREATE INDEX idx_inventory_items_category ON inventory_items(category_id);
CREATE INDEX idx_inventory_stocks_outlet ON inventory_stocks(outlet_id);
CREATE INDEX idx_inventory_stocks_item ON inventory_stocks(inventory_item_id);
CREATE INDEX idx_stock_movements_outlet ON stock_movements(outlet_id);
CREATE INDEX idx_stock_movements_item ON stock_movements(inventory_item_id);
CREATE INDEX idx_stock_movements_date ON stock_movements(created_at);
CREATE INDEX idx_recipes_tenant ON recipes(tenant_id);
CREATE INDEX idx_recipes_product ON recipes(product_id);
CREATE INDEX idx_purchase_orders_outlet ON purchase_orders(outlet_id);
CREATE INDEX idx_purchase_orders_supplier ON purchase_orders(supplier_id);
CREATE INDEX idx_stock_batches_outlet ON stock_batches(outlet_id);
CREATE INDEX idx_stock_batches_item ON stock_batches(inventory_item_id);
CREATE INDEX idx_stock_batches_expiry ON stock_batches(expiry_date);
```

### 2.3 Auto Deduct Flow (Sales → Stock)

```
┌─────────────────────────────────────────────────────────────────┐
│                   AUTO DEDUCT STOCK FLOW                        │
└─────────────────────────────────────────────────────────────────┘

┌──────────────┐
│ Order Item   │  Customer order: 1x Beef Burger
│ (1x Burger)  │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│ Get Recipe   │  Recipe: Beef Burger
│              │  Output: 1 pcs
└──────┬───────┘
       │
       ▼
┌──────────────────────────────────────────────────────┐
│                  RECIPE ITEMS                         │
├──────────────────────────────────────────────────────┤
│  • Beef Patty        150g    (Ingredient)            │
│  • Burger Bun        1 pcs   (Raw Material)          │
│  • Lettuce           30g     (Raw Material)          │
│  • Tomato Slice      2 pcs   (Raw Material)          │
│  • Cheese Slice      1 pcs   (Raw Material)          │
│  • Special Sauce     25ml    (Sub-Recipe) ──────┐    │
│                                                 │    │
└─────────────────────────────────────────────────┼────┘
                                                  │
       ┌──────────────────────────────────────────┘
       ▼
┌──────────────────────────────────────────────────────┐
│              SUB-RECIPE: Special Sauce               │
├──────────────────────────────────────────────────────┤
│  • Mayonnaise        15ml    (Raw Material)          │
│  • Ketchup           5ml     (Raw Material)          │
│  • Mustard           5ml     (Raw Material)          │
└──────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────────────────────────────────────────────┐
│              STOCK DEDUCTION (FIFO)                  │
├──────────────────────────────────────────────────────┤
│                                                      │
│  inventory_stocks (quantity - used)                  │
│  stock_movements (record each deduction)             │
│  stock_batches (deduct from oldest first)            │
│                                                      │
└──────────────────────────────────────────────────────┘
```

### 2.4 Admin Dashboard Features (Inventory)

| Module | Features |
|--------|----------|
| **Master Data** | Units, Categories, Suppliers |
| **Inventory Items** | CRUD raw materials & ingredients |
| **Recipe Management** | Create recipes, link to products |
| **Stock Overview** | Current stock per outlet |
| **Purchase Orders** | Create, approve, track PO |
| **Goods Receive** | Receive items, batch tracking |
| **Stock Adjustment** | Stock opname, corrections |
| **Waste Log** | Record waste with reasons |
| **Stock Transfer** | Transfer between outlets |
| **Reports** | Stock value, movement, COGS |

### 2.5 API Endpoints (Inventory)

```
Units
├── GET    /api/units
├── POST   /api/units
├── PUT    /api/units/{id}
└── DELETE /api/units/{id}

Suppliers
├── GET    /api/suppliers
├── POST   /api/suppliers
├── GET    /api/suppliers/{id}
├── PUT    /api/suppliers/{id}
├── DELETE /api/suppliers/{id}
└── GET    /api/suppliers/{id}/items

Inventory Items
├── GET    /api/inventory-items
├── POST   /api/inventory-items
├── GET    /api/inventory-items/{id}
├── PUT    /api/inventory-items/{id}
├── DELETE /api/inventory-items/{id}
├── GET    /api/inventory-items/{id}/stock
├── GET    /api/inventory-items/{id}/movements
└── POST   /api/inventory-items/import

Recipes
├── GET    /api/recipes
├── POST   /api/recipes
├── GET    /api/recipes/{id}
├── PUT    /api/recipes/{id}
├── DELETE /api/recipes/{id}
├── POST   /api/recipes/{id}/calculate-cost
└── POST   /api/recipes/{id}/duplicate

Stock
├── GET    /api/stock                         # Stock per outlet
├── GET    /api/stock/low-stock               # Items below min
├── GET    /api/stock/expiring                # Expiring soon
├── GET    /api/stock/{itemId}/batches        # Batch list
└── GET    /api/stock/{itemId}/movements      # Movement history

Purchase Orders
├── GET    /api/purchase-orders
├── POST   /api/purchase-orders
├── GET    /api/purchase-orders/{id}
├── PUT    /api/purchase-orders/{id}
├── DELETE /api/purchase-orders/{id}
├── POST   /api/purchase-orders/{id}/submit
├── POST   /api/purchase-orders/{id}/approve
├── POST   /api/purchase-orders/{id}/cancel
└── POST   /api/purchase-orders/{id}/receive  # Quick receive all

Goods Receive
├── GET    /api/goods-receives
├── POST   /api/goods-receives
├── GET    /api/goods-receives/{id}
├── PUT    /api/goods-receives/{id}
├── POST   /api/goods-receives/{id}/confirm
└── DELETE /api/goods-receives/{id}

Stock Adjustments
├── GET    /api/stock-adjustments
├── POST   /api/stock-adjustments
├── GET    /api/stock-adjustments/{id}
├── PUT    /api/stock-adjustments/{id}
├── POST   /api/stock-adjustments/{id}/approve
└── DELETE /api/stock-adjustments/{id}

Waste Logs
├── GET    /api/waste-logs
├── POST   /api/waste-logs
└── GET    /api/waste-logs/summary

Stock Transfers
├── GET    /api/stock-transfers
├── POST   /api/stock-transfers
├── GET    /api/stock-transfers/{id}
├── PUT    /api/stock-transfers/{id}
├── POST   /api/stock-transfers/{id}/send
├── POST   /api/stock-transfers/{id}/receive
└── DELETE /api/stock-transfers/{id}

Inventory Reports
├── GET    /api/inventory-reports/stock-value
├── GET    /api/inventory-reports/movement-summary
├── GET    /api/inventory-reports/cogs                # Cost of Goods Sold
├── GET    /api/inventory-reports/food-cost           # Food cost percentage
├── GET    /api/inventory-reports/waste-summary
└── GET    /api/inventory-reports/purchase-summary
```

### 2.6 Key Reports

| Report | Description |
|--------|-------------|
| **Stock Valuation** | Total nilai inventory per outlet |
| **Stock Movement** | History keluar masuk barang |
| **COGS Report** | Cost of Goods Sold per periode |
| **Food Cost %** | (COGS / Sales) x 100% |
| **Waste Report** | Total waste per kategori |
| **Purchase Report** | Purchase per supplier/item |
| **Low Stock Alert** | Items yang perlu reorder |
| **Expiry Alert** | Items yang akan expired |
| **Stock Card** | Kartu stok per item |
| **Recipe Cost** | Breakdown cost per menu |

### 2.7 Food Cost Calculation

```
┌─────────────────────────────────────────────────────────────────┐
│                    FOOD COST FORMULA                            │
└─────────────────────────────────────────────────────────────────┘

Food Cost % = (Cost of Goods Sold / Food Sales) × 100%

COGS = Opening Stock + Purchases - Closing Stock

Example:
─────────────────────────────────────────────────
Opening Stock (1 Jan)         :  Rp 10.000.000
+ Purchases (Jan)             :  Rp 25.000.000
- Closing Stock (31 Jan)      :  Rp  8.000.000
─────────────────────────────────────────────────
COGS                          :  Rp 27.000.000

Food Sales (Jan)              :  Rp 90.000.000

Food Cost % = (27.000.000 / 90.000.000) × 100%
            = 30%

Target Food Cost: 28-32% (typical F&B industry)
```

---

## PHASE 10: Payment Gateway Integration
**Durasi: 1-2 minggu**

### 8.1 Xendit Integration

| Feature | Description |
|---------|-------------|
| QRIS | QR Code payment |
| Virtual Account | Bank transfer |
| eWallet | OVO, DANA, GoPay, etc |
| Invoice | Payment link |
| Callback | Auto payment confirmation |

### 8.2 Database Additions

```sql
-- Payment Gateway Configuration per Tenant
CREATE TABLE payment_gateway_configs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id),
    
    provider VARCHAR(50) NOT NULL, -- xendit, midtrans
    
    -- Credentials (encrypted)
    api_key_encrypted TEXT,
    webhook_token_encrypted TEXT,
    
    -- Settings
    is_sandbox BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE(tenant_id, provider)
);

-- Gateway Transactions
CREATE TABLE gateway_transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    payment_id UUID NOT NULL REFERENCES payments(id),
    
    -- Gateway Info
    provider VARCHAR(50) NOT NULL,
    external_id VARCHAR(255) NOT NULL,
    
    -- Type
    type VARCHAR(50) NOT NULL, -- qris, va, ewallet, invoice
    
    -- Data
    qr_code_url TEXT,
    payment_url TEXT,
    va_number VARCHAR(100),
    
    -- Status
    status VARCHAR(20) DEFAULT 'pending',
    -- pending, paid, expired, failed
    
    -- Amount
    amount DECIMAL(15,2) NOT NULL,
    paid_amount DECIMAL(15,2),
    
    -- Expiry
    expires_at TIMESTAMP,
    paid_at TIMESTAMP,
    
    -- Callback
    callback_data JSONB,
    callback_received_at TIMESTAMP,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_gateway_tx_payment ON gateway_transactions(payment_id);
CREATE INDEX idx_gateway_tx_external ON gateway_transactions(external_id);
```

### 8.3 API Endpoints (Phase 8)

```
Payment Gateway (Admin)
├── GET    /api/payment-gateway/config
├── POST   /api/payment-gateway/config
├── PUT    /api/payment-gateway/config/{id}
└── POST   /api/payment-gateway/test

Payment Gateway (POS)
├── POST   /api/payments/gateway/qris
├── POST   /api/payments/gateway/va
├── POST   /api/payments/gateway/ewallet
├── GET    /api/payments/gateway/{id}/status
└── POST   /api/payments/gateway/{id}/cancel

Webhooks
└── POST   /api/webhooks/xendit
```

---

## PHASE 11: POS Android App (Flutter)
**Durasi: 3-4 minggu**

### 9.1 Features

| Feature | Description |
|---------|-------------|
| Login | Email/PIN login |
| Outlet Selection | Multi-outlet support |
| Shift Management | Open/close shift |
| Menu Browse | Grid dengan kategori |
| Cart | Full cart management |
| Variant/Modifier | Modal selection |
| Table Selection | Quick table pick |
| Order Types | Dine-in, takeaway |
| Payments | Multi-payment + gateway |
| Receipt | Bluetooth printer |
| Order History | View shift orders |
| Offline Mode | Queue orders offline |

### 9.2 Project Structure

```
lib/
├── core/
│   ├── config/
│   │   ├── app_config.dart
│   │   ├── api_config.dart
│   │   └── env.dart
│   ├── network/
│   │   ├── api_client.dart
│   │   ├── api_interceptor.dart
│   │   └── api_response.dart
│   ├── storage/
│   │   ├── local_storage.dart
│   │   └── secure_storage.dart
│   ├── utils/
│   │   ├── currency_helper.dart
│   │   ├── date_helper.dart
│   │   └── validator.dart
│   └── theme/
│       ├── app_theme.dart
│       ├── colors.dart
│       └── typography.dart
│
├── data/
│   ├── models/
│   │   ├── user.dart
│   │   ├── outlet.dart
│   │   ├── category.dart
│   │   ├── product.dart
│   │   ├── order.dart
│   │   ├── order_item.dart
│   │   ├── payment.dart
│   │   ├── shift.dart
│   │   └── table.dart
│   ├── repositories/
│   │   ├── auth_repository.dart
│   │   ├── product_repository.dart
│   │   ├── order_repository.dart
│   │   ├── payment_repository.dart
│   │   └── shift_repository.dart
│   └── providers/
│       ├── auth_provider.dart
│       ├── cart_provider.dart
│       ├── menu_provider.dart
│       ├── order_provider.dart
│       └── shift_provider.dart
│
├── features/
│   ├── auth/
│   │   ├── screens/
│   │   │   ├── login_screen.dart
│   │   │   ├── pin_screen.dart
│   │   │   └── outlet_select_screen.dart
│   │   └── widgets/
│   │       └── login_form.dart
│   │
│   ├── pos/
│   │   ├── screens/
│   │   │   ├── pos_screen.dart
│   │   │   ├── cart_screen.dart
│   │   │   ├── checkout_screen.dart
│   │   │   └── receipt_screen.dart
│   │   └── widgets/
│   │       ├── category_tabs.dart
│   │       ├── product_grid.dart
│   │       ├── product_card.dart
│   │       ├── cart_item.dart
│   │       ├── variant_modal.dart
│   │       ├── modifier_modal.dart
│   │       └── payment_modal.dart
│   │
│   ├── tables/
│   │   ├── screens/
│   │   │   └── table_screen.dart
│   │   └── widgets/
│   │       ├── floor_tabs.dart
│   │       └── table_card.dart
│   │
│   ├── orders/
│   │   ├── screens/
│   │   │   ├── orders_screen.dart
│   │   │   └── order_detail_screen.dart
│   │   └── widgets/
│   │       └── order_card.dart
│   │
│   ├── shift/
│   │   ├── screens/
│   │   │   ├── shift_open_screen.dart
│   │   │   └── shift_close_screen.dart
│   │   └── widgets/
│   │       └── shift_summary.dart
│   │
│   └── settings/
│       ├── screens/
│       │   ├── settings_screen.dart
│       │   └── printer_settings_screen.dart
│       └── widgets/
│           └── printer_card.dart
│
├── services/
│   ├── printer_service.dart
│   ├── websocket_service.dart
│   ├── sync_service.dart
│   └── notification_service.dart
│
├── widgets/
│   ├── app_button.dart
│   ├── app_input.dart
│   ├── loading_overlay.dart
│   ├── error_widget.dart
│   └── empty_state.dart
│
└── main.dart
```

### 9.3 Key Packages

```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # State Management
  provider: ^6.1.0
  
  # Network
  dio: ^5.4.0
  
  # Storage
  shared_preferences: ^2.2.0
  flutter_secure_storage: ^9.0.0
  sqflite: ^2.3.0 # Offline sync
  
  # UI
  cached_network_image: ^3.3.0
  shimmer: ^3.0.0
  flutter_slidable: ^3.0.0
  
  # Bluetooth Printer
  flutter_blue_plus: ^1.31.0
  esc_pos_utils: ^1.1.0
  esc_pos_bluetooth: ^0.4.1
  
  # Others
  intl: ^0.19.0
  uuid: ^4.2.0
  connectivity_plus: ^5.0.0
  web_socket_channel: ^2.4.0
```

---

## PHASE 12: Polish, Testing & Deployment
**Durasi: 2 minggu**

### 10.1 Testing

| Type | Coverage |
|------|----------|
| Unit Test | Repository, Helper |
| Feature Test | API endpoints |
| Integration | Order flow E2E |
| UI Test | Critical flows |
| Load Test | Concurrent orders |

### 10.2 Deployment Checklist

```
Infrastructure
├── [ ] Server setup (production)
├── [ ] Database setup & optimization
├── [ ] Redis setup
├── [ ] SSL certificates
├── [ ] Domain configuration
├── [ ] CDN for assets
├── [ ] Backup strategy
└── [ ] Monitoring (Sentry, etc)

Backend
├── [ ] Environment config
├── [ ] Queue workers
├── [ ] WebSocket server
├── [ ] Cron jobs
├── [ ] API documentation
└── [ ] Rate limiting

Frontend (Web)
├── [ ] Build optimization
├── [ ] PWA setup
├── [ ] Error tracking
└── [ ] Analytics

Mobile (Android)
├── [ ] Release build
├── [ ] App signing
├── [ ] Play Store listing
└── [ ] Crash reporting
```

### 10.3 Documentation

| Document | Description |
|----------|-------------|
| API Documentation | Swagger/OpenAPI |
| User Manual | Admin, POS, KDS |
| Setup Guide | Installation steps |
| Troubleshooting | Common issues |

---

## Summary Timeline

| Phase | Duration | Deliverable |
|-------|----------|-------------|
| Phase 1 | 3-4 minggu | Foundation + Multi-tenant |
| Phase 2 | 3-4 minggu | Inventory, Stock & Recipe |
| Phase 3 | 2-3 minggu | Product & Menu Management |
| Phase 4 | 3-4 minggu | POS Core Web |
| Phase 5 | 2 minggu | Table Management |
| Phase 6 | 2-3 minggu | Kitchen System |
| Phase 7 | 2 minggu | QR Order |
| Phase 8 | 2-3 minggu | Waiter App (Flutter) |
| Phase 9 | 2 minggu | Reporting & Analytics |
| Phase 10 | 1-2 minggu | Payment Gateway |
| Phase 11 | 3-4 minggu | POS Android App |
| Phase 12 | 2 minggu | Testing & Deploy |
| **Total** | **27-37 minggu** | **Full System** |

---

## MVP Suggestion

Kalau mau launch lebih cepat, bisa prioritaskan:

**MVP Phase (8-10 minggu):**
1. Phase 1: Foundation (3 minggu)
2. Phase 2: Product Management (2 minggu)
3. Phase 3: POS Core Web (3-4 minggu)
4. Phase 5: Kitchen Printer only (1 minggu)

**Post-MVP:**
- Table Management
- KDS
- QR Order
- Reports
- Payment Gateway
- Android App

---

*Document Version: 1.0*
*Created: January 2026*
