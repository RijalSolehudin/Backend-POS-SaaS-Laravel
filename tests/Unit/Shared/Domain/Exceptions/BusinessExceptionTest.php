<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Exceptions;

use App\Shared\Domain\Exceptions\BusinessException;
use PHPUnit\Framework\TestCase;

final class BusinessExceptionTest extends TestCase
{
    public function test_a_business_exception_exposes_a_stable_application_error_code(): void
    {
        $exception = new class('The operation is not allowed.') extends BusinessException
        {
            public function errorCode(): string
            {
                return 'OPERATION_NOT_ALLOWED';
            }
        };

        self::assertSame('OPERATION_NOT_ALLOWED', $exception->errorCode());
        self::assertSame('The operation is not allowed.', $exception->getMessage());
    }
}
