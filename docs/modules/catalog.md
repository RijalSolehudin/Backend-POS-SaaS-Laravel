# Catalog Module

## Owns

- Product category.
- Product dan sellable state.
- Base price dan outlet availability.
- Variant dan modifier definitions pada phase lanjutan.

## MVP Use Cases

- Manage category.
- Manage simple product.
- Set outlet price/availability.
- List sellable products untuk POS melalui `/api/v1/pos/outlets/{outlet}/catalog`.

## Invariants

- SKU uniqueness mengikuti tenant scope.
- Produk tidak dapat dijual pada outlet jika tidak aktif/tersedia.
- Product dan category reference selalu di-scope pada tenant yang sama.
- Outlet availability selalu divalidasi lewat active outlet milik tenant.
- API catalog hanya mengembalikan product aktif, category aktif, dan availability aktif untuk outlet context.
- Perubahan master price tidak mengubah snapshot order lama.

## Open Decisions

- Recipe ownership.
- Variant/modifier inclusion dalam MVP.
- Price book, scheduled price, dan tax category model.
