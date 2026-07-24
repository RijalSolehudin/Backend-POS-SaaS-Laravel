<?php

declare(strict_types=1);

namespace App\Shared\Application\Context;

use InvalidArgumentException;

final readonly class ActorContext
{
    public function __construct(
        public string $actorType,
        public string $actorId,
        public string $correlationId,
    ) {
        $this->ensureNotBlank($this->actorType, 'Actor type');
        $this->ensureNotBlank($this->actorId, 'Actor ID');
        $this->ensureNotBlank($this->correlationId, 'Correlation ID');
    }

    private function ensureNotBlank(string $value, string $label): void
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException("{$label} must not be blank.");
        }
    }
}
