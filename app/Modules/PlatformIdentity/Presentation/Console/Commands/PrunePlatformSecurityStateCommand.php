<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

final class PrunePlatformSecurityStateCommand extends Command
{
    protected $signature = 'platform:prune-security-state';

    protected $description = 'Prune expired Platform sessions and audit events past retention';

    public function handle(): int
    {
        $idleCutoff = now()->subMinutes((int) config('platform_identity.session.idle_minutes', 15))->getTimestamp();
        $absoluteCutoff = now()->subMinutes((int) config('platform_identity.session.absolute_minutes', 240));

        $sessions = DB::table('platform_sessions')
            ->where('last_activity', '<', $idleCutoff)
            ->orWhere('created_at', '<', $absoluteCutoff)
            ->delete();

        $auditEvents = DB::table('platform_security_audit_events')
            ->where('occurred_at', '<', now()->subYear())
            ->delete();

        $this->info("Pruned {$sessions} session(s) and {$auditEvents} audit event(s).");

        return SymfonyCommand::SUCCESS;
    }
}
