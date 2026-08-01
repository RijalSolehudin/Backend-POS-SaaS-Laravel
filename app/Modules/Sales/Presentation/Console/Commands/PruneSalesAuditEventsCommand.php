<?php

declare(strict_types=1);

namespace App\Modules\Sales\Presentation\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

final class PruneSalesAuditEventsCommand extends Command
{
    protected $signature = 'sales:prune-audit-events {--pretend : Count prunable audit events without deleting them}';

    protected $description = 'Prune Sales audit events past configured retention';

    public function handle(): int
    {
        $retentionYears = max(1, (int) config('sales.audit_retention_years', 2));
        $cutoff = now()->subYears($retentionYears);
        $query = DB::table('sales_audit_events')->where('occurred_at', '<', $cutoff);

        if ((bool) $this->option('pretend')) {
            $count = $query->count();
            $this->info("{$count} Sales audit event(s) are older than {$retentionYears} year(s).");

            return SymfonyCommand::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Pruned {$deleted} Sales audit event(s) older than {$retentionYears} year(s).");

        return SymfonyCommand::SUCCESS;
    }
}
