<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class PosDeviceInput
{
    /**
     * @return array{installation_id: string, name: string, platform: string, app_version: string|null}
     *
     * @throws ValidationException
     */
    public function validate(
        string $installationId,
        string $name,
        string $platform,
        ?string $appVersion,
    ): array {
        $input = [
            'installation_id' => mb_strtolower(trim($installationId)),
            'name' => trim($name),
            'platform' => mb_strtolower(trim($platform)),
            'app_version' => $appVersion === null ? null : trim($appVersion),
        ];

        Validator::make($input, [
            'installation_id' => ['required', 'regex:/^[0-9a-hjkmnp-tv-z]{26}$/'],
            'name' => ['required', 'string', 'max:120'],
            'platform' => ['required', 'string', 'max:40'],
            'app_version' => ['nullable', 'string', 'max:40'],
        ])->validate();

        return $input;
    }
}
