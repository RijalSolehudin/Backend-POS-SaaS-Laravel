<?php

declare(strict_types=1);

namespace App\Modules\Procurement\Presentation\Http\Web\Controllers;

use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Procurement\Application\Actions\ChangeSupplierStatus;
use App\Modules\Procurement\Application\Actions\CreateSupplier;
use App\Modules\Procurement\Application\Actions\UpdateSupplier;
use App\Modules\Procurement\Application\Actions\UpsertSupplierItem;
use App\Modules\Procurement\Application\Data\SupplierInput;
use App\Modules\Procurement\Application\Data\SupplierItemInput;
use App\Modules\Procurement\Application\Exceptions\ProcurementException;
use App\Modules\Procurement\Domain\Enums\SupplierStatus;
use App\Modules\Procurement\Domain\Models\Supplier;
use App\Modules\Procurement\Domain\Models\SupplierItem;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantCatalogSummary;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantProcurementController extends Controller
{
    public function __construct(private TenantCatalogReference $tenancy) {}

    public function index(Request $request): View
    {
        $context = $this->context($request);

        return view('procurement::tenant.procurement.index', [
            'tenant' => $this->tenantView($this->tenant($context)),
            'context' => $context,
            'suppliers' => Supplier::query()->where('tenant_id', $context->tenantId)->orderBy('name')->get(),
            'supplierItems' => SupplierItem::query()->where('tenant_id', $context->tenantId)->orderBy('supplier_sku')->get(),
            'inventoryItems' => InventoryItem::query()->where('tenant_id', $context->tenantId)->orderBy('name')->get(),
        ]);
    }

    public function storeSupplier(Request $request, CreateSupplier $create): RedirectResponse
    {
        try {
            $create->handle($this->context($request), $this->supplierInput($request));
        } catch (ProcurementException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Supplier created.');
    }

    public function updateSupplier(Request $request, string $tenant, string $supplier, UpdateSupplier $update): RedirectResponse
    {
        try {
            $update->handle($this->context($request), $supplier, $this->supplierInput($request));
        } catch (ProcurementException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Supplier updated.');
    }

    public function supplierStatus(Request $request, string $tenant, string $supplier, ChangeSupplierStatus $change): RedirectResponse
    {
        $input = $request->validate(['status' => ['required', 'in:active,inactive']]);

        try {
            $change->handle($this->context($request), $supplier, SupplierStatus::from((string) $input['status']));
        } catch (ProcurementException) {
            abort(404);
        }

        return back()->with('status', 'Supplier status updated.');
    }

    public function storeSupplierItem(Request $request, UpsertSupplierItem $upsert): RedirectResponse
    {
        $input = $request->validate([
            'supplier_id' => ['required', 'string', 'size:26'],
            'inventory_item_id' => ['required', 'string', 'size:26'],
            'supplier_sku' => ['required', 'string', 'max:80'],
            'last_price_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $upsert->handle($this->context($request), new SupplierItemInput(
                supplierId: (string) $input['supplier_id'],
                inventoryItemId: (string) $input['inventory_item_id'],
                supplierSku: (string) $input['supplier_sku'],
                lastPriceMinor: (int) $input['last_price_minor'],
                currency: (string) $input['currency'],
                isActive: (bool) ($input['is_active'] ?? false),
            ));
        } catch (ProcurementException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Supplier item saved.');
    }

    private function supplierInput(Request $request): SupplierInput
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:64'],
        ]);

        return new SupplierInput((string) $input['name'], (string) $input['code']);
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

    private function validation(ProcurementException $exception): ValidationException
    {
        return ValidationException::withMessages([
            match ($exception->errorCode()) {
                'PROCUREMENT_SUPPLIER_CODE_UNAVAILABLE' => 'code',
                'PROCUREMENT_SUPPLIER_ITEM_UNAVAILABLE' => 'supplier_sku',
                default => 'procurement',
            } => [$exception->getMessage()],
        ]);
    }
}
