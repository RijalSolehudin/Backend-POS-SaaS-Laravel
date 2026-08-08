<?php

declare(strict_types=1);

namespace App\Modules\Sync\Presentation\Http\Api\Controllers;

use App\Modules\Sync\Application\Actions\GetOfflineCatalogSnapshot;
use App\Modules\Sync\Application\Actions\GetSyncBootstrapPolicy;
use App\Modules\Sync\Application\Actions\ProcessSyncMutation;
use App\Modules\Sync\Application\Actions\PullSyncOutbox;
use App\Modules\Sync\Application\Data\SyncMutationInput;
use App\Modules\Sync\Domain\Models\SyncOutboxRecord;
use App\Modules\Tenancy\Application\Actions\ResolvePosOutletApiContext;
use App\Modules\Tenancy\Application\Data\PosOutletContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Sanctum\Contracts\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class SyncController extends Controller
{
    public function bootstrap(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        GetSyncBootstrapPolicy $bootstrap,
    ): JsonResponse {
        $posContext = $this->context($outlet, $request, $context);

        return response()->json([
            'data' => $bootstrap->handle($posContext->tenantId, $posContext->outletId, $posContext->deviceId),
        ]);
    }

    public function catalogSnapshot(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        GetOfflineCatalogSnapshot $snapshot,
    ): JsonResponse {
        return response()->json([
            'data' => $snapshot->handle($this->context($outlet, $request, $context)),
        ]);
    }

    public function push(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        ProcessSyncMutation $sync,
    ): JsonResponse {
        /** @var array{client_record_id: string, action: string, sequence_number: int, idempotency_key: string, payload_hash: string, payload: array<string, mixed>} $validated */
        $validated = $request->validate([
            'client_record_id' => ['required', 'string', 'max:120'],
            'action' => ['required', 'string', 'max:80'],
            'sequence_number' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['required', 'string', 'max:120'],
            'payload_hash' => ['required', 'string', 'size:64'],
            'payload' => ['required', 'array'],
        ]);
        $posContext = $this->context($outlet, $request, $context);
        $result = $sync->handle($posContext, new SyncMutationInput(
            tenantId: $posContext->tenantId,
            outletId: $posContext->outletId,
            deviceId: $posContext->deviceId,
            clientRecordId: $validated['client_record_id'],
            action: $validated['action'],
            sequenceNumber: $validated['sequence_number'],
            idempotencyKey: $validated['idempotency_key'],
            payloadHash: $validated['payload_hash'],
            payload: $validated['payload'],
        ));

        return response()->json([
            'data' => [
                'status' => $result->status,
                'resource_type' => $result->resourceType,
                'resource_id' => $result->resourceId,
                'response' => $result->response,
            ],
        ], $result->status === 'accepted' ? 202 : 200);
    }

    public function pull(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
        PullSyncOutbox $pull,
    ): JsonResponse {
        /** @var array{after_cursor?: string|null, limit?: int|null} $validated */
        $validated = $request->validate([
            'after_cursor' => ['nullable', 'string', 'size:26'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $records = $pull->handle(
            $this->context($outlet, $request, $context),
            $validated['after_cursor'] ?? null,
            $validated['limit'] ?? 100,
        );

        return response()->json([
            'data' => array_map(
                fn (SyncOutboxRecord $record): array => [
                    'cursor' => $record->id,
                    'event_type' => $record->event_type,
                    'resource_type' => $record->resource_type,
                    'resource_id' => $record->resource_id,
                    'payload' => $record->payload,
                    'created_at' => $record->created_at?->toISOString(),
                ],
                $records,
            ),
        ]);
    }

    private function context(
        string $outlet,
        Request $request,
        ResolvePosOutletApiContext $context,
    ): PosOutletContext {
        $user = $request->user();

        if (! $user instanceof HasApiTokens) {
            throw new UnauthorizedHttpException('Bearer', 'A valid POS access token is required.');
        }

        $token = $this->bearerToken($request, (string) $user->getAuthIdentifier());

        if (! $token instanceof PersonalAccessToken) {
            throw new UnauthorizedHttpException('Bearer', 'A valid POS device token is required.');
        }

        $deviceId = $token->getAttribute('pos_device_id');

        if (! is_string($deviceId) || $deviceId === '') {
            throw new UnauthorizedHttpException('Bearer', 'A valid POS device token is required.');
        }

        return $context->handle((string) $user->getAuthIdentifier(), $deviceId, $outlet);
    }

    private function bearerToken(Request $request, string $userId): ?PersonalAccessToken
    {
        $plainToken = $request->bearerToken();

        if (! is_string($plainToken) || $plainToken === '') {
            return null;
        }

        $token = PersonalAccessToken::findToken($plainToken);

        if (! $token instanceof PersonalAccessToken || $token->getAttribute('tokenable_id') !== $userId) {
            return null;
        }

        return $token;
    }
}
