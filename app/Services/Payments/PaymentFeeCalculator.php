<?php

namespace App\Services\Payments;

use App\Data\Payments\PaymentMethodData;
use InvalidArgumentException;

final class PaymentFeeCalculator
{
    private const int BASIS_POINTS_DIVISOR = 10_000;

    public function calculateCustomerFee(PaymentMethodData $method, int $principal): int
    {
        if ($principal < 0) {
            throw new InvalidArgumentException('Payment principal must not be negative.');
        }

        if ($method->feeByMerchant) {
            return 0;
        }

        $flatFee = $method->flatFee;
        $percentageFeeBasisPoints = $method->percentageFeeBasisPoints;
        $matchingTier = $this->matchingTier($method, $principal);

        if ($matchingTier !== null) {
            $flatFee = $matchingTier['flatFee'];
            $percentageFeeBasisPoints = $matchingTier['percentageFeeBasisPoints'];
        }

        $percentageFeeBasisPoints = max(0, $percentageFeeBasisPoints);
        $percentageFee = intdiv(
            ($principal * $percentageFeeBasisPoints) + self::BASIS_POINTS_DIVISOR - 1,
            self::BASIS_POINTS_DIVISOR,
        );

        return max(0, $flatFee) + $percentageFee;
    }

    /**
     * @return array{minimumAmount: ?int, maximumAmount: ?int, flatFee: int, percentageFeeBasisPoints: int}|null
     */
    private function matchingTier(PaymentMethodData $method, int $principal): ?array
    {
        $matchingTier = null;
        $highestMinimum = -1;

        foreach ($method->feeTiers as $tier) {
            $minimum = $tier['minimumAmount'];
            $maximum = $tier['maximumAmount'];

            if (($minimum !== null && $principal < $minimum) || ($maximum !== null && $principal > $maximum)) {
                continue;
            }

            $tierMinimum = $minimum ?? 0;

            if ($matchingTier === null || $tierMinimum > $highestMinimum) {
                $matchingTier = $tier;
                $highestMinimum = $tierMinimum;
            }
        }

        return $matchingTier;
    }
}
