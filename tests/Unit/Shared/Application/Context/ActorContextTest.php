<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Application\Context;

use App\Shared\Application\Context\ActorContext;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ActorContextTest extends TestCase
{
    public function test_it_exposes_the_trusted_actor_identity_and_correlation_id(): void
    {
        $context = new ActorContext(
            actorType: 'tenant_user',
            actorId: '01j00000000000000000000000',
            correlationId: 'request-123',
        );

        self::assertSame('tenant_user', $context->actorType);
        self::assertSame('01j00000000000000000000000', $context->actorId);
        self::assertSame('request-123', $context->correlationId);
    }

    /**
     * @param  array{actorType: string, actorId: string, correlationId: string}  $input
     */
    #[DataProvider('blankValueProvider')]
    public function test_it_rejects_blank_context_values(array $input, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        new ActorContext(...$input);
    }

    /**
     * @return iterable<string, array{
     *     input: array{actorType: string, actorId: string, correlationId: string},
     *     message: string
     * }>
     */
    public static function blankValueProvider(): iterable
    {
        yield 'actor type' => [
            'input' => [
                'actorType' => ' ',
                'actorId' => 'actor-1',
                'correlationId' => 'request-1',
            ],
            'message' => 'Actor type must not be blank.',
        ];

        yield 'actor id' => [
            'input' => [
                'actorType' => 'tenant_user',
                'actorId' => '',
                'correlationId' => 'request-1',
            ],
            'message' => 'Actor ID must not be blank.',
        ];

        yield 'correlation id' => [
            'input' => [
                'actorType' => 'tenant_user',
                'actorId' => 'actor-1',
                'correlationId' => "\t",
            ],
            'message' => 'Correlation ID must not be blank.',
        ];
    }
}
