<?php

declare(strict_types=1);

namespace App\Modules\Sales\Presentation\Http\Web\Controllers;

use App\Modules\Sales\Application\Actions\VoidCompletedOrder;
use App\Modules\Sales\Application\Exceptions\OrderException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;

final class TenantOrderVoidController extends Controller
{
    public function __invoke(
        string $tenant,
        string $order,
        Request $request,
        VoidCompletedOrder $void,
    ): RedirectResponse {
        $user = $request->user();
        abort_if($user === null, 401);

        /** @var array{reason: string, idempotency_key?: string|null} $validated */
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ]);
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey) || trim($idempotencyKey) === '') {
            $idempotencyKey = $validated['idempotency_key'] ?? '';
        }

        if (trim($idempotencyKey) === '') {
            throw OrderException::idempotencyKeyRequired();
        }

        try {
            $void->handle($tenant, $order, (string) $user->getAuthIdentifier(), $validated['reason'], $idempotencyKey);
        } catch (OrderException $exception) {
            throw ValidationException::withMessages(['reason' => $exception->getMessage()]);
        }

        return back()->with('status', 'Order voided.');
    }
}
