<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Console\Commands;

use App\Modules\Tenancy\Application\Actions\ProvisionTenant;
use App\Modules\Tenancy\Application\Data\ProvisionTenantData;
use App\Modules\Tenancy\Application\Exceptions\TenantProvisioningException;
use App\Shared\Application\Context\ActorContext;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

final class ProvisionTenantCommand extends Command
{
    protected $signature = 'tenant:provision';

    protected $description = 'Provision a tenant, initial outlet, and Tenant Owner through controlled interactive input';

    public function handle(ProvisionTenant $provisionTenant): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('This command only supports interactive execution.');

            return SymfonyCommand::FAILURE;
        }

        $operator = trim((string) $this->ask('Operator name or email'));
        $reason = trim((string) $this->ask('Provisioning reason / ticket'));

        if ($operator === '' || $reason === '') {
            $this->error('Operator identity and reason are required.');

            return SymfonyCommand::FAILURE;
        }

        $generatedKey = strtolower((string) Str::ulid());
        $idempotencyKey = mb_strtolower(trim((string) $this->ask('Idempotency key', $generatedKey)));
        $tenantName = trim((string) $this->ask('Tenant name'));
        $tenantCode = mb_strtolower(trim((string) $this->ask('Tenant code')));
        $currency = mb_strtoupper(trim((string) $this->ask('Currency', (string) config('tenancy.defaults.currency', 'IDR'))));
        $timezone = trim((string) $this->ask('Timezone', (string) config('tenancy.defaults.timezone', 'Asia/Jakarta')));
        $outletName = trim((string) $this->ask('Initial outlet name'));
        $outletCode = mb_strtoupper(trim((string) $this->ask('Initial outlet code', (string) config('tenancy.defaults.outlet_code', 'MAIN'))));
        $ownerName = trim((string) $this->ask('Tenant Owner name'));
        $ownerEmail = mb_strtolower(trim((string) $this->ask('Tenant Owner email')));
        $password = (string) $this->secret('Initial owner password (12-128 characters)');
        $confirmation = (string) $this->secret('Confirm initial owner password');

        if ($password !== $confirmation) {
            $this->error('Password confirmation does not match.');

            return SymfonyCommand::FAILURE;
        }

        $this->warn('This atomically creates the tenant, outlet, owner identity, membership, and owner role.');

        if (! $this->confirm('Provision this tenant?', false)) {
            $this->warn('Tenant provisioning cancelled.');

            return SymfonyCommand::FAILURE;
        }

        $correlationId = strtolower((string) Str::ulid());

        try {
            $result = $provisionTenant->handle(
                new ProvisionTenantData(
                    idempotencyKey: $idempotencyKey,
                    tenantName: $tenantName,
                    tenantCode: $tenantCode,
                    outletName: $outletName,
                    outletCode: $outletCode,
                    ownerName: $ownerName,
                    ownerEmail: $ownerEmail,
                    ownerPassword: $password,
                    currency: $currency,
                    timezone: $timezone,
                    reason: $reason,
                ),
                new ActorContext(
                    actorType: 'cli_operator_claim',
                    actorId: $operator,
                    correlationId: $correlationId,
                ),
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return SymfonyCommand::FAILURE;
        } catch (TenantProvisioningException $exception) {
            $this->error($exception->getMessage());

            return SymfonyCommand::FAILURE;
        }

        $this->info($result->wasReplayed ? 'Original provisioning result returned.' : 'Tenant provisioned successfully.');
        $this->line('Tenant ID: '.$result->tenantId);
        $this->line('Initial outlet ID: '.$result->outletId);
        $this->line('Owner user ID: '.$result->ownerUserId);
        $this->line('Idempotency key: '.$idempotencyKey);
        $this->line('Correlation ID: '.$correlationId);

        return SymfonyCommand::SUCCESS;
    }
}
