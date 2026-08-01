<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure\Persistence;

use App\Modules\Identity\Application\Contracts\PosTokenIssuer;
use App\Modules\Identity\Application\Data\IssuedPosToken;
use App\Modules\Identity\Domain\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class DatabasePosTokenIssuer implements PosTokenIssuer
{
    public function replaceForDevice(string $userId, string $deviceId, CarbonImmutable $expiresAt): IssuedPosToken
    {
        /** @var User $user */
        $user = User::query()->findOrFail($userId);

        DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('tokenable_id', $userId)
            ->where('pos_device_id', $deviceId)
            ->delete();

        $accessToken = $user->createToken(
            name: 'POS device',
            abilities: ['pos:*'],
            expiresAt: $expiresAt,
        );
        $accessToken->accessToken
            ->forceFill(['pos_device_id' => $deviceId])
            ->save();

        return new IssuedPosToken($accessToken->plainTextToken, $expiresAt);
    }
}
