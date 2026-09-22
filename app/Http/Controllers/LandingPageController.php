<?php

namespace App\Http\Controllers;

use App\Actions\Ecommerce\Checkout\CreateLandingOrderAction;
use App\Enums\FulfillmentType;
use App\Models\Marketing\LandingPage;
use App\Models\Product\ProductFlat;
use App\Models\Shipping\ShippingRateQuote;
use App\Services\Shipping\ShippingManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Throwable;

class LandingPageController extends Controller
{
    public function areas(Request $request, LandingPage $landingPage, ShippingManager $shipping): JsonResponse
    {
        $validated = $request->validate([
            'query' => ['required', 'string', 'min:3', 'max:120'],
            'product_flat_id' => ['required', 'integer'],
        ]);
        $flat = $this->publishedFlat($landingPage, (int) $validated['product_flat_id']);
        [$primary, $fallback] = $this->providerRoute($flat);

        return response()->json([
            'areas' => $shipping->searchAreas($primary, $fallback, $validated['query']),
        ]);
    }

    public function rates(Request $request, LandingPage $landingPage, ShippingManager $shipping): JsonResponse
    {
        $validated = $request->validate([
            'product_flat_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'between:1,100'],
            'destination_area_id' => ['required', 'string', 'max:255'],
            'payment_mode' => ['required', 'in:online,cod'],
        ]);
        $flat = $this->publishedFlat($landingPage, (int) $validated['product_flat_id']);
        [$primary, $fallback] = $this->providerRoute($flat);

        $payload = [
            'destination_area_id' => $validated['destination_area_id'],
            'couriers' => ['jne', 'jnt', 'sicepat', 'tiki', 'ninja', 'lion'],
            'items' => [[
                'name' => $flat->name,
                'description' => strip_tags((string) $flat->description),
                'value' => (int) round((float) $flat->price),
                'weight' => max(1, (int) $flat->weight),
                'quantity' => (int) $validated['quantity'],
            ]],
            'cod_amount' => $validated['payment_mode'] === 'cod'
                ? (int) round((float) $flat->price) * (int) $validated['quantity']
                : null,
        ];

        $rates = $this->ratesWithProviderOrigins($shipping, $flat, $primary, $fallback, $payload);
        $hash = CreateLandingOrderAction::quotePayloadHash(
            $landingPage->getKey(),
            $flat->getKey(),
            (int) $validated['quantity'],
            $validated['destination_area_id'],
        );

        $quotes = collect($rates)
            ->filter(fn (array $rate): bool => $validated['payment_mode'] !== 'cod' || $rate['supports_cod'])
            ->map(function (array $rate) use ($landingPage, $flat, $validated, $hash): array {
                $quote = ShippingRateQuote::query()->create([
                    'landing_page_id' => $landingPage->getKey(),
                    'product_flat_id' => $flat->getKey(),
                    'provider' => $rate['provider'],
                    'courier_company' => $rate['courier_company'],
                    'courier_service' => $rate['courier_service'],
                    'description' => $rate['description'],
                    'price' => $rate['price'],
                    'destination_area_id' => $validated['destination_area_id'],
                    'quantity' => (int) $validated['quantity'],
                    'payload_hash' => $hash,
                    'provider_payload' => $rate['raw'],
                    'expires_at' => now()->addMinutes(15),
                ]);

                return [
                    'id' => $quote->public_id,
                    'courier' => $rate['courier_name'],
                    'service' => $rate['courier_service'],
                    'description' => $rate['description'],
                    'price' => $rate['price'],
                    'provider' => $rate['provider'],
                ];
            })
            ->values();

        return response()->json(['rates' => $quotes]);
    }

    private function publishedFlat(LandingPage $landingPage, int $productFlatId): ProductFlat
    {
        abort_unless($landingPage->is_active && $landingPage->published_at?->isPast(), 404);

        return ProductFlat::query()
            ->with(['shop.location', 'activeSupplierOffer.warehouse'])
            ->whereKey($productFlatId)
            ->where('product_id', $landingPage->product_id)
            ->where('status', true)
            ->firstOrFail();
    }

    /** @return array{string, string|null} */
    private function providerRoute(ProductFlat $flat): array
    {
        if ($flat->fulfillment_type === FulfillmentType::SupplierDropship) {
            $warehouse = $flat->activeSupplierOffer?->warehouse;
            abort_if($warehouse === null || ! $warehouse->is_active, 422, 'Gudang supplier tidak tersedia.');

            return [$warehouse->primary_shipping_provider, $warehouse->fallback_shipping_provider];
        }

        return ['biteship', null];
    }

    /** @param array<string, mixed> $payload @return list<array<string, mixed>> */
    private function ratesWithProviderOrigins(
        ShippingManager $shipping,
        ProductFlat $flat,
        string $primary,
        ?string $fallback,
        array $payload,
    ): array {
        try {
            $rates = $shipping->rates($primary, null, [
                ...$payload,
                'origin_area_id' => $this->originAreaId($flat, $primary),
            ]);

            if ($rates !== [] || blank($fallback) || $fallback === $primary) {
                return $rates;
            }
        } catch (Throwable $exception) {
            if (blank($fallback) || $fallback === $primary) {
                throw $exception;
            }
        }

        return $shipping->rates($fallback, null, [
            ...$payload,
            'origin_area_id' => $this->originAreaId($flat, $fallback),
        ]);
    }

    private function originAreaId(ProductFlat $flat, string $provider): string
    {
        if ($flat->fulfillment_type === FulfillmentType::SupplierDropship) {
            $areaId = Arr::get($flat->activeSupplierOffer?->warehouse?->provider_area_ids ?? [], $provider);
        } else {
            $areaId = $provider === 'biteship' ? $flat->shop?->location?->biteship_area_id : null;
        }

        abort_if(blank($areaId), 422, 'Area asal pengiriman belum dikonfigurasi.');

        return (string) $areaId;
    }
}
