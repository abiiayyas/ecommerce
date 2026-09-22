<?php

namespace App\Actions\Ecommerce\Checkout;

use App\Actions\Inventory\ReserveOrderStockAction;
use App\Contracts\Marketing\MarketingEventPublisher;
use App\Data\Marketing\MarketingEventData;
use App\Enums\FulfillmentType;
use App\Enums\OrderFulfillmentStatus;
use App\Enums\SalesChannel;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageDailyStat;
use App\Models\Order\Order;
use App\Models\Order\OrderShop;
use App\Models\Order\OrderShopItem;
use App\Models\Product\ProductFlat;
use App\Models\Shipping\ShippingRateQuote;
use App\Models\Supplier\SupplierDispatch;
use App\Services\Notifications\NotificationDispatcher;
use App\Traits\WithGenerateReference;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateLandingOrderAction
{
    use WithGenerateReference;

    private const int APPLICATION_FEE = 1000;

    private const int INSURANCE_FEE = 2500;

    public function __construct(
        private readonly ReserveOrderStockAction $reserveOrderStock,
        private readonly MarketingEventPublisher $marketingEvents,
        private readonly NotificationDispatcher $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(LandingPage $landingPage, array $data): Order
    {
        $order = DB::transaction(function () use ($landingPage, $data): Order {
            $landingPage = LandingPage::query()
                ->published()
                ->lockForUpdate()
                ->findOrFail($landingPage->getKey());

            $productFlat = ProductFlat::query()
                ->with(['activeSupplierOffer.supplier', 'activeSupplierOffer.warehouse'])
                ->whereKey($data['product_flat_id'])
                ->where('product_id', $landingPage->product_id)
                ->where('status', true)
                ->firstOrFail();

            $quantity = (int) $data['quantity'];
            $quote = ShippingRateQuote::query()
                ->where('public_id', $data['shipping_rate_quote_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $expectedHash = self::quotePayloadHash(
                $landingPage->getKey(),
                $productFlat->getKey(),
                $quantity,
                (string) $data['destination_area_id'],
            );

            if (! $quote->isUsable()
                || $quote->landing_page_id !== $landingPage->getKey()
                || $quote->product_flat_id !== $productFlat->getKey()
                || $quote->quantity !== $quantity
                || ! hash_equals($quote->payload_hash, $expectedHash)) {
                throw ValidationException::withMessages([
                    'shipping_rate_quote_id' => 'Tarif pengiriman sudah tidak berlaku. Silakan hitung ulang.',
                ]);
            }

            $paymentMode = (string) $data['payment_mode'];
            if (($paymentMode === 'cod' && ! $landingPage->cod_enabled)
                || ($paymentMode === 'online' && ! $landingPage->online_payment_enabled)) {
                throw ValidationException::withMessages([
                    'payment_mode' => 'Metode pembayaran tidak tersedia untuk halaman ini.',
                ]);
            }

            $itemTotal = (float) $productFlat->price * $quantity;
            $shippingTotal = (float) $quote->price;
            $reference = $this->generateReference(
                model: Order::whereDate('created_at', now()),
                prefix: 'TRX-'.now()->format('Ymd').'-',
            );

            $order = Order::query()->create([
                'user_id' => null,
                'location_id' => null,
                'landing_page_id' => $landingPage->getKey(),
                'sales_channel' => SalesChannel::AdLanding,
                'payment_mode' => $paymentMode,
                'reference' => $reference['code'],
                'access_token' => Str::random(64),
                'ref_number' => $reference['number'],
                'guest_data' => [
                    'contact_name' => $data['contact_name'],
                    'contact_email' => $data['contact_email'],
                    'contact_phone' => $data['contact_phone'],
                    'address' => $data['address'],
                    'note' => $data['note'] ?? null,
                    'postal_code' => $data['postal_code'],
                    'area_string' => $data['area_string'],
                    'biteship_area_id' => $data['destination_area_id'],
                    'shipping_area_id' => $data['destination_area_id'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'attribution' => array_filter([
                        'utm_source' => $data['utm_source'] ?? null,
                        'utm_medium' => $data['utm_medium'] ?? null,
                        'utm_campaign' => $data['utm_campaign'] ?? null,
                        'utm_content' => $data['utm_content'] ?? null,
                        'click_id' => $data['click_id'] ?? null,
                    ]),
                ],
                'total_checkout' => $itemTotal,
                'total_shipping' => $shippingTotal,
                'application_fee' => self::APPLICATION_FEE,
                'insurance_fee' => self::INSURANCE_FEE,
                'payment_fee' => 0,
                'tax_total' => 0,
                'total' => $itemTotal + $shippingTotal + self::APPLICATION_FEE + self::INSURANCE_FEE,
                'status' => false,
                'fulfillment_status' => $paymentMode === 'cod'
                    ? OrderFulfillmentStatus::AwaitingSupplier
                    : OrderFulfillmentStatus::AwaitingPayment,
            ]);

            $orderShop = OrderShop::query()->create([
                'order_id' => $order->getKey(),
                'shop_id' => $productFlat->shop_id,
                'shipping_data' => [
                    'provider' => $quote->provider,
                    'company' => $quote->courier_company,
                    'type' => $quote->courier_service,
                    'description' => $quote->description,
                    'price' => (float) $quote->price,
                    'quote_id' => $quote->public_id,
                ],
                'total_checkout' => $itemTotal,
                'total_shipping' => $shippingTotal,
                'tax' => 0,
                'total' => $itemTotal + $shippingTotal,
                'shipping_status' => false,
            ]);

            $fulfillmentData = $this->fulfillmentSnapshot($productFlat);
            OrderShopItem::query()->create([
                'order_id' => $order->getKey(),
                'order_shop_id' => $orderShop->getKey(),
                'product_flat_id' => $productFlat->getKey(),
                'product_data' => $productFlat->toArray(),
                'fulfillment_data' => $fulfillmentData,
                'quantity' => $quantity,
                'price' => $productFlat->price,
                'total' => $itemTotal,
            ]);

            $this->reserveOrderStock->handle(
                $order,
                $productFlat,
                $quantity,
                commitImmediately: $paymentMode === 'cod',
            );

            if ($productFlat->fulfillment_type === FulfillmentType::SupplierDropship) {
                $offer = $productFlat->activeSupplierOffer;
                SupplierDispatch::query()->create([
                    'order_shop_id' => $orderShop->getKey(),
                    'supplier_id' => $offer->supplier_id,
                    'supplier_warehouse_id' => $offer->supplier_warehouse_id,
                    'order_snapshot' => [
                        'order_reference' => $order->reference,
                        'customer' => $order->guest_data,
                        'item' => $fulfillmentData,
                        'quantity' => $quantity,
                    ],
                ]);
            }

            $quote->update(['consumed_at' => now()]);

            return $order;
        }, attempts: 3);

        $stats = LandingPageDailyStat::query()->firstOrCreate([
            'landing_page_id' => $landingPage->getKey(),
            'campaign_id' => 0,
            'date' => today(),
        ]);
        $stats->increment('orders');
        $stats->increment('checkouts');

        $eventId = filled($data['marketing_event_id'] ?? null)
            ? (string) $data['marketing_event_id']
            : $this->marketingEvents->newEventId();
        $this->marketingEvents->publish(new MarketingEventData(
            eventId: $eventId,
            name: 'InitiateCheckout',
            occurredAt: now(),
            sourceUrl: route('landing.show', ['slug' => $landingPage->slug]),
            customerData: [
                'email' => $data['contact_email'],
                'phone' => $data['contact_phone'],
                'external_id' => (string) $order->getKey(),
            ],
            customData: [
                'content_ids' => [(string) $data['product_flat_id']],
                'content_type' => 'product',
                'currency' => 'IDR',
                'value' => (float) $order->total,
                'order_id' => $order->reference,
            ],
            attribution: array_filter([
                'utm_source' => $data['utm_source'] ?? null,
                'utm_medium' => $data['utm_medium'] ?? null,
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'utm_content' => $data['utm_content'] ?? null,
                'click_id' => $data['click_id'] ?? null,
            ]),
        ));

        $destinationRoute = $order->payment_mode === 'online' ? 'payment.show' : 'orders.detail';
        $destinationUrl = route($destinationRoute, [
            'reference' => $order->reference,
            ...$order->guestRouteParameters(),
        ]);
        $this->notifications->sendWhatsApp(
            messageType: 'order_confirmation',
            recipient: (string) $data['contact_phone'],
            message: "Pesanan {$order->reference} sudah kami terima. Lihat detail: {$destinationUrl}",
            idempotencyKey: "order:{$order->getKey()}:confirmation",
            context: ['order_id' => $order->getKey()],
        );

        return $order;
    }

    public static function quotePayloadHash(int $landingPageId, int $productFlatId, int $quantity, string $destinationAreaId): string
    {
        return hash('sha256', implode('|', [
            $landingPageId,
            $productFlatId,
            $quantity,
            trim($destinationAreaId),
        ]));
    }

    /** @return array<string, mixed> */
    private function fulfillmentSnapshot(ProductFlat $productFlat): array
    {
        if ($productFlat->fulfillment_type === FulfillmentType::OwnedStock) {
            return ['type' => FulfillmentType::OwnedStock->value];
        }

        $offer = $productFlat->activeSupplierOffer;

        return [
            'type' => FulfillmentType::SupplierDropship->value,
            'supplier_id' => $offer->supplier_id,
            'supplier_name' => $offer->supplier->name,
            'supplier_warehouse_id' => $offer->supplier_warehouse_id,
            'supplier_warehouse_name' => $offer->warehouse->name,
            'supplier_sku' => $offer->supplier_sku,
            'cost_price' => (float) $offer->cost_price,
        ];
    }
}
