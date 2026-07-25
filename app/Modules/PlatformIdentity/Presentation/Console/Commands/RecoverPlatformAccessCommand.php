<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Console\Commands;

use App\Modules\PlatformIdentity\Application\Actions\RecoverPlatformAccess;
use App\Modules\PlatformIdentity\Application\Data\CliOperatorData;
use App\Modules\PlatformIdentity\Application\Exceptions\PlatformIdentityException;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

final class RecoverPlatformAccessCommand extends Command
{
    protected $signature = 'platform:recover-access';

    protected $description = 'Reset Platform Administrator access and require fresh TOTP enrollment';

    public function handle(RecoverPlatformAccess $recover): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('This command only supports interactive execution.');

            return SymfonyCommand::FAILURE;
        }

        $allowed = config('platform_identity.emergency_allowed_environments', []);

        if (! is_array($allowed) || ! app()->environment($allowed)) {
            $this->error('Emergency recovery is not allowed in this environment.');

            return SymfonyCommand::FAILURE;
        }

        $email = mb_strtolower(trim((string) $this->ask('Platform Administrator email')));
        $identity = trim((string) $this->ask('Operator name or email'));
        $reason = trim((string) $this->ask('Emergency recovery reason'));
        $reference = trim((string) $this->ask('Ticket / incident reference (optional)'));
        $password = (string) $this->secret('New password (12-128 characters)');
        $confirmation = (string) $this->secret('Confirm new password');

        if ($password !== $confirmation || $identity === '' || $reason === '') {
            $this->error('Operator identity, reason, and matching passwords are required.');

            return SymfonyCommand::FAILURE;
        }

        $this->warn('This revokes every platform session, TOTP secret, recovery code, and active challenge.');

        if (! $this->confirm('Continue with emergency recovery?', false)) {
            $this->warn('Emergency recovery cancelled.');

            return SymfonyCommand::FAILURE;
        }

        $operator = new CliOperatorData(
            identity: $identity,
            reason: $reason,
            reference: $reference !== '' ? $reference : null,
            osUser: (string) (getenv('USERNAME') ?: getenv('USER') ?: 'unknown'),
            hostname: (string) (gethostname() ?: 'unknown'),
        );
        $correlationId = strtolower((string) Str::ulid());

        try {
            $user = $recover->handle($email, $password, $operator, $correlationId);
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

        $this->info('Access reset. The account must enroll TOTP again at the next login.');
        $this->line('Platform user ID: '.$user->getKey());
        $this->line('Correlation ID: '.$correlationId);

        return SymfonyCommand::SUCCESS;
    }
}
