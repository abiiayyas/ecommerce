<?php

namespace App\Data\Payments;

use App\Enums\PaymentMethod;
use InvalidArgumentException;

final readonly class CreatePaymentData
{
    /** @param array<string, mixed>|null $metadata */
    public function __construct(
        public string $orderId,
        public int $amount,
        public PaymentMethod $paymentMethod,
        public ?int $expiryMinutes = null,
        public ?string $redirectUrl = null,
        public ?bool $feeByMerchant = null,
        public ?array $metadata = null,
        public ?bool $paymentReminderEnabled = null,
        public ?string $providerCode = null,
        public ?int $totalAmount = null,
    ) {
        if (blank($this->orderId)) {
            throw new InvalidArgumentException('Payment order ID is required.');
        }

        if ($this->amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if ($this->totalAmount !== null && $this->totalAmount < $this->amount) {
            throw new InvalidArgumentException('Payment total amount must not be less than the principal amount.');
        }

        if ($this->expiryMinutes !== null && $this->expiryMinutes <= 0) {
            throw new InvalidArgumentException('Payment expiry must be greater than zero minutes.');
        }

        if ($this->redirectUrl !== null && filter_var($this->redirectUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Payment redirect URL must be valid.');
        }

        if ($this->providerCode !== null && preg_match('/^[A-Za-z0-9_-]+$/D', $this->providerCode) !== 1) {
            throw new InvalidArgumentException('Payment provider code is invalid.');
        }
    }
}
