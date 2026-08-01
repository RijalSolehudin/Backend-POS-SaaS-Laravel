<?php

declare(strict_types=1);

namespace App\Modules\Recipe\Presentation\Http\Web\Controllers;

use App\Modules\Recipe\Application\Actions\ChangeRecipeStatus;
use App\Modules\Recipe\Application\Actions\CreateRecipe;
use App\Modules\Recipe\Application\Actions\UpdateRecipe;
use App\Modules\Recipe\Application\Data\RecipeInput;
use App\Modules\Recipe\Application\Exceptions\RecipeException;
use App\Modules\Recipe\Domain\Enums\RecipeStatus;
use App\Modules\Recipe\Domain\Models\Recipe;
use App\Modules\Tenancy\Application\Contracts\TenantCatalogReference;
use App\Modules\Tenancy\Application\Data\TenantCatalogSummary;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class TenantRecipeController extends Controller
{
    public function __construct(private TenantCatalogReference $tenancy) {}

    public function index(Request $request): View
    {
        $context = $this->context($request);

        return view('recipe::tenant.recipe.index', [
            'tenant' => $this->tenantView($this->tenant($context)),
            'context' => $context,
            'recipes' => Recipe::query()->where('tenant_id', $context->tenantId)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, CreateRecipe $create): RedirectResponse
    {
        try {
            $create->handle($this->context($request), $this->input($request));
        } catch (RecipeException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Recipe created.');
    }

    public function update(Request $request, string $tenant, string $recipe, UpdateRecipe $update): RedirectResponse
    {
        try {
            $update->handle($this->context($request), $recipe, $this->input($request));
        } catch (RecipeException $exception) {
            throw $this->validation($exception);
        }

        return back()->with('status', 'Recipe updated.');
    }

    public function status(Request $request, string $tenant, string $recipe, ChangeRecipeStatus $change): RedirectResponse
    {
        $input = $request->validate(['status' => ['required', 'in:active,inactive']]);

        try {
            $change->handle($this->context($request), $recipe, RecipeStatus::from((string) $input['status']));
        } catch (RecipeException) {
            abort(404);
        }

        return back()->with('status', 'Recipe status updated.');
    }

    private function input(Request $request): RecipeInput
    {
        $input = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sku' => ['required', 'string', 'max:64'],
            'requires_recipe' => ['nullable', 'boolean'],
        ]);

        return new RecipeInput(
            name: (string) $input['name'],
            sku: (string) $input['sku'],
            requiresRecipe: (bool) ($input['requires_recipe'] ?? false),
        );
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

    private function validation(RecipeException $exception): ValidationException
    {
        return ValidationException::withMessages([
            match ($exception->errorCode()) {
                'RECIPE_SKU_UNAVAILABLE' => 'sku',
                default => 'recipe',
            } => [$exception->getMessage()],
        ]);
    }
}
