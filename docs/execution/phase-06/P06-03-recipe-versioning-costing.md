# P06-03 — Recipe Versioning and Costing

Status: **Done**

## Outcome

Recipe memiliki versioned ingredients dan cost yang dapat dijelaskan dari Inventory average cost.

## Scope

- Buat `recipe_versions`, `recipe_ingredients`, dan `recipe_variant_mappings`.
- Aktivasi hanya satu active version per catalog variant.
- Hitung recipe cost dari Inventory average cost.
- Simpan yield percent dan ingredient snapshot pada version.

## Implementation Contract

- Ikuti [Phase 06 Implementation Contract](implementation-contract.md).
- Quantity ingredient memakai base unit Inventory.
- Recipe version active tidak boleh diedit langsung; perubahan membuat version baru.

## Verification

- Feature tests activation/version archive.
- Costing tests.
- Cross-tenant reference rejection.
- `composer quality`.
