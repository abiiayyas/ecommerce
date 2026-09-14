<?php

use App\Actions\Ecommerce\Payment\CreatePaymentAction;
use App\Actions\Ecommerce\Payment\ReconcilePaymentStatusAction;
use App\Contracts\Payments\PaymentGateway;
use App\Data\Payments\CreatePaymentData;
use App\Data\Payments\PaymentMethodData;
use App\Data\Payments\PaymentTransactionData;
use App\Enums\PaymentGatewayDriver;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentTransactionNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(DatabaseMigrations::class);

it('creates one Midtrans payment from the canonical advertised selection', function (
    string $method,
    string $providerCode,
    string $paymentType,
    int $flatFee,
    int $percentageFeeBasisPoints,
    int $expectedFee,
) {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'total' => 100_000,
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('paymentMethods')
        ->once()
        ->andReturn([new PaymentMethodData(
            paymentMethod: PaymentMethod::from($method),
            providerCode: $providerCode,
            name: 'Advertised method',
            type: $paymentType,
            flatFee: $flatFee,
            percentageFeeBasisPoints: $percentageFeeBasisPoints,
        )]);
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->andThrow(new PaymentTransactionNotFoundException);
    $gateway->shouldReceive('createPayment')
        ->once()
        ->withArgs(function (CreatePaymentData $data) use ($method, $providerCode, $expectedFee): bool {
            expect(DB::transactionLevel())->toBe(0)
                ->and($data->orderId)->not->toBeEmpty()
                ->and($data->amount)->toBe(100_000)
                ->and($data->totalAmount)->toBe(100_000 + $expectedFee)
                ->and($data->paymentMethod)->toBe(PaymentMethod::from($method))
                ->and($data->providerCode)->toBe($providerCode);

            return true;
        })
        ->andReturnUsing(fn (CreatePaymentData $data): PaymentTransactionData => new PaymentTransactionData(
            id: 'midtrans-transaction-id',
            orderId: $data->orderId,
            amount: $data->totalAmount,
            totalPayment: $data->totalAmount,
            paymentMethod: $data->paymentMethod,
            paymentNumber: $data->paymentMethod === PaymentMethod::Qris ? null : '880812345678',
            paymentUrl: $data->paymentMethod === PaymentMethod::Qris ? 'https://example.test/qris.png' : null,
            status: PaymentStatus::Pending,
            providerCode: $data->providerCode,
        ));

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldReceive('defaultDriver')
        ->once()
        ->andReturn(PaymentGatewayDriver::Midtrans);
    $gatewayManager->shouldReceive('driver')
        ->once()
        ->with(PaymentGatewayDriver::Midtrans)
        ->andReturn($gateway);

    $action = new CreatePaymentAction($gatewayManager);

    $firstPayment = $action->handle($order, $method, $user, providerCode: $providerCode);
    $secondPayment = $action->handle($order->refresh(), $method, $user, providerCode: $providerCode);

    expect($secondPayment->is($firstPayment))->toBeTrue()
        ->and(Payment::query()->count())->toBe(1)
        ->and((float) $firstPayment->amount)->toBe(100_000.0)
        ->and((float) $firstPayment->fee)->toBe((float) $expectedFee)
        ->and((float) $firstPayment->total)->toBe((float) (100_000 + $expectedFee))
        ->and((float) $order->refresh()->payment_fee)->toBe((float) $expectedFee)
        ->and($firstPayment->driver)->toBe('midtrans')
        ->and($firstPayment->payment_type)->toBe($paymentType)
        ->and($firstPayment->channel)->toBe($providerCode)
        ->and($firstPayment->account_number)->not->toBeEmpty();
})->with([
    'QRIS' => ['qris', 'qris', 'qris', 0, 70, 700],
    'BCA virtual account' => ['bca', 'bca', 'virtual_account', 4_500, 0, 4_500],
]);

it('rejects mismatched and unsupported payment method provider pairs', function (string $method, string $providerCode) {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000, 'payment_fee' => 0]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('paymentMethods')
        ->once()
        ->andReturn([
            new PaymentMethodData(PaymentMethod::Qris, 'qris', 'QRIS', 'qris', percentageFeeBasisPoints: 70),
            new PaymentMethodData(PaymentMethod::Bca, 'bca', 'BCA Virtual Account', 'virtual_account', flatFee: 4_500),
        ]);
    $gateway->shouldNotReceive('paymentStatus');
    $gateway->shouldNotReceive('createPayment');

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldReceive('defaultDriver')->once()->andReturn(PaymentGatewayDriver::Midtrans);
    $gatewayManager->shouldReceive('driver')->once()->with(PaymentGatewayDriver::Midtrans)->andReturn($gateway);

    expect(fn () => (new CreatePaymentAction($gatewayManager))->handle($order, $method, $user, providerCode: $providerCode))
        ->toThrow(InvalidArgumentException::class, 'Unsupported payment method/provider combination.');

    expect(Payment::query()->count())->toBe(0)
        ->and((float) $order->refresh()->payment_fee)->toBe(0.0);
})->with([
    'mismatched advertised pair' => ['qris', 'bca'],
    'unadvertised provider code' => ['qris', 'not-advertised'],
]);

