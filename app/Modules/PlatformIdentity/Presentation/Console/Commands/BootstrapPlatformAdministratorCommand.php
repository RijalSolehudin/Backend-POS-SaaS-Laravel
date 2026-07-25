<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Console\Commands;

use App\Modules\PlatformIdentity\Application\Actions\BootstrapPlatformAdministrator;
use App\Modules\PlatformIdentity\Application\Data\CliOperatorData;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

final class BootstrapPlatformAdministratorCommand extends Command
{
    protected $signature = 'platform:bootstrap';

    protected $description = 'Bootstrap the first Platform Administrator through an interactive prompt';

    public function handle(BootstrapPlatformAdministrator $bootstrap): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('This command only supports interactive execution.');

            return SymfonyCommand::FAILURE;
        }

        $name = trim((string) $this->ask('Platform Administrator name'));
        $email = mb_strtolower(trim((string) $this->ask('Platform Administrator email')));
        $operator = $this->operatorData('Initial Platform Administrator bootstrap');
        $password = (string) $this->secret('Password (12-128 characters)');
        $confirmation = (string) $this->secret('Confirm password');

        if ($password !== $confirmation) {
            $this->error('Password confirmation does not match.');

            return SymfonyCommand::FAILURE;
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:254']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return SymfonyCommand::FAILURE;
        }

        if (! $this->confirm('Create the first Platform Administrator?', false)) {
            $this->warn('Bootstrap cancelled.');

            return SymfonyCommand::FAILURE;
        }

        $correlationId = strtolower((string) Str::ulid());

        try {
            $user = $bootstrap->handle($name, $email, $password, $operator, $correlationId);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return SymfonyCommand::FAILURE;
        } catch (PlatformIdentityException $exception) {
            $this->error($exception->getMessage());

            return SymfonyCommand::FAILURE;
        }

        $this->info('Platform Administrator created. Complete TOTP enrollment at /platform/login.');
        $this->line('Platform user ID: '.$user->getKey());
        $this->line('Correlation ID: '.$correlationId);

        return SymfonyCommand::SUCCESS;
    }

    private function operatorData(string $defaultReason): CliOperatorData
    {
        $identity = trim((string) $this->ask('Operator name or email'));
        $reason = trim((string) $this->ask('Reason', $defaultReason));
        $reference = trim((string) $this->ask('Ticket / incident reference (optional)'));

        return new CliOperatorData(
            identity: $identity,
            reason: $reason,
            reference: $reference !== '' ? $reference : null,
            osUser: (string) (getenv('USERNAME') ?: getenv('USER') ?: 'unknown'),
            hostname: (string) (gethostname() ?: 'unknown'),
        );
    }
}
