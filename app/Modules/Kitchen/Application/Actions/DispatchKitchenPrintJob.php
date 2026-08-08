<?php

declare(strict_types=1);

namespace App\Modules\Kitchen\Application\Actions;

use App\Modules\Kitchen\Application\Contracts\KitchenPrinterDispatcher;
use App\Modules\Kitchen\Application\Exceptions\KitchenException;
use App\Modules\Kitchen\Domain\Enums\PrintJobStatus;
use App\Modules\Kitchen\Domain\Models\KitchenPrintJob;
use App\Modules\Kitchen\Domain\Models\KitchenTicket;
use App\Modules\Tenancy\Application\Data\TenantRequestContext;
use App\Modules\Tenancy\Application\Services\TenantPermissionGuard;
use Carbon\CarbonImmutable;

final readonly class DispatchKitchenPrintJob
{
    public function __construct(
        private TenantPermissionGuard $permissions,
        private KitchenPrinterDispatcher $dispatcher,
    ) {}

    public function handle(TenantRequestContext $context, string $outletId, string $ticketId, string $jobType = 'chit'): KitchenPrintJob
    {
        $this->permissions->authorizeOperatePos($context, $outletId);
        $ticket = $this->ticket($context, $outletId, $ticketId);

        return $this->createAndDispatch($context, $ticket, $jobType, null, null);
    }

    public function retry(TenantRequestContext $context, string $outletId, string $printJobId): KitchenPrintJob
    {
        $this->permissions->authorizeOperatePos($context, $outletId);
        $source = KitchenPrintJob::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($printJobId)
            ->first();

        if (! $source instanceof KitchenPrintJob) {
            throw KitchenException::printJobNotFound();
        }

        if ($source->status !== PrintJobStatus::Failed) {
            throw KitchenException::printFailed('Only failed print jobs can be retried.');
        }

        $ticket = $this->ticket($context, $outletId, $source->ticket_id);

        return $this->createAndDispatch($context, $ticket, $source->job_type, 'Retry failed print job.', $source->id);
    }

    public function reprint(TenantRequestContext $context, string $outletId, string $ticketId, string $reason): KitchenPrintJob
    {
        $this->permissions->authorizeOperatePos($context, $outletId);
        $ticket = $this->ticket($context, $outletId, $ticketId);

        return $this->createAndDispatch($context, $ticket, 'reprint', trim($reason), null);
    }

    private function createAndDispatch(
        TenantRequestContext $context,
        KitchenTicket $ticket,
        string $jobType,
        ?string $reason,
        ?string $sourcePrintJobId,
    ): KitchenPrintJob {
        $job = KitchenPrintJob::query()->create([
            'tenant_id' => $context->tenantId,
            'outlet_id' => $ticket->outlet_id,
            'ticket_id' => $ticket->id,
            'source_print_job_id' => $sourcePrintJobId,
            'job_type' => $jobType,
            'status' => PrintJobStatus::Queued,
            'requested_by' => $context->userId,
            'reason' => $reason,
            'payload' => [
                'ticket_id' => $ticket->id,
                'station_id' => $ticket->station_id,
                'order_number' => $ticket->order_number,
            ],
        ]);

        $result = $this->dispatcher->dispatch($job);

        $job->forceFill([
            'status' => $result->sent ? PrintJobStatus::Sent : PrintJobStatus::Failed,
            'sent_at' => $result->sent ? CarbonImmutable::now() : null,
            'error_message' => $result->errorMessage,
        ])->save();

        return $job;
    }

    private function ticket(TenantRequestContext $context, string $outletId, string $ticketId): KitchenTicket
    {
        $ticket = KitchenTicket::query()
            ->where('tenant_id', $context->tenantId)
            ->where('outlet_id', $outletId)
            ->whereKey($ticketId)
            ->first();

        if (! $ticket instanceof KitchenTicket) {
            throw KitchenException::ticketNotFound();
        }

        return $ticket;
    }
}