it('submits and persists the fee-bearing Midtrans total', function (string $method, int $expectedFee) {
    config()->set([
        'payment.default' => PaymentGatewayDriver::Midtrans->value,
        'midtrans.server_key' => 'midtrans-server-key',
        'midtrans.client_key' => 'midtrans-client-key',
        'midtrans.is_production' => false,
        'midtrans.retries' => 0,
    ]);

    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000, 'payment_fee' => 0]);
    $expectedTotal = 100_000 + $expectedFee;

    Http::preventStrayRequests();
    Http::fake(function (Request $request) use ($expectedTotal, $method) {
        if ($request->method() === 'GET') {
            return Http::response(['status_message' => 'Transaction does not exist'], 404);
        }

        $orderId = data_get($request->data(), 'transaction_details.order_id');
        $response = [
            'transaction_id' => 'midtrans-fee-bearing-id',
            'order_id' => $orderId,
            'gross_amount' => $expectedTotal.'.00',
            'payment_type' => $method === 'qris' ? 'qris' : 'bank_transfer',
            'transaction_status' => 'pending',
        ];

        if ($method === 'qris') {
            $response['actions'] = [[
                'name' => 'generate-qr-code',
                'url' => 'https://api.sandbox.midtrans.com/qris.png',
            ]];
        } else {
            $response['va_numbers'] = [['bank' => $method, 'va_number' => '880812345678']];
        }

        return Http::response($response, 201);
    });

    $payment = app(CreatePaymentAction::class)->handle($order, $method, $user);

    expect((float) $payment->amount)->toBe(100_000.0)
        ->and((float) $payment->fee)->toBe((float) $expectedFee)
        ->and((float) $payment->total)->toBe((float) $expectedTotal)
        ->and((float) $order->refresh()->payment_fee)->toBe((float) $expectedFee);

    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.sandbox.midtrans.com/v2/charge'
        && data_get($request->data(), 'transaction_details.gross_amount') === $expectedTotal);
    Http::assertSentCount(2);
})->with([
    'QRIS' => ['qris', 700],
    'BCA virtual account' => ['bca', 4_500],
]);

it('rejects provider integrity mismatches without mutating payment or order', function (string $mismatch) {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000, 'payment_fee' => 0]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('paymentMethods')
        ->once()
        ->andReturn([
            new PaymentMethodData(PaymentMethod::Va, 'CIMBVA', 'CIMB Virtual Account', 'virtual_account', flatFee: 3_400),
        ]);
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->andThrow(new PaymentTransactionNotFoundException);
    $gateway->shouldReceive('createPayment')
        ->once()
        ->andReturnUsing(fn (CreatePaymentData $data): PaymentTransactionData => new PaymentTransactionData(
            id: 'provider-transaction-id',
            orderId: $data->orderId,
            amount: $mismatch === 'amount' ? 99_999 : 100_000,
            totalPayment: $mismatch === 'total' ? 99_999 : 103_400,
            paymentMethod: PaymentMethod::Va,
            paymentNumber: '880812345678',
            paymentUrl: null,
            status: PaymentStatus::Pending,
            providerCode: $mismatch === 'method' ? 'BSIVA' : 'CIMBVA',
        ));

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldReceive('defaultDriver')->once()->andReturn(PaymentGatewayDriver::Paywuz);
    $gatewayManager->shouldReceive('driver')->once()->with(PaymentGatewayDriver::Paywuz)->andReturn($gateway);

    expect(fn () => (new CreatePaymentAction($gatewayManager))->handle($order, 'va', $user, providerCode: 'CIMBVA'))
        ->toThrow(UnexpectedValueException::class);

    $payment = Payment::query()->sole();

    expect($payment->transaction_id)->toBeNull()
        ->and((float) $payment->amount)->toBe(100_000.0)
        ->and((float) $payment->fee)->toBe(3_400.0)
        ->and((float) $payment->total)->toBe(103_400.0)
        ->and((float) $order->refresh()->payment_fee)->toBe(3_400.0)
        ->and($order->status)->toBeFalse();
})->with(['amount', 'total', 'method']);

