<?php

namespace App\Payments;

class PaymentResult
{
    public function __construct(
        public bool $successful,
        public string $status, // 'SUCCESS', 'FAILED', 'PENDING'
        public string $transactionReference,
        public string $message,
        public array $rawPayload = []
    ) {}

    public static function success(string $reference, string $message = 'Payment approved', array $payload = []): self
    {
        return new self(true, 'SUCCESS', $reference, $message, $payload);
    }

    public static function failure(string $reference, string $message = 'Payment declined or simulation failed', array $payload = []): self
    {
        return new self(false, 'FAILED', $reference, $message, $payload);
    }

    public static function pending(string $reference, string $message = 'Payment is processing', array $payload = []): self
    {
        return new self(false, 'PENDING', $reference, $message, $payload);
    }
}

