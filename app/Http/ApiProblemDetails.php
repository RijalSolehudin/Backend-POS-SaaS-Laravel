<?php

declare(strict_types=1);

namespace App\Http;

use App\Shared\Domain\Exceptions\BusinessException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ApiProblemDetails
{
    private const TITLES = [
        400 => 'Bad Request',
        401 => 'Unauthenticated',
        403 => 'Forbidden',
        404 => 'Not Found',
        422 => 'Validation Failed',
        429 => 'Too Many Requests',
        500 => 'Server Error',
        503 => 'Service Unavailable',
    ];

    public function render(Response $response, Throwable $exception, Request $request): Response
    {
        if (! $request->is('api/*')) {
            return $response;
        }

        $traceId = $this->traceId($request);
        $response->headers->set('X-Request-ID', $traceId);

        if ($response->getStatusCode() < 400) {
            return $response;
        }

        $status = $this->status($response, $exception);
        $body = [
            'type' => 'https://api.pos-saas.local/problems/'.$this->code($status, $exception),
            'title' => self::TITLES[$status] ?? 'Request Failed',
            'status' => $status,
            'detail' => $this->detail($status, $exception),
            'instance' => $request->path(),
            'code' => $this->code($status, $exception),
            'trace_id' => $traceId,
            'retryable' => in_array($status, [429, 503], true),
        ];

        if ($exception instanceof ValidationException) {
            $body['errors'] = $this->validationErrors($exception);
        }

        return response()
            ->json($body, $status, ['Content-Type' => 'application/problem+json'])
            ->withHeaders(['X-Request-ID' => $traceId]);
    }

    private function traceId(Request $request): string
    {
        $attribute = $request->attributes->get('api_request_id');

        if (is_string($attribute) && $attribute !== '') {
            return $attribute;
        }

        $requested = $request->header('X-Request-ID');

        if (is_string($requested) && $requested !== '' && mb_strlen($requested) <= 80) {
            return $requested;
        }

        return (string) Str::uuid();
    }

    private function status(Response $response, Throwable $exception): int
    {
        if ($exception instanceof ValidationException) {
            return 422;
        }

        if ($exception instanceof AuthenticationException) {
            return 401;
        }

        if ($exception instanceof BusinessException) {
            return $this->businessStatus($exception);
        }

        if ($exception instanceof AuthorizationException) {
            return $response->getStatusCode() === 500 ? 403 : $response->getStatusCode();
        }

        if ($exception instanceof NotFoundHttpException) {
            return 404;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return $response->getStatusCode();
    }

    private function code(int $status, Throwable $exception): string
    {
        if ($exception instanceof BusinessException) {
            return $exception->errorCode();
        }

        if ($exception instanceof ValidationException) {
            return 'VALIDATION_FAILED';
        }

        if ($exception instanceof AuthenticationException || $status === 401) {
            return 'UNAUTHENTICATED';
        }

        if ($status === 403) {
            return 'FORBIDDEN';
        }

        if ($status === 404) {
            return 'NOT_FOUND';
        }

        if ($status === 429) {
            return 'RATE_LIMITED';
        }

        return 'REQUEST_FAILED';
    }

    private function businessStatus(BusinessException $exception): int
    {
        return match ($exception->errorCode()) {
            'IDENTITY_INVALID_CREDENTIALS' => 401,
            'OUTLET_NOT_FOUND', 'POS_DEVICE_NOT_FOUND', 'DEVICE_NOT_REGISTERED', 'SHIFT_NOT_FOUND', 'ORDER_NOT_FOUND', 'ORDER_ITEM_NOT_FOUND', 'ORDER_PRODUCT_UNAVAILABLE', 'RECEIPT_NOT_FOUND', 'APPROVAL_NOT_FOUND' => 404,
            'SHIFT_ALREADY_OPEN', 'SHIFT_NOT_OPEN', 'ORDER_ACTIVE_SHIFT_REQUIRED', 'ORDER_NOT_DRAFT', 'ORDER_NOT_COMPLETED', 'ORDER_ITEMS_REQUIRED', 'IDEMPOTENCY_CONFLICT', 'APPROVAL_REQUIRED', 'APPROVAL_EXPIRED', 'APPROVAL_ALREADY_CONSUMED', 'APPROVAL_INVALID_STATE', 'APPROVAL_TARGET_MISMATCH', 'REFUND_ORDER_NOT_REFUNDABLE', 'REFUND_ALREADY_RECORDED', 'CASH_MOVEMENT_SHIFT_NOT_OPEN' => 409,
            'ORDER_REASON_REQUIRED', 'APPROVAL_REASON_REQUIRED', 'REFUND_REASON_REQUIRED', 'CASH_MOVEMENT_REASON_REQUIRED' => 422,
            'PAYMENT_AMOUNT_MISMATCH', 'PAYMENT_CURRENCY_MISMATCH' => 422,
            'REFUND_AMOUNT_MISMATCH', 'REFUND_CURRENCY_MISMATCH', 'SHIFT_CURRENCY_MISMATCH' => 422,
            'IDEMPOTENCY_KEY_REQUIRED' => 422,
            'VALIDATION_FAILED' => 422,
            default => 403,
        };
    }

    private function detail(int $status, Throwable $exception): string
    {
        if ($exception instanceof BusinessException || $exception instanceof ValidationException) {
            return $exception->getMessage();
        }

        return match ($status) {
            401 => 'Authentication is required for this request.',
            403 => 'The authenticated actor is not allowed to perform this request.',
            404 => 'The requested resource was not found.',
            422 => 'The request payload failed validation.',
            429 => 'Too many requests were sent in a short period.',
            503 => 'The service is temporarily unavailable.',
            default => 'The request could not be completed.',
        };
    }

    /**
     * @return list<array{field: string, code: string, message: string}>
     */
    private function validationErrors(ValidationException $exception): array
    {
        $errors = [];

        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $errors[] = [
                    'field' => (string) $field,
                    'code' => 'VALIDATION_FAILED',
                    'message' => $message,
                ];
            }
        }

        return $errors;
    }
}