it('rejects mismatched gateway transaction identity', function (string $mismatch) {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('paymentMethods')
        ->once()
        ->andReturn([
            new PaymentMethodData(PaymentMethod::Qris, 'qris', 'QRIS', 'qris', percentageFeeBasisPoints: 70),
        ]);
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->andThrow(new PaymentTransactionNotFoundException);
    $gateway->shouldReceive('createPayment')
        ->once()
        ->andReturnUsing(fn (CreatePaymentData $data): PaymentTransactionData => new PaymentTransactionData(
            id: 'provider-transaction-id',
            orderId: $mismatch === 'order' ? 'wrong-order' : $data->orderId,
            amount: $data->totalAmount ?? $data->amount,
            totalPayment: $data->totalAmount ?? $data->amount,
            paymentMethod: $mismatch === 'method' ? PaymentMethod::Bca : $data->paymentMethod,
            paymentNumber: null,
            paymentUrl: 'https://example.test/qris.png',
            status: PaymentStatus::Pending,
            providerCode: 'QRIS',
        ));

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldReceive('defaultDriver')
        ->once()
        ->andReturn(PaymentGatewayDriver::Midtrans);
    $gatewayManager->shouldReceive('driver')
        ->once()
        ->with(PaymentGatewayDriver::Midtrans)
        ->andReturn($gateway);

    expect(fn () => (new CreatePaymentAction($gatewayManager))->handle($order, 'qris', $user))
        ->toThrow(UnexpectedValueException::class, 'Payment gateway returned a mismatched payment response.');

    expect(Payment::query()->sole()->transaction_id)->toBeNull();
})->with(['order', 'method']);

it('uses the persisted driver when resuming an unfinished payment after configuration changes', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);
    $payment = Payment::factory()->for($order, 'payable')->create([
        'driver' => 'paywuz',
        'transaction_id' => null,
        'payment_type' => 'qris',
        'channel' => 'qris',
        'amount' => 100_000,
        'fee' => 700,
        'total' => 100_700,
    ]);

    config()->set('payment.default', 'midtrans');

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldNotReceive('paymentMethods');
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->with($payment->order_id)
        ->andThrow(new PaymentTransactionNotFoundException);
    $gateway->shouldReceive('createPayment')
        ->once()
        ->withArgs(function (CreatePaymentData $data): bool {
            expect($data->amount)->toBe(100_000);

            return true;
        })
        ->andReturn(new PaymentTransactionData(
            id: 'paywuz-transaction-id',
            orderId: $payment->order_id,
            amount: 100_000,
            totalPayment: 100_700,
            paymentMethod: PaymentMethod::Qris,
            paymentNumber: null,
            paymentUrl: 'https://paywuz.id/pay/paywuz-transaction-id',
            status: PaymentStatus::Pending,
            providerCode: 'QRIS',
        ));

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldNotReceive('defaultDriver');
    $gatewayManager->shouldReceive('driver')
        ->once()
        ->with(PaymentGatewayDriver::Paywuz)
        ->andReturn($gateway);

    $result = (new CreatePaymentAction($gatewayManager))->handle($order, 'qris', $user);

    expect($result->driver)->toBe('paywuz')
        ->and($result->transaction_id)->toBe('paywuz-transaction-id')
        ->and($result->account_number)->toBe('https://paywuz.id/pay/paywuz-transaction-id');
});

it('persists the Paywuz Meta VA payment URL when no payment number exists yet', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldReceive('paymentMethods')
        ->once()
        ->andReturn([
            new PaymentMethodData(PaymentMethod::Va, 'VA', 'Virtual Account', 'meta', flatFee: 3_400),
        ]);
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->andThrow(new PaymentTransactionNotFoundException);
    $gateway->shouldReceive('createPayment')
        ->once()
        ->withArgs(function (CreatePaymentData $data): bool {
            expect($data->amount)->toBe(100_000)
                ->and($data->paymentMethod)->toBe(PaymentMethod::Va)
                ->and($data->providerCode)->toBe('VA');

            return true;
        })
        ->andReturnUsing(fn (CreatePaymentData $data): PaymentTransactionData => new PaymentTransactionData(
            id: 'paywuz-meta-va-id',
            orderId: $data->orderId,
            amount: 100_000,
            totalPayment: 103_400,
            paymentMethod: PaymentMethod::Va,
            paymentNumber: null,
            paymentUrl: 'https://paywuz.id/pay/paywuz-meta-va-id',
            status: PaymentStatus::Pending,
            providerCode: 'VA',
        ));

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldReceive('defaultDriver')
        ->once()
        ->andReturn(PaymentGatewayDriver::Paywuz);
    $gatewayManager->shouldReceive('driver')
        ->once()
        ->with(PaymentGatewayDriver::Paywuz)
        ->andReturn($gateway);

    $payment = (new CreatePaymentAction($gatewayManager))->handle($order, 'va', $user, providerCode: 'VA');

    expect($payment->driver)->toBe(PaymentGatewayDriver::Paywuz->value)
        ->and($payment->channel)->toBe('VA')
        ->and($payment->payment_type)->toBe('meta')
        ->and($payment->transaction_id)->toBe('paywuz-meta-va-id')
        ->and($payment->account_number)->toBe('https://paywuz.id/pay/paywuz-meta-va-id')
        ->and((float) $payment->amount)->toBe(100_000.0)
        ->and((float) $payment->fee)->toBe(3_400.0)
        ->and((float) $payment->total)->toBe(103_400.0);
});

