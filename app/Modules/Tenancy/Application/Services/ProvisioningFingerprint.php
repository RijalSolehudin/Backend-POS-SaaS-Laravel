<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Services;

use App\Modules\Tenancy\Application\Data\ProvisionTenantData;
use JsonException;
use RuntimeException;

final class ProvisioningFingerprint
{
    /**
     * @throws JsonException
     */
    public function for(ProvisionTenantData $data): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('Application key is required for provisioning fingerprint generation.');
        }

        $payload = json_encode([
            'tenant_name' => trim($data->tenantName),
            'tenant_code' => mb_strtolower(trim($data->tenantCode)),
            'outlet_name' => trim($data->outletName),
            'outlet_code' => mb_strtoupper(trim($data->outletCode)),
            'owner_name' => trim($data->ownerName),
            'owner_email' => mb_strtolower(trim($data->ownerEmail)),
            'owner_password' => $data->ownerPassword,
            'currency' => mb_strtoupper(trim($data->currency)),
            'timezone' => trim($data->timezone),
            'reason' => trim($data->reason),
        ], JSON_THROW_ON_ERROR);

        return hash_hmac('sha256', $payload, $key);
    }
}
