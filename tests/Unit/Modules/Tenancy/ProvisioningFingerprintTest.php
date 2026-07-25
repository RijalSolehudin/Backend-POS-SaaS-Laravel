<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Tenancy;

use App\Modules\Tenancy\Application\Data\ProvisionTenantData;
use App\Modules\Tenancy\Application\Services\ProvisioningFingerprint;
use Tests\TestCase;

final class ProvisioningFingerprintTest extends TestCase
{
    public function test_it_canonicalizes_non_secret_input_and_never_returns_plaintext(): void
    {
        config()->set('app.key', 'base64:test-application-key');
        $service = new ProvisioningFingerprint;

        $first = $service->for($this->data(
            tenantName: ' Kopi Nusantara ',
            tenantCode: 'KOPI-NUSANTARA',
            ownerEmail: 'OWNER@EXAMPLE.COM',
        ));
        $second = $service->for($this->data(
            tenantName: 'Kopi Nusantara',
            tenantCode: 'kopi-nusantara',
            ownerEmail: 'owner@example.com',
        ));

        self::assertSame($first, $second);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $first);
        self::assertStringNotContainsString('correct horse battery staple', $first);
    }

    public function test_password_change_produces_a_different_fingerprint(): void
    {
        config()->set('app.key', 'base64:test-application-key');
        $service = new ProvisioningFingerprint;

        $first = $service->for($this->data(ownerPassword: 'correct horse battery staple'));
        $second = $service->for($this->data(ownerPassword: 'another secure owner password'));

        self::assertNotSame($first, $second);
    }

    private function data(
        string $tenantName = 'Kopi Nusantara',
        string $tenantCode = 'kopi-nusantara',
        string $ownerEmail = 'owner@example.com',
        string $ownerPassword = 'correct horse battery staple',
    ): ProvisionTenantData {
        return new ProvisionTenantData(
            idempotencyKey: '01k123456789abcdefghjkmnp',
            tenantName: $tenantName,
            tenantCode: $tenantCode,
            outletName: 'Main Outlet',
            outletCode: 'MAIN',
            ownerName: 'Tenant Owner',
            ownerEmail: $ownerEmail,
            ownerPassword: $ownerPassword,
            currency: 'IDR',
            timezone: 'Asia/Jakarta',
            reason: 'Pilot onboarding',
        );
    }
}