it('recovers an authoritative transaction by immutable order ID before creating another remote payment', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);
    $payment = Payment::factory()->for($order, 'payable')->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'order_id' => 'recover-existing-order',
        'transaction_id' => null,
        'payment_type' => 'bank_transfer',
        'channel' => 'CIMBVA',
        'account_number' => '',
        'amount' => 100_000,
        'fee' => 0,
        'total' => 100_000,
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldNotReceive('paymentMethods');
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->with('recover-existing-order')
        ->andReturn(new PaymentTransactionData(
            id: 'recovered-transaction-id',
            orderId: 'recover-existing-order',
            amount: 100_000,
            totalPayment: 103_400,
            paymentMethod: PaymentMethod::Va,
            paymentNumber: '880812345678',
            paymentUrl: null,
            status: PaymentStatus::Pending,
            providerCode: 'CIMBVA',
        ));
    $gateway->shouldNotReceive('createPayment');

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldNotReceive('defaultDriver');
    $gatewayManager->shouldReceive('driver')->once()->with(PaymentGatewayDriver::Paywuz)->andReturn($gateway);

    $result = (new CreatePaymentAction($gatewayManager))->handle($order, 'va', $user, providerCode: 'BSIVA');

    expect($result->is($payment))->toBeTrue()
        ->and(Payment::query()->count())->toBe(1)
        ->and($result->transaction_id)->toBe('recovered-transaction-id')
        ->and($result->channel)->toBe('CIMBVA')
        ->and($result->account_number)->toBe('880812345678');
});

it('reconciles transaction-less authoritative statuses without requiring a payment destination', function (
    PaymentStatus $status,
    bool $isPaid,
) {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create([
        'total' => 100_000,
        'payment_fee' => 3_400,
    ]);
    $payment = Payment::factory()->for($order, 'payable')->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'order_id' => 'status-first-recovery',
        'transaction_id' => null,
        'payment_type' => 'virtual_account',
        'channel' => 'CIMBVA',
        'account_number' => '',
        'account_code' => null,
        'expired_at' => now()->addDay(),
        'paid_at' => null,
        'amount' => 100_000,
        'fee' => 3_400,
        'total' => 103_400,
    ]);
    Mail::fake();

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldNotReceive('paymentMethods');
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->with('status-first-recovery')
        ->andReturn(new PaymentTransactionData(
            id: 'status-first-transaction',
            orderId: 'status-first-recovery',
            amount: 100_000,
            totalPayment: 103_400,
            paymentMethod: PaymentMethod::Va,
            paymentNumber: null,
            paymentUrl: null,
            status: $status,
            providerCode: 'CIMBVA',
        ));
    $gateway->shouldNotReceive('createPayment');

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldNotReceive('defaultDriver');
    $gatewayManager->shouldReceive('driver')->once()->with(PaymentGatewayDriver::Paywuz)->andReturn($gateway);

    $result = (new CreatePaymentAction($gatewayManager))->handle($order, 'va', $user, providerCode: 'CIMBVA');

    expect($result->is($payment))->toBeTrue()
        ->and($result->transaction_id)->toBe('status-first-transaction')
        ->and($result->account_number)->toBe('')
        ->and($result->paid_at !== null)->toBe($isPaid)
        ->and($order->refresh()->status)->toBe($isPaid);

    if ($isPaid) {
        expect($result->account_code)->toBeNull();
    } else {
        expect($result->account_code)->toBe('provider-status:'.$status->value)
            ->and($result->expired_at?->lessThanOrEqualTo(now()))->toBeTrue();
    }
})->with([
    'paid' => [PaymentStatus::Paid, true],
    'failed' => [PaymentStatus::Failed, false],
    'cancelled' => [PaymentStatus::Cancelled, false],
    'expired' => [PaymentStatus::Expired, false],
]);

