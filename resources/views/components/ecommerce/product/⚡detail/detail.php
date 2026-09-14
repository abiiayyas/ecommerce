<?php

use App\Models\Product\Product;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Sqids\Sqids;

new class extends Component
{
    private const MAX_PURCHASABLE_QUANTITY = 100;

    #[Locked]
    public Product $product;

    public array $variants = [];

    public function mount(): void
    {
        $this->product->load('productFlats.media', 'shop.location', 'productAttributeGroups.productAttributes.attribute');

        // Flatten all product attributes into a single array of variants, grouped by product flat ID
        $this->variants = $this->product->productAttributeGroups
            ->flatMap(fn ($group) => $group->productAttributes)
            ->groupBy('product_flat_id')
            ->map(fn ($attributes, $productFlatId) => [
                'product_flat_id' => $productFlatId,
                'label' => $attributes->map(fn ($attr) => $attr->attribute->name)->join(' - '),
            ])
            ->values()
            ->all();
    }

    public function buyNow(int $productFlatId, mixed $quantity): void
    {
        $productFlat = $this->product->productFlats()
            ->whereKey($productFlatId)
            ->where('status', true)
            ->first();

        if (! $productFlat || (! $productFlat->is_unlimited_stock && $productFlat->stock < 1)) {
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Produk sedang tidak tersedia.',
            );

            return;
        }

        $normalizedQuantity = is_numeric($quantity) ? (int) $quantity : 1;
        $normalizedQuantity = min(self::MAX_PURCHASABLE_QUANTITY, max(1, $normalizedQuantity));

        if (! $productFlat->is_unlimited_stock) {
            $normalizedQuantity = min($normalizedQuantity, $productFlat->stock);
        }

        $this->dispatch(
            'buy-now',
            item: [
                'id' => $productFlat->id,
                'shop_id' => $productFlat->shop_id,
                'shop_name' => $this->product->shop->name,
                'name' => $productFlat->name,
                'price' => (float) $productFlat->price,
                'image' => $productFlat->getFirstMediaUrl('image_slot_0'),
                'qty' => $normalizedQuantity,
            ],
            checkoutUrl: route('checkout', [
                'items' => (new Sqids)->encode([$productFlat->id]),
            ]),
        );
    }
};
