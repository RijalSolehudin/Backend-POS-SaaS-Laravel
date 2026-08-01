<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Presentation\Http\Web\Controllers;

use App\Modules\Inventory\Application\Actions\ChangeInventoryItemStatus;
use App\Modules\Inventory\Application\Actions\ChangeInventoryUnitStatus;
use App\Modules\Inventory\Application\Actions\CreateInventoryItem;
use App\Modules\Inventory\Application\Actions\CreateInventoryUnit;
use App\Modules\Inventory\Application\Actions\RecordOpeningBalance;
use App\Modules\Inventory\Application\Actions\SetInventoryItemOutletSettings;
use App\Modules\Inventory\Application\Actions\UpdateInventoryItem;
use App\Modules\Inventory\Application\Actions\UpdateInventoryUnit;
use App\Modules\Inventory\Application\Data\InventoryItemInput;
use App\Modules\Inventory\Application\Data\InventoryItemOutletSettingsInput;
use App\Modules\Inventory\Application\Data\InventoryUnitInput;
use App\Modules\Inventory\Application\Data\OpeningBalanceInput;
use App\Modules\Inventory\Application\Exceptions\InventoryException;
use App\Modules\Inventory\Domain\Enums\InventoryStatus;
use App\Modules\Inventory\Domain\Models\InventoryBalance;
use App\Modules\Inventory\Domain\Models\InventoryItem;
use App\Modules\Inventory\Domain\Models\InventoryItemOutletSetting;
use App\Modules\Inventory\Domain\Models\InventoryUnit;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantCatalogSummary;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantInventoryController extends Controller
{
    public function __construct(private TenantCatalogReference $tenancy) {}

    public function index(Request $request): View
    {
        $context = $this->context($request);
        $tenant = $this->tenant($context);

        return view('inventory::tenant.inventory.index', [
            'tenant' => $this->tenantView($tenant),
            'context' => $context,
            'units' => InventoryUnit::query()
                ->where('tenant_id', $context->tenantId)
                ->orderBy('name')
                ->get(),
            'items' => InventoryItem::query()
                ->where('tenant_id', $context->tenantId)
                ->orderBy('name')
                ->get(),
            'settings' => InventoryItemOutletSetting::query()
                ->where('tenant_id', $context->tenantId)
                ->get()
                ->keyBy(fn (InventoryItemOutletSetting $setting): string => $setting->item_id.'|'.$setting->outlet_id),
            'balances' => InventoryBalance::query()
                ->where('tenant_id', $context->tenantId)
                ->get()
                ->keyBy(fn (InventoryBalance $balance): string => $balance->item_id.'|'.$balance->outlet_id),
            'outlets' => $this->tenancy->activeOutlets($context->tenantId),
        ]);
    }

    public function storeUnit(Request $request, CreateInventoryUnit $create): RedirectResponse
    {
        try {
            $create->handle($this->context($request), $this->validatedUnit($request));
        } catch (InventoryException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Inventory unit created.');
    }

    public function updateUnit(
        Request $request,
        string $tenant,
        string $unit,
        UpdateInventoryUnit $update,
    ): RedirectResponse {
        try {
            $update->handle($this->context($request), $unit, $this->validatedUnit($request));
        } catch (InventoryException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Inventory unit updated.');
    }

    public function changeUnitStatus(
        Request $request,
        string $tenant,
        string $unit,
        ChangeInventoryUnitStatus $change,
    ): RedirectResponse {
        $input = $request->validate(['status' => ['required', 'in:active,inactive']]);

        try {
            $change->handle(
                $this->context($request),
                $unit,
                InventoryStatus::from((string) $input['status']),
            );
        } catch (InventoryException) {
            abort(404);
        }

        return back()->with('status', 'Inventory unit status updated.');
    }

    public function storeItem(Request $request, CreateInventoryItem $create): RedirectResponse
    {
        try {
            $create->handle($this->context($request), $this->validatedItem($request));
        } catch (InventoryException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Inventory item created.');
    }

    public function updateItem(
        Request $request,
        string $tenant,
        string $item,
        UpdateInventoryItem $update,
    ): RedirectResponse {
        try {
            $update->handle($this->context($request), $item, $this->validatedItem($request));
        } catch (InventoryException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Inventory item updated.');
    }

    public function changeItemStatus(
        Request $request,
        string $tenant,
        string $item,
        ChangeInventoryItemStatus $change,
    ): RedirectResponse {
        $input = $request->validate(['status' => ['required', 'in:active,inactive']]);

        try {
            $change->handle(
                $this->context($request),
                $item,
                InventoryStatus::from((string) $input['status']),
            );
        } catch (InventoryException) {
            abort(404);
        }

        return back()->with('status', 'Inventory item status updated.');
    }

    public function setOutletSettings(
        Request $request,
        string $tenant,
        string $item,
        SetInventoryItemOutletSettings $settings,
    ): RedirectResponse {
        $input = $request->validate([
            'outlet_id' => ['required', 'string', 'size:26'],
            'status' => ['required', 'in:active,inactive'],
            'low_stock_threshold_quantity' => ['required', 'decimal:0,3', 'min:0'],
        ]);

        try {
            $settings->handle(
                $this->context($request),
                new InventoryItemOutletSettingsInput(
                    outletId: (string) $input['outlet_id'],
                    itemId: $item,
                    status: InventoryStatus::from((string) $input['status']),
                    lowStockThresholdQuantity: (string) $input['low_stock_threshold_quantity'],
                ),
            );
        } catch (InventoryException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Inventory outlet settings updated.');
    }

    public function recordOpeningBalance(
        Request $request,
        string $tenant,
        string $item,
        RecordOpeningBalance $openingBalance,
    ): RedirectResponse {
        $input = $request->validate([
            'outlet_id' => ['required', 'string', 'size:26'],
            'quantity' => ['required', 'decimal:0,3', 'min:0'],
            'total_cost_minor' => ['required', 'integer', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'reason' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);

        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey) || trim($idempotencyKey) === '') {
            $idempotencyKey = (string) ($input['idempotency_key'] ?? '');
        }

        try {
            $movement = $openingBalance->handle(
                $this->context($request),
                new OpeningBalanceInput(
                    outletId: (string) $input['outlet_id'],
                    itemId: $item,
                    quantity: (string) $input['quantity'],
                    totalCostMinor: (int) $input['total_cost_minor'],
                    currency: (string) $input['currency'],
                    reason: ($input['reason'] ?? null) === null || $input['reason'] === '' ? null : (string) $input['reason'],
                ),
                $idempotencyKey,
            );
        } catch (InventoryException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Opening balance recorded: '.$movement->id);
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

    private function validatedUnit(Request $request): InventoryUnitInput
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'symbol' => ['required', 'string', 'max:24'],
            'precision' => ['required', 'integer', 'between:0,3'],
        ]);

        return new InventoryUnitInput(
            name: (string) $input['name'],
            symbol: (string) $input['symbol'],
            precision: (int) $input['precision'],
        );
    }

    private function validatedItem(Request $request): InventoryItemInput
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['required', 'string', 'max:64'],
            'unit_id' => ['required', 'string', 'size:26'],
        ]);

        return new InventoryItemInput(
            unitId: (string) $input['unit_id'],
            name: (string) $input['name'],
            sku: (string) $input['sku'],
        );
    }

    private function validation(InventoryException $exception): ValidationException
    {
        return ValidationException::withMessages([
            match ($exception->errorCode()) {
                'INVENTORY_UNIT_NOT_FOUND',
                'INVENTORY_CROSS_TENANT_REFERENCE' => 'unit_id',
                'INVENTORY_SKU_UNAVAILABLE' => 'sku',
                'INVENTORY_UNIT_SYMBOL_UNAVAILABLE' => 'symbol',
                'INVENTORY_OUTLET_NOT_FOUND' => 'outlet_id',
                'INVENTORY_CURRENCY_MISMATCH' => 'currency',
                'INVENTORY_OPENING_BALANCE_ALREADY_RECORDED',
                'INVENTORY_IDEMPOTENCY_CONFLICT',
                'INVENTORY_IDEMPOTENCY_KEY_REQUIRED' => 'idempotency_key',
                default => 'item',
            } => [$exception->getMessage()],
        ]);
    }
}