it('requires a payment destination for a transaction-less pending status', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);
    $payment = Payment::factory()->for($order, 'payable')->create([
        'driver' => PaymentGatewayDriver::Paywuz->value,
        'order_id' => 'pending-without-destination',
        'transaction_id' => null,
        'payment_type' => 'virtual_account',
        'channel' => 'CIMBVA',
        'account_number' => '',
        'amount' => 100_000,
        'fee' => 3_400,
        'total' => 103_400,
    ]);

    $gateway = Mockery::mock(PaymentGateway::class);
    $gateway->shouldNotReceive('paymentMethods');
    $gateway->shouldReceive('paymentStatus')
        ->once()
        ->andReturn(new PaymentTransactionData(
            id: 'pending-without-destination-transaction',
            orderId: 'pending-without-destination',
            amount: 100_000,
            totalPayment: 103_400,
            paymentMethod: PaymentMethod::Va,
            paymentNumber: null,
            paymentUrl: null,
            status: PaymentStatus::Pending,
            providerCode: 'CIMBVA',
        ));
    $gateway->shouldNotReceive('createPayment');

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldNotReceive('defaultDriver');
    $gatewayManager->shouldReceive('driver')->once()->with(PaymentGatewayDriver::Paywuz)->andReturn($gateway);

    expect(fn () => (new CreatePaymentAction($gatewayManager))->handle($order, 'va', $user, providerCode: 'CIMBVA'))
        ->toThrow(UnexpectedValueException::class, 'Payment gateway returned no usable payment destination.');

    expect($payment->refresh()->transaction_id)->toBeNull();
});

it('locks payment before order when resuming creation and configures transaction retries', function () {
    $user = User::factory()->create();
    $order = Order::factory()->for($user)->create(['total' => 100_000]);
    $payment = Payment::factory()->for($order, 'payable')->create([
        'transaction_id' => 'already-created-transaction',
        'amount' => 100_000,
        'fee' => 4_500,
        'total' => 104_500,
    ]);
    $lockOrder = [];

    DB::listen(function (QueryExecuted $query) use (&$lockOrder): void {
        if (preg_match('/\bfrom\s+["`]?payments["`]?\b/i', $query->sql) === 1) {
            $lockOrder[] = 'payment';
        } elseif (preg_match('/\bfrom\s+["`]?orders["`]?\b/i', $query->sql) === 1) {
            $lockOrder[] = 'order';
        }
    });

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldNotReceive('defaultDriver');
    $gatewayManager->shouldNotReceive('driver');

    $result = (new CreatePaymentAction($gatewayManager))->handle($order, 'qris', $user);
    $creationLockOrder = $lockOrder;
    $lockOrder = [];

    (new ReconcilePaymentStatusAction)->handle($payment, new PaymentTransactionData(
        id: 'already-created-transaction',
        orderId: (string) $payment->order_id,
        amount: 104_500,
        totalPayment: 104_500,
        paymentMethod: PaymentMethod::Bca,
        paymentNumber: (string) $payment->account_number,
        paymentUrl: null,
        status: PaymentStatus::Pending,
        providerCode: 'bca',
    ));

    expect($result->is($payment))->toBeTrue()
        ->and(array_slice($creationLockOrder, 0, 2))->toBe(['payment', 'order'])
        ->and(array_slice($lockOrder, 0, 2))->toBe(['payment', 'order'])
        ->and(file_get_contents(app_path('Actions/Ecommerce/Payment/CreatePaymentAction.php')))->toContain('attempts: 3')
        ->and(file_get_contents(app_path('Actions/Ecommerce/Payment/ReconcilePaymentStatusAction.php')))->toContain('attempts: 3');
});

it('rejects payment creation by non owners and invalid guest tokens', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $registeredOrder = Order::factory()->for($owner)->create();
    $guestOrder = Order::factory()->guest()->create([
        'access_token' => 'valid-guest-token',
    ]);

    $gatewayManager = Mockery::mock(PaymentGatewayManager::class);
    $gatewayManager->shouldNotReceive('defaultDriver');
    $gatewayManager->shouldNotReceive('driver');
    $action = new CreatePaymentAction($gatewayManager);

    expect(fn () => $action->handle($registeredOrder, 'qris', $otherUser))
        ->toThrow(AuthorizationException::class);

    expect(fn () => $action->handle($guestOrder, 'qris', guestToken: 'invalid-guest-token'))
        ->toThrow(AuthorizationException::class);
});
