<?php

declare(strict_types=1);

namespace App\Modules\Identity\Application\Actions;

use App\Modules\Identity\Application\Contracts\InitialTenantOwnerCreator;
use App\Modules\Identity\Application\Data\InitialTenantOwnerData;
use App\Modules\Identity\Application\Data\InitialTenantOwnerResult;
use App\Modules\Identity\Application\Exceptions\IdentityException;
use App\Modules\Identity\Application\Services\TenantPasswordPolicy;
use App\Modules\Identity\Domain\Enums\PredefinedRole;
use App\Modules\Identity\Domain\Enums\UserStatus;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Domain\Models\UserRoleAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final readonly class CreateInitialTenantOwner implements InitialTenantOwnerCreator
{
    public function __construct(
        private TenantPasswordPolicy $passwordPolicy,
    ) {}

    public function handle(InitialTenantOwnerData $data): InitialTenantOwnerResult
    {
        $name = trim($data->name);
        $email = mb_strtolower(trim($data->email));

        Validator::make(
            ['name' => $name, 'email' => $email],
            [
                'name' => ['required', 'string', 'max:120'],
                'email' => ['required', 'email', 'max:254'],
            ],
        )->validate();
        $this->passwordPolicy->validate($data->password);

        return DB::transaction(function () use ($data, $name, $email): InitialTenantOwnerResult {
            if (User::query()->where('email', $email)->exists()) {
                throw IdentityException::emailUnavailable();
            }

            $user = User::query()->create([
                'name' => $name,
                'email' => $email,
                'password' => $data->password,
                'status' => UserStatus::Active,
                'must_change_password' => true,
                'password_changed_at' => now(),
            ]);

            $assignment = UserRoleAssignment::query()->create([
                'user_id' => $user->getKey(),
                'role' => PredefinedRole::TenantOwner,
            ]);

            return new InitialTenantOwnerResult(
                userId: (string) $user->getKey(),
                roleAssignmentId: (string) $assignment->getKey(),
                normalizedEmail: $email,
            );
        });
    }
}
