<?php

declare(strict_types=1);

namespace App\Modules\Dining\Presentation\Http\Web\Controllers;

use App\Modules\Dining\Application\Actions\ChangeDiningFloorStatus;
use App\Modules\Dining\Application\Actions\ChangeDiningTableStatus;
use App\Modules\Dining\Application\Actions\CreateDiningFloor;
use App\Modules\Dining\Application\Actions\CreateDiningTable;
use App\Modules\Dining\Application\Actions\UpdateDiningFloor;
use App\Modules\Dining\Application\Actions\UpdateDiningTable;
use App\Modules\Dining\Application\Data\DiningFloorInput;
use App\Modules\Dining\Application\Data\DiningTableInput;
use App\Modules\Dining\Application\Exceptions\DiningException;
use App\Modules\Dining\Domain\Enums\TableStatus;
use App\Modules\Dining\Domain\Models\DiningFloor;
use App\Modules\Dining\Domain\Models\DiningTable;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantCatalogSummary;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantDiningController extends Controller
{
    public function __construct(private TenantCatalogReference $tenancy) {}

    public function index(Request $request): View
    {
        $context = $this->context($request);

        return view('dining::tenant.dining.index', [
            'tenant' => $this->tenantView($this->tenant($context)),
            'context' => $context,
            'outlets' => $this->tenancy->activeOutlets($context->tenantId),
            'floors' => DiningFloor::query()
                ->where('tenant_id', $context->tenantId)
                ->orderBy('outlet_id')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
            'tables' => DiningTable::query()
                ->where('tenant_id', $context->tenantId)
                ->orderBy('outlet_id')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function storeFloor(Request $request, CreateDiningFloor $create): RedirectResponse
    {
        try {
            $create->handle($this->context($request), $this->validatedFloor($request));
        } catch (DiningException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Dining floor created.');
    }

    public function updateFloor(
        Request $request,
        string $tenant,
        string $floor,
        UpdateDiningFloor $update,
    ): RedirectResponse {
        try {
            $update->handle($this->context($request), $floor, $this->validatedFloor($request));
        } catch (DiningException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Dining floor updated.');
    }

    public function changeFloorStatus(
        Request $request,
        string $tenant,
        string $floor,
        ChangeDiningFloorStatus $change,
    ): RedirectResponse {
        $input = $request->validate(['status' => ['required', 'in:active,inactive']]);

        try {
            $change->handle($this->context($request), $floor, TableStatus::from((string) $input['status']));
        } catch (DiningException) {
            abort(404);
        }

        return back()->with('status', 'Dining floor status updated.');
    }

    public function storeTable(Request $request, CreateDiningTable $create): RedirectResponse
    {
        try {
            $create->handle($this->context($request), $this->validatedTable($request));
        } catch (DiningException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Dining table created.');
    }

    public function updateTable(
        Request $request,
        string $tenant,
        string $table,
        UpdateDiningTable $update,
    ): RedirectResponse {
        try {
            $update->handle($this->context($request), $table, $this->validatedTable($request));
        } catch (DiningException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Dining table updated.');
    }

    public function changeTableStatus(
        Request $request,
        string $tenant,
        string $table,
        ChangeDiningTableStatus $change,
    ): RedirectResponse {
        $input = $request->validate(['status' => ['required', 'in:active,inactive']]);

        try {
            $change->handle($this->context($request), $table, TableStatus::from((string) $input['status']));
        } catch (DiningException) {
            abort(404);
        }

        return back()->with('status', 'Dining table status updated.');
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

    private function validatedFloor(Request $request): DiningFloorInput
    {
        $input = $request->validate([
            'outlet_id' => ['required', 'string', 'size:26'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32'],
            'display_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        return new DiningFloorInput(
            outletId: (string) $input['outlet_id'],
            name: (string) $input['name'],
            code: (string) $input['code'],
            displayOrder: (int) $input['display_order'],
        );
    }

    private function validatedTable(Request $request): DiningTableInput
    {
        $input = $request->validate([
            'outlet_id' => ['required', 'string', 'size:26'],
            'floor_id' => ['required', 'string', 'size:26'],
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:32'],
            'capacity' => ['required', 'integer', 'min:1', 'max:999'],
            'display_order' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        return new DiningTableInput(
            outletId: (string) $input['outlet_id'],
            floorId: (string) $input['floor_id'],
            name: (string) $input['name'],
            code: (string) $input['code'],
            capacity: (int) $input['capacity'],
            displayOrder: (int) $input['display_order'],
        );
    }

    private function validation(DiningException $exception): ValidationException
    {
        return ValidationException::withMessages([
            match ($exception->errorCode()) {
                'DINING_OUTLET_NOT_FOUND',
                'DINING_FLOOR_OUTLET_IMMUTABLE' => 'outlet_id',
                'DINING_FLOOR_NOT_FOUND',
                'DINING_CROSS_OUTLET_FLOOR' => 'floor_id',
                'DINING_FLOOR_CODE_UNAVAILABLE',
                'DINING_TABLE_CODE_UNAVAILABLE' => 'code',
                default => 'table',
            } => [$exception->getMessage()],
        ]);
    }
}
