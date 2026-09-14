<?php

use App\Actions\Ecommerce\Checkout\ResolveShopGroupsAction;
use App\Actions\Ecommerce\Checkout\StoreCheckoutAction;
use App\Actions\Ecommerce\Shipping\GetShippingRatesAction;
use App\Models\Product\ProductFlat;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Sqids\Sqids;

new class extends Component
{
    private const MAX_PURCHASABLE_QUANTITY = 100;

    // CONSTANTS
    #[Locked]
    public $insuranceFee = 2500;

    #[Locked]
    public $applicationFee = 1000;

    // Selected product flat ids to checkout
    #[Locked]
    public mixed $selectedIds = '';

    /**
     * Resolved per-shop groups populated by resolveShopGroups().
     */
    #[Locked]
    public $shopGroups = [];

    /**
     * Per-shop selected rate: [ shopId => [ courier_code, price, name, etd ] ]
     *
     * @var array<int, array<string, mixed>>
     */
    public $shopRates = [];

    /**
     * Total ongkir dari semua toko.
     */
    public $totalShippingCost = 0;

    /**
     * Selected location ID from the shipping list.
     */
    public $selectedLocationId = null;

    /**
     * Parsed array of selected IDs.
     */
    public function getSelectedIdsArrayProperty(): array
    {
        if (! is_string($this->selectedIds) || blank($this->selectedIds)) {
            return [];
        }

        $selectedIds = (new Sqids)->decode($this->selectedIds);

        return array_values(array_filter(
            array_unique(array_map('intval', $selectedIds)),
            fn (int $selectedId): bool => $selectedId > 0,
        ));
    }

    /**
     * Called from Alpine when cart items are available.
     * Groups items by shop_id and stores result into $this->shopGroups
     * so the blade @foreach re-renders via Livewire reactivity.
     *
     * @param  array<int, array<string, mixed>>  $cartItems
     */
    public function resolveShopGroups(array $cartItems, ResolveShopGroupsAction $resolveShopGroupsAction): void
    {
        $selectedIds = $this->getSelectedIdsArrayProperty();

        $this->shopGroups = $this->normalizeShopGroups(
            $resolveShopGroupsAction->handle($this->normalizeCartItems($cartItems), $selectedIds),
        );
    }

    /**
     * Called from the shipping-rates child component when a rate is selected.
     */
    #[On('shipping-rate-selected')]
    public function onRateSelected(array $payload)
    {
        $shopId = $payload['shopId'];

        // Group shop rates by shop_id
        $this->shopRates[$shopId] = [
            'courier_code' => $payload['courier_code'],
            'courier_service_code' => $payload['courier_service_code'],
            'price' => (int) $payload['price'],
            'name' => $payload['name'],
            'etd' => $payload['etd'],
        ];

        // Calculate total shipping cost from all selected rates
        $this->totalShippingCost = (int) collect($this->shopRates)->sum('price');
    }

    #[On('shipping-address-selected')]
    public function onAddressSelected($locationId)
    {
        $this->selectedLocationId = (int) $locationId;
    }

    /**
     * Submit checkout
     */
    public function submit(?array $guestData, GetShippingRatesAction $getShippingRatesAction, StoreCheckoutAction $storeCheckoutAction)
    {
        $this->shopGroups = $this->normalizeShopGroups($this->shopGroups);

        $location = auth()->check()
            ? auth()->user()->locations()->whereKey($this->selectedLocationId)->first()
            : null;

        if (auth()->check() && ! $location) {
            // Toast message
            $this->dispatch('toast',
                type: 'error',
                message: 'Silakan pilih alamat tujuan pengiriman terlebih dahulu.',
            );

            return;
        }

        // Check if guest data is provided for guest checkout
        if (! auth()->check() && ! $guestData) {
            $this->dispatch('toast',
                type: 'error',
                message: 'Silakan isi data pengiriman terlebih dahulu.',
            );

            return;
        }

        if (! auth()->check()) {
            $guestData = Validator::make($guestData ?? [], [
                'contact_name' => ['required', 'string', 'max:255'],
                'contact_phone' => ['required', 'string', 'max:30'],
                'email' => ['required', 'email', 'max:255'],
                'address' => ['required', 'string', 'max:1000'],
                'note' => ['nullable', 'string', 'max:1000'],
                'postal_code' => ['required', 'string', 'max:20'],
                'area_string' => ['required', 'string', 'max:255'],
                'biteship_area_id' => ['required', 'string', 'max:255'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
            ])->validate();
        }

        // Validate the data before proceeding
        $this->validate([
            'selectedLocationId' => 'nullable|integer',
            'shopRates' => 'required|array',
            'shopRates.*.courier_code' => 'required|string',
            'shopRates.*.courier_service_code' => 'required|string',
            'shopRates.*.price' => 'required|integer|min:0',
            'shopRates.*.name' => 'required|string',
            'shopRates.*.etd' => 'nullable|string',
            'shopGroups' => 'required|array',
            'shopGroups.*.shop_id' => 'required|integer|exists:shops,id',
            'shopGroups.*.items' => 'required|array',
            'shopGroups.*.items.*' => 'required|integer|min:1|max:'.self::MAX_PURCHASABLE_QUANTITY, // value is qty, key is product_flat_id
        ]);

        // Validate that each shop in shopGroups has a corresponding rate in shopRates
        $totalCheckout = 0;
        $totalRates = 0;

        // Submited shop groups
        $submitedShopGroups = [];

        foreach ($this->shopGroups as $group) {
            $shopId = $group['shop_id'];
            if (! isset($this->shopRates[$shopId])) {
                $this->dispatch('toast',
                    type: 'error',
                    message: "Kurir untuk toko {$group['shop_name']} belum dipilih. Silakan pilih kurir terlebih dahulu.",
                );

                return;
            }

            // Validate the items in the group against the checkoutItems
            $totalCheckoutShop = 0;
            foreach ($group['items'] as $productFlatId => $qty) {
                $productFlat = ProductFlat::findOrFail($productFlatId);
                $totalCheckoutShop += $productFlat->price * $qty;

                // Store the submitted shop groups with product details for later use
                $submitedShopGroups[$shopId]['items'][$productFlatId] = [
                    'price' => $productFlat->price,
                    'qty' => $qty,
                    'total' => $productFlat->price * $qty,
                    'raw' => $productFlat->toArray(), // Store the raw product flat data for later use
                ];
            }

            // Validate the shipping rates
            $destinationAreaId = auth()->check()
                ? $location?->biteship_area_id
                : ($guestData['biteship_area_id'] ?? null);

            try {
                $availableRates = $getShippingRatesAction->handle(
                    shopId: $shopId,
                    destinationAreaId: $destinationAreaId,
                    items: $group['items'],
                );
            } catch (Exception $e) {
                Log::error('Failed to get shipping rates.', [
                    'shop_id' => (int) $shopId,
                    'product_flat_ids' => collect(array_keys($group['items']))
                        ->map(fn (int|string $productFlatId): int => (int) $productFlatId)
                        ->sort()
                        ->values()
                        ->all(),
                    'exception' => $e::class,
                ]);
                $this->dispatch('toast',
                    type: 'error',
                    message: "Gagal mendapatkan tarif pengiriman untuk toko {$group['shop_name']}.",
                );

                return;
            }

            $selectedRate = $this->shopRates[$shopId];

            $matchedRate = collect($availableRates)->first(function ($rate) use ($selectedRate) {
                return $rate['courier_code'] === $selectedRate['courier_code'] &&
                    $rate['courier_service_code'] === $selectedRate['courier_service_code'];
            });

            if (! $matchedRate) {
                $this->dispatch('toast',
                    type: 'error',
                    message: "Tarif pengiriman yang dipilih untuk toko {$group['shop_name']} tidak valid. Silakan pilih kurir yang tersedia.",
                );

                return;
            }

            // Make sure the price is updated to the matched rate's price
            $submitedShopGroups[$shopId]['selected_rate'] = $matchedRate;
            $submitedShopGroups[$shopId]['total_checkout'] = $totalCheckoutShop;
            $submitedShopGroups[$shopId]['total_shipping'] = $matchedRate['price'];
            $submitedShopGroups[$shopId]['total'] = $totalCheckoutShop + $matchedRate['price'];

            $totalCheckout += $totalCheckoutShop;
            $totalRates += $matchedRate['price'];
        }

        // Store order
        $submitedData = [
            'shop_groups' => $submitedShopGroups,
            'total_checkout' => $totalCheckout,
            'total_rates' => $totalRates,
            'application_fee' => $this->applicationFee,
            'insurance_fee' => $this->insuranceFee,
            'selected_location_id' => $this->selectedLocationId,
            'guest_data' => $guestData,
        ];

        try {
            $checkout = $storeCheckoutAction->handle($submitedData);
            $purchasedIds = collect($submitedShopGroups)
                ->flatMap(fn (array $shopGroup): array => array_keys($shopGroup['items']))
                ->map(fn (int|string $productFlatId): int => (int) $productFlatId)
                ->unique()
                ->values()
                ->all();

            // Dispatch success message
            $this->dispatch('toast',
                type: 'success',
                message: 'Order berhasil dibuat. Silakan lanjutkan ke pembayaran.',
            );

            $this->dispatch('remove-cart-items', ids: $purchasedIds);

            if (! auth()->check()) {
                $this->dispatch('delete-localstorage', key: 'checkout_guest_address');
            }

            // Redirect to order detail page with the order reference
            return $this->redirectRoute('payment.show', [
                'reference' => $checkout->reference,
                ...$checkout->guestRouteParameters(),
            ], navigate: true);
        } catch (Exception $e) {
            Log::error('Failed to store order.', [
                'checkout_actor' => auth()->check() ? 'user' : 'guest',
                'shop_ids' => collect(array_keys($submitedShopGroups))
                    ->map(fn (int|string $shopId): int => (int) $shopId)
                    ->sort()
                    ->values()
                    ->all(),
                'product_flat_ids' => collect($submitedShopGroups)
                    ->flatMap(fn (array $shopGroup): array => array_keys($shopGroup['items']))
                    ->map(fn (int|string $productFlatId): int => (int) $productFlatId)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
                'exception' => $e::class,
            ]);

            $this->dispatch('toast',
                type: 'error',
                message: 'Gagal membuat order. Silakan coba lagi.',
            );

            return;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $shopGroups
     * @return array<int, array<string, mixed>>
     */
    private function normalizeShopGroups(array $shopGroups): array
    {
        foreach ($shopGroups as &$group) {
            foreach ($group['items'] ?? [] as $productFlatId => $quantity) {
                $group['items'][$productFlatId] = $this->normalizeQuantity($quantity);
            }
        }
        unset($group);

        return $shopGroups;
    }

    /**
     * @param  array<int, mixed>  $cartItems
     * @return array<int, mixed>
     */
    private function normalizeCartItems(array $cartItems): array
    {
        return array_map(function (mixed $cartItem): mixed {
            if (! is_array($cartItem) || ! isset($cartItem['qty'])) {
                return $cartItem;
            }

            $cartItem['qty'] = $this->normalizeQuantity($cartItem['qty']);

            return $cartItem;
        }, $cartItems);
    }

    private function normalizeQuantity(mixed $quantity): int
    {
        $normalizedQuantity = is_numeric($quantity) ? (int) $quantity : 1;

        return min(self::MAX_PURCHASABLE_QUANTITY, max(1, $normalizedQuantity));
    }
};
