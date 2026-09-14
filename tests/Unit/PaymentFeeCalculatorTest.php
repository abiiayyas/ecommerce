<?php

use App\Data\Payments\PaymentMethodData;
use App\Enums\PaymentMethod;
use App\Services\Payments\PaymentFeeCalculator;

it('rounds percentage fees up to the next rupiah', function (int $principal, int $expectedFee) {
    $paymentMethod = new PaymentMethodData(
        paymentMethod: PaymentMethod::Qris,
        providerCode: 'qris',
        name: 'QRIS',
        type: 'qris',
        percentageFeeBasisPoints: 70,
    );

    expect((new PaymentFeeCalculator)->calculateCustomerFee($paymentMethod, $principal))
        ->toBe($expectedFee);
})->with([
    'exact rupiah boundary' => [100_000, 700],
    'fractional rupiah boundary' => [100_001, 701],
]);

it('uses the matching fee tier and honors merchant-paid fees', function () {
    $calculator = new PaymentFeeCalculator;
    $tieredMethod = new PaymentMethodData(
        paymentMethod: PaymentMethod::Qris,
        providerCode: 'QRIS',
        name: 'QRIS',
        type: 'qris',
        flatFee: 290,
        percentageFeeBasisPoints: 70,
        feeTiers: [
            [
                'minimumAmount' => 150_000,
                'maximumAmount' => null,
                'flatFee' => 0,
                'percentageFeeBasisPoints' => 95,
            ],
        ],
    );
    $merchantPaidMethod = new PaymentMethodData(
        paymentMethod: PaymentMethod::Bca,
        providerCode: 'bca',
        name: 'BCA Virtual Account',
        type: 'virtual_account',
        flatFee: 4_500,
        feeByMerchant: true,
    );

    expect($calculator->calculateCustomerFee($tieredMethod, 150_001))->toBe(1_426)
        ->and($calculator->calculateCustomerFee($merchantPaidMethod, 150_001))->toBe(0);
});
