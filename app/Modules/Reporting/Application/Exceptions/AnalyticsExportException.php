<?php

declare(strict_types=1);

namespace App\Modules\Reporting\Application\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;

final class AnalyticsExportException extends BusinessException
{
    private function __construct(
        string $message,
        private readonly string $businessErrorCode,
    ) {
        parent::__construct($message);
    }

    public static function failed(): self
    {
        return new self('The analytics export could not be completed.', 'ANALYTICS_EXPORT_FAILED');
    }

    public function errorCode(): string
    {
        return $this->businessErrorCode;
    }

    public function httpStatus(): int
    {
        return 500;
    }
}
