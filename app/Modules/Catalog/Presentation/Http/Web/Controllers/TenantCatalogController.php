<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Presentation\Http\Web\Controllers;

use App\Modules\Catalog\Application\Actions\ChangeCategoryStatus;
use App\Modules\Catalog\Application\Actions\ChangeProductStatus;
use App\Modules\Catalog\Application\Actions\CreateCategory;
use App\Modules\Catalog\Application\Actions\CreateProduct;
use App\Modules\Catalog\Application\Actions\SetOutletAvailability;
use App\Modules\Catalog\Application\Actions\UpdateCategory;
use App\Modules\Catalog\Application\Actions\UpdateProduct;
use App\Modules\Catalog\Application\Data\CategoryInput;
use App\Modules\Catalog\Application\Data\ProductInput;
use App\Modules\Catalog\Application\Exceptions\CatalogException;
use App\Modules\Catalog\Domain\Enums\CategoryStatus;
use App\Modules\Catalog\Domain\Enums\ProductStatus;
use App\Modules\Catalog\Domain\Models\Category;
use App\Modules\Catalog\Domain\Models\Product;
use App\Modules\Catalog\Domain\Models\ProductOutletAvailability;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantCatalogSummary;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantCatalogController extends Controller
{
    public function __construct(private TenantCatalogReference $tenancy) {}

    public function index(Request $request): View
    {
        $context = $this->context($request);
        $tenant = $this->tenant($context);

        return view('catalog::tenant.catalog.index', [
            'tenant' => $this->tenantView($tenant),
            'context' => $context,
            'categories' => Category::query()
                ->where('tenant_id', $context->tenantId)
                ->orderBy('name')
                ->get(),
            'products' => Product::query()
                ->where('tenant_id', $context->tenantId)
                ->orderBy('name')
                ->get(),
            'availabilities' => ProductOutletAvailability::query()
                ->where('tenant_id', $context->tenantId)
                ->get()
                ->keyBy(fn (ProductOutletAvailability $availability): string => $availability->product_id.'|'.$availability->outlet_id),
            'outlets' => $this->tenancy->activeOutlets($context->tenantId),
            'defaultCurrency' => $tenant->currency,
        ]);
    }

    public function storeCategory(Request $request, CreateCategory $create): RedirectResponse
    {
        $input = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $create->handle($this->context($request), new CategoryInput((string) $input['name']));

        return back()->with('status', 'Category created.');
    }

    public function updateCategory(
        Request $request,
        string $tenant,
        string $category,
        UpdateCategory $update,
    ): RedirectResponse {
        $input = $request->validate(['name' => ['required', 'string', 'max:120']]);

        try {
            $update->handle($this->context($request), $category, new CategoryInput((string) $input['name']));
        } catch (CatalogException) {
            abort(404);
        }

        return back()->with('status', 'Category updated.');
    }

    public function changeCategoryStatus(
        Request $request,
        string $tenant,
        string $category,
        ChangeCategoryStatus $change,
    ): RedirectResponse {
        $input = $request->validate(['status' => ['required', 'in:active,inactive']]);

        try {
            $change->handle(
                $this->context($request),
                $category,
                CategoryStatus::from((string) $input['status']),
            );
        } catch (CatalogException) {
            abort(404);
        }

        return back()->with('status', 'Category status updated.');
    }

    public function storeProduct(Request $request, CreateProduct $create): RedirectResponse
    {
        $input = $this->validatedProduct($request);

        try {
            $create->handle($this->context($request), $input);
        } catch (CatalogException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Product created.');
    }

    public function updateProduct(
        Request $request,
        string $tenant,
        string $product,
        UpdateProduct $update,
    ): RedirectResponse {
        $input = $this->validatedProduct($request);

        try {
            $update->handle($this->context($request), $product, $input);
        } catch (CatalogException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Product updated.');
    }

    public function changeProductStatus(
        Request $request,
        string $tenant,
        string $product,
        ChangeProductStatus $change,
    ): RedirectResponse {
        $input = $request->validate(['status' => ['required', 'in:active,inactive']]);

        try {
            $change->handle(
                $this->context($request),
                $product,
                ProductStatus::from((string) $input['status']),
            );
        } catch (CatalogException) {
            abort(404);
        }

        return back()->with('status', 'Product status updated.');
    }

    public function setAvailability(
        Request $request,
        string $tenant,
        string $product,
        SetOutletAvailability $availability,
    ): RedirectResponse {
        $input = $request->validate([
            'outlet_id' => ['required', 'string', 'size:26'],
            'available' => ['nullable', 'boolean'],
            'price_minor' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $availability->handle(
                $this->context($request),
                $product,
                (string) $input['outlet_id'],
                (bool) ($input['available'] ?? false),
                array_key_exists('price_minor', $input) ? (int) $input['price_minor'] : null,
            );
        } catch (CatalogException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Outlet availability updated.');
    }

    private function context(Request $request): TenantRequestContext
    {
        $context = $request->attributes->get('tenant_context');

        abort_unless($context instanceof TenantRequestContext, 404);

        return $context;
    }

    private function tenant(TenantRequestContext $context): TenantCatalogSummary
    {
        $tenant = $this->tenancy->tenant($context->tenantId);

        abort_unless($tenant instanceof TenantCatalogSummary, 404);

        return $tenant;
    }

    private function tenantView(TenantCatalogSummary $tenant): object
    {
        return (object) [
            'id' => $tenant->tenantId,
            'name' => $tenant->name,
            'currency' => $tenant->currency,
        ];
    }

    private function validatedProduct(Request $request): ProductInput
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['required', 'string', 'max:64'],
            'category_id' => ['required', 'string', 'size:26'],
            'base_price_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
        ]);

        return new ProductInput(
            name: (string) $input['name'],
            sku: (string) $input['sku'],
            categoryId: (string) $input['category_id'],
            basePriceMinor: (int) $input['base_price_minor'],
            currency: (string) $input['currency'],
        );
    }

    private function validation(CatalogException $exception): ValidationException
    {
        return ValidationException::withMessages([
            match ($exception->errorCode()) {
                'CATALOG_SKU_UNAVAILABLE' => 'sku',
                'CATALOG_CATEGORY_NOT_FOUND' => 'category_id',
                'CATALOG_OUTLET_NOT_FOUND' => 'outlet_id',
                default => 'product',
            } => [$exception->getMessage()],
        ]);
    }
}
