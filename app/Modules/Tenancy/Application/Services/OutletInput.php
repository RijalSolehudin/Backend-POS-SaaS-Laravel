<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Application\Services;

use Illuminate\Support\Facades\Validator;

final class OutletInput
{
    /**
     * @return array{name: string, code: string}
     */
    public function validate(string $name, string $code): array
    {
        $input = [
            'name' => trim($name),
            'code' => mb_strtoupper(trim($code)),
        ];

        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'regex:/^[A-Z0-9][A-Z0-9_-]*$/', 'max:32'],
        ])->validate();

        return $input;
    }
}
