<?php

declare(strict_types=1);

namespace App\Modules\OrderingChannel\Application\Actions;

use App\Modules\OrderingChannel\Application\Exceptions\OrderingChannelException;
use App\Modules\OrderingChannel\Domain\Enums\QrSessionStatus;
use App\Modules\OrderingChannel\Domain\Models\OrderingQrSession;
use Carbon\CarbonImmutable;

final readonly class ResolveQrSession
{
    public function handle(string $token): OrderingQrSession
    {
        [$raw, $signature] = array_pad(explode('.', $token, 2), 2, null);

        if (! is_string($raw) || ! is_string($signature) || ! hash_equals($this->signature($raw), $signature)) {
            throw OrderingChannelException::qrSessionNotFound();
        }

        $session = OrderingQrSession::query()->where('token_hash', hash('sha256', $raw))->first();

        if (! $session instanceof OrderingQrSession) {
            throw OrderingChannelException::qrSessionNotFound();
        }

        if ($session->status !== QrSessionStatus::Active || $session->expires_at->lessThanOrEqualTo(CarbonImmutable::now())) {
            $session->forceFill(['status' => QrSessionStatus::Expired])->save();

            throw OrderingChannelException::qrSessionExpired();
        }

        return $session;
    }

    private function signature(string $raw): string
    {
        return hash_hmac('sha256', $raw, (string) config('app.key'));
    }
}
