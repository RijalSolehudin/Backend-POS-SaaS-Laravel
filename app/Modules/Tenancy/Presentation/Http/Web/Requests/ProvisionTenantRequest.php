<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Web\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

final class ProvisionTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('platform')->check();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => mb_strtolower(trim((string) $this->input('idempotency_key'))),
            'tenant_name' => trim((string) $this->input('tenant_name')),
            'tenant_code' => mb_strtolower(trim((string) $this->input('tenant_code'))),
            'outlet_name' => trim((string) $this->input('outlet_name')),
            'outlet_code' => mb_strtoupper(trim((string) $this->input('outlet_code'))),
            'owner_name' => trim((string) $this->input('owner_name')),
            'owner_email' => mb_strtolower(trim((string) $this->input('owner_email'))),
            'currency' => mb_strtoupper(trim((string) $this->input('currency'))),
            'timezone' => trim((string) $this->input('timezone')),
            'reason' => trim((string) $this->input('reason')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'regex:/^[0-9a-hjkmnp-tv-z]{26}$/'],
            'tenant_name' => ['required', 'string', 'max:160'],
            'tenant_code' => ['required', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', 'max:64'],
            'outlet_name' => ['required', 'string', 'max:120'],
            'outlet_code' => ['required', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', 'max:32'],
            'owner_name' => ['required', 'string', 'max:120'],
            'owner_email' => ['required', 'email', 'max:254'],
            'password' => [
                'required',
                'confirmed',
                'max:'.(int) config('identity.password.max', 128),
                Password::min((int) config('identity.password.min', 12)),
            ],
            'currency' => ['required', 'in:'.implode(',', config('tenancy.currencies', ['IDR']))],
            'timezone' => ['required', 'in:'.implode(',', config('tenancy.timezones', ['Asia/Jakarta']))],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
