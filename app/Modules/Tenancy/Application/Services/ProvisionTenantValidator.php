<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Services;

use App\Modules\Tenancy\Application\Data\ProvisionTenantData;
use Illuminate\Support\Facades\Validator;

final class ProvisionTenantValidator
{
    public function validate(ProvisionTenantData $data): void
    {
        Validator::make([
            'idempotency_key' => $data->idempotencyKey,
            'tenant_name' => $data->tenantName,
            'tenant_code' => $data->tenantCode,
            'outlet_name' => $data->outletName,
            'outlet_code' => $data->outletCode,
            'owner_name' => $data->ownerName,
            'owner_email' => $data->ownerEmail,
            'currency' => $data->currency,
            'timezone' => $data->timezone,
            'reason' => $data->reason,
        ], [
            'idempotency_key' => ['required', 'regex:/^[0-9a-hjkmnp-tv-z]{26}$/'],
            'tenant_name' => ['required', 'string', 'max:160'],
            'tenant_code' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:64'],
            'outlet_name' => ['required', 'string', 'max:120'],
            'outlet_code' => ['required', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', 'max:32'],
            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:254'],
            'currency' => ['required', 'in:'.implode(',', config('tenancy.currencies', ['IDR']))],
            'timezone' => ['required', 'in:'.implode(',', config('tenancy.timezones', ['Asia/Jakarta']))],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ])->validate();
    }
}
