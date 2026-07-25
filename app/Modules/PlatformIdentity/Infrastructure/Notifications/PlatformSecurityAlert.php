<?php

declare(strict_types=1);

namespace App\Modules\PlatformIdentity\Infrastructure\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PlatformSecurityAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        private readonly string $eventType,
        private readonly string $outcome,
        private readonly string $occurredAt,
        private readonly ?string $ipAddress,
        private readonly string $correlationId,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Platform security alert')
            ->line('A Platform Administrator security event occurred.')
            ->line('Event: '.$this->eventType)
            ->line('Outcome: '.$this->outcome)
            ->line('Time: '.$this->occurredAt)
            ->line('IP address: '.($this->ipAddress ?? 'not available'))
            ->line('Correlation ID: '.$this->correlationId)
            ->line('If this activity was not expected, follow the platform emergency recovery runbook.');
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }
}
