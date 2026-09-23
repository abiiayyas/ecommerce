<?php

use App\Actions\Cms\Product\Product\SetProductFulfillmentAction;
use App\Actions\Cms\Supplier\DeleteSupplierAction;
use App\Actions\Cms\Supplier\Offer\DeleteSupplierOfferAction;
use App\Actions\Cms\Supplier\Offer\StoreSupplierOfferAction;
use App\Actions\Cms\Supplier\Offer\UpdateSupplierOfferAction;
use App\Actions\Cms\Supplier\StoreSupplierAction;
use App\Actions\Cms\Supplier\UpdateSupplierAction;
use App\Actions\Cms\Supplier\Warehouse\DeleteSupplierWarehouseAction;
use App\Actions\Cms\Supplier\Warehouse\StoreSupplierWarehouseAction;
use App\Actions\Cms\Supplier\Warehouse\UpdateSupplierWarehouseAction;
use App\Enums\FulfillmentType;
use App\Models\Product\ProductFlat;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierOffer;
use App\Models\Supplier\SupplierWarehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $activeTab = 'supplier';

    public ?int $editingSupplierId = null;
    public string $supplierCode = '';
    public string $supplierName = '';
    public string $contactName = '';
    public string $contactPhone = '';
    public string $orderPhone = '';
    public string $contactEmail = '';
    public bool $supplierIsActive = true;

    public ?int $editingWarehouseId = null;
    public ?int $warehouseSupplierId = null;
    public string $warehouseName = '';
    public string $warehouseContactName = '';
    public string $warehouseContactPhone = '';
    public string $warehouseAddress = '';
    public string $warehousePostalCode = '';
    public string $warehouseAreaName = '';
    public string $primaryProvider = 'biteship';
    public string $fallbackProvider = 'mengantar';
    public string $biteshipAreaId = '';
    public string $mengantarAreaId = '';
    public string $biteshipAddressId = '';
    public string $mengantarAddressId = '';
    public bool $warehouseIsActive = true;

    public ?int $editingOfferId = null;
    public ?int $offerSupplierId = null;
    public ?int $offerWarehouseId = null;
    public ?int $productFlatId = null;
    public string $supplierSku = '';
    public string $costPrice = '';
    public string $availableStock = '';
    public bool $offerIsAvailable = true;
    public bool $offerIsActive = true;

    public function mount(): void
    {
        $this->authorizeOperator();

        if ($firstSupplier = Supplier::query()->latest()->first()) {
            $this->warehouseSupplierId = $firstSupplier->id;
            $this->offerSupplierId = $firstSupplier->id;

            if ($firstWarehouse = $firstSupplier->warehouses()->latest()->first()) {
                $this->offerWarehouseId = $firstWarehouse->id;
            }
        }
    }

    #[Computed]
    public function suppliers(): Collection
    {
        return Supplier::query()
            ->with(['warehouses.offers', 'offers.productFlat.product'])
            ->latest()
            ->get();
    }

    #[Computed]
    public function productFlats(): Collection
    {
        return ProductFlat::query()
            ->with('product')
            ->where('status', true)
            ->orderBy('name')
            ->get();
    }

    public function storeSupplier(StoreSupplierAction $action): void
    {
        $validated = $this->validateSupplier();
        $payload = [
            'code' => strtoupper($validated['supplierCode']),
            'name' => $validated['supplierName'],
            'contact_name' => $validated['contactName'] ?: null,
            'contact_phone' => $validated['contactPhone'] ?: null,
            'order_phone' => $validated['orderPhone'] ?: null,
            'contact_email' => $validated['contactEmail'] ?: null,
            'is_active' => $validated['supplierIsActive'],
        ];

        if ($this->editingSupplierId) {
            $supplier = app(UpdateSupplierAction::class)->handle(
                Supplier::query()->findOrFail($this->editingSupplierId),
                $payload,
            );
            $this->resetSupplierForm();
            $this->refreshLists('Supplier berhasil diperbarui.');
        } else {
            $supplier = $action->handle($payload);
            $this->resetSupplierForm();
            $this->warehouseSupplierId = $supplier->getKey();
            $this->offerSupplierId = $supplier->getKey();
            $this->activeTab = 'warehouse';
            $this->refreshLists('Supplier berhasil ditambahkan. Silakan tambahkan gudang.');
        }
    }

    public function storeWarehouse(StoreSupplierWarehouseAction $action): void
    {
        $validated = $this->validateWarehouse();
        $supplier = Supplier::query()->findOrFail($validated['warehouseSupplierId']);

        if (! $supplier->is_active) {
            $this->addError('warehouseSupplierId', 'Aktifkan supplier terlebih dahulu.');

            return;
        }

        $payload = $this->warehousePayload($validated);

        if ($this->editingWarehouseId) {
            $warehouse = SupplierWarehouse::query()->findOrFail($this->editingWarehouseId);
            abort_unless($warehouse->supplier_id === $supplier->getKey(), 404);
            $updatedWarehouse = app(UpdateSupplierWarehouseAction::class)->handle($warehouse, $payload);

            if (! $updatedWarehouse->is_active) {
                $this->deactivateWarehouseOffers($updatedWarehouse, app(SetProductFulfillmentAction::class), app(UpdateSupplierOfferAction::class));
            }

            $this->resetWarehouseForm();
            $this->refreshLists('Gudang supplier berhasil diperbarui.');
        } else {
            $warehouse = $action->handle($supplier, $payload);
            $this->resetWarehouseForm();
            $this->offerSupplierId = $supplier->getKey();
            $this->offerWarehouseId = $warehouse->getKey();
            $this->activeTab = 'offer';
            $this->refreshLists('Gudang supplier berhasil ditambahkan. Silakan tambahkan penawaran varian produk.');
        }
    }

    public function storeOffer(StoreSupplierOfferAction $store, SetProductFulfillmentAction $setFulfillment): void
    {
        $validated = $this->validateOffer();
        $supplier = Supplier::query()->findOrFail($validated['offerSupplierId']);
        $warehouse = SupplierWarehouse::query()->findOrFail($validated['offerWarehouseId']);
        $productFlat = ProductFlat::query()->findOrFail($validated['productFlatId']);

        if (! $supplier->is_active || ! $warehouse->is_active) {
            $this->addError('offerSupplierId', 'Supplier dan gudang harus aktif.');

            return;
        }

        $payload = $this->offerPayload($validated);

        if ($this->editingOfferId) {
            $offer = SupplierOffer::query()->with('productFlat')->findOrFail($this->editingOfferId);
            abort_unless($offer->supplier_id === $supplier->getKey(), 404);
            $offer = app(UpdateSupplierOfferAction::class)->handle($offer, $warehouse, $payload);

            if ((int) $offer->productFlat->active_supplier_offer_id === $offer->getKey() && (! $offer->is_active || ! $offer->is_available)) {
                $setFulfillment->handle($offer->productFlat, FulfillmentType::OwnedStock);
            }

            $this->resetOfferForm();
            $this->refreshLists('Penawaran supplier berhasil diperbarui.');
        } else {
            $offer = $store->handle($supplier, $warehouse, $productFlat, $payload);
            $setFulfillment->handle($productFlat, FulfillmentType::SupplierDropship, $offer);

            $this->resetOfferForm();
            $this->refreshLists('Penawaran dibuat dan diaktifkan untuk varian produk.');
        }
    }

    public function editSupplier(int $supplierId): void
    {
        $supplier = Supplier::query()->findOrFail($supplierId);
        $this->editingSupplierId = $supplier->getKey();
        $this->supplierCode = $supplier->code;
        $this->supplierName = $supplier->name;
        $this->contactName = $supplier->contact_name ?? '';
        $this->contactPhone = $supplier->contact_phone ?? '';
        $this->orderPhone = $supplier->order_phone ?? '';
        $this->contactEmail = $supplier->contact_email ?? '';
        $this->supplierIsActive = $supplier->is_active;
        $this->activeTab = 'supplier';
        $this->resetValidation();
    }

    public function editWarehouse(int $warehouseId): void
    {
        $warehouse = SupplierWarehouse::query()->findOrFail($warehouseId);
        $areaIds = $warehouse->provider_area_ids ?? [];
        $addressIds = $warehouse->provider_address_ids ?? [];
        $this->editingWarehouseId = $warehouse->getKey();
        $this->warehouseSupplierId = $warehouse->supplier_id;
        $this->warehouseName = $warehouse->name;
        $this->warehouseContactName = $warehouse->contact_name ?? '';
        $this->warehouseContactPhone = $warehouse->contact_phone ?? '';
        $this->warehouseAddress = $warehouse->address;
        $this->warehousePostalCode = $warehouse->postal_code ?? '';
        $this->warehouseAreaName = $warehouse->area_name ?? '';
        $this->primaryProvider = $warehouse->primary_shipping_provider;
        $this->fallbackProvider = $warehouse->fallback_shipping_provider ?? '';
        $this->biteshipAreaId = $areaIds['biteship'] ?? '';
        $this->mengantarAreaId = $areaIds['mengantar'] ?? '';
        $this->biteshipAddressId = $addressIds['biteship'] ?? '';
        $this->mengantarAddressId = $addressIds['mengantar'] ?? '';
        $this->warehouseIsActive = $warehouse->is_active;
        $this->activeTab = 'warehouse';
        $this->resetValidation();
    }

    public function editOffer(int $offerId): void
    {
        $offer = SupplierOffer::query()->findOrFail($offerId);
        $this->editingOfferId = $offer->getKey();
        $this->offerSupplierId = $offer->supplier_id;
        $this->offerWarehouseId = $offer->supplier_warehouse_id;
        $this->productFlatId = $offer->product_flat_id;
        $this->supplierSku = $offer->supplier_sku;
        $this->costPrice = (string) $offer->cost_price;
        $this->availableStock = $offer->available_stock === null ? '' : (string) $offer->available_stock;
        $this->offerIsAvailable = $offer->is_available;
        $this->offerIsActive = $offer->is_active;
        $this->activeTab = 'offer';
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        match ($this->activeTab) {
            'supplier' => $this->resetSupplierForm(),
            'warehouse' => $this->resetWarehouseForm(),
            'offer' => $this->resetOfferForm(),
            default => null,
        };
        $this->resetValidation();
    }

    public function deactivateOffer(int $offerId, UpdateSupplierOfferAction $update, SetProductFulfillmentAction $setFulfillment): void
    {
        $offer = SupplierOffer::query()->with('productFlat', 'warehouse')->findOrFail($offerId);
        if ((int) $offer->productFlat->active_supplier_offer_id === $offer->getKey()) {
            $setFulfillment->handle($offer->productFlat, FulfillmentType::OwnedStock);
        }

        $update->handle($offer, $offer->warehouse, [
            'supplier_sku' => $offer->supplier_sku,
            'cost_price' => $offer->cost_price,
            'available_stock' => $offer->available_stock,
            'is_available' => false,
            'is_active' => false,
        ]);
        $this->refreshLists('Sumber dropship dinonaktifkan dan varian dikembalikan ke stok sendiri.');
    }

    public function activateOffer(int $offerId, UpdateSupplierOfferAction $update, SetProductFulfillmentAction $action): void
    {
        $offer = SupplierOffer::query()->with(['productFlat', 'warehouse', 'supplier'])->findOrFail($offerId);
        if (! $offer->supplier->is_active || ! $offer->warehouse->is_active) {
            $this->addError('offerSupplierId', 'Aktifkan supplier dan gudang terlebih dahulu.');

            return;
        }

        $offer = $update->handle($offer, $offer->warehouse, [
            'supplier_sku' => $offer->supplier_sku,
            'cost_price' => $offer->cost_price,
            'available_stock' => $offer->available_stock,
            'is_available' => true,
            'is_active' => true,
        ]);
        $action->handle($offer->productFlat, FulfillmentType::SupplierDropship, $offer);
        $this->refreshLists('Sumber fulfillment aktif diperbarui.');
    }

    public function toggleSupplier(int $supplierId, UpdateSupplierAction $update, SetProductFulfillmentAction $setFulfillment, UpdateSupplierOfferAction $updateOffer): void
    {
        $supplier = Supplier::query()->findOrFail($supplierId);
        $isActive = ! $supplier->is_active;
        $update->handle($supplier, ['is_active' => $isActive]);

        if (! $isActive) {
            foreach ($supplier->offers()->with(['productFlat', 'warehouse'])->where('is_active', true)->get() as $offer) {
                if ((int) $offer->productFlat->active_supplier_offer_id === $offer->getKey()) {
                    $setFulfillment->handle($offer->productFlat, FulfillmentType::OwnedStock);
                }
                $updateOffer->handle($offer, $offer->warehouse, [
                    'supplier_sku' => $offer->supplier_sku,
                    'cost_price' => $offer->cost_price,
                    'available_stock' => $offer->available_stock,
                    'is_available' => false,
                    'is_active' => false,
                ]);
            }
        }

        $this->refreshLists($isActive ? 'Supplier diaktifkan.' : 'Supplier dinonaktifkan beserta sumber dropshipnya.');
    }

    public function toggleWarehouse(int $warehouseId, UpdateSupplierWarehouseAction $update, SetProductFulfillmentAction $setFulfillment, UpdateSupplierOfferAction $updateOffer): void
    {
        $warehouse = SupplierWarehouse::query()->findOrFail($warehouseId);
        $isActive = ! $warehouse->is_active;
        $update->handle($warehouse, ['is_active' => $isActive]);

        if (! $isActive) {
            foreach ($warehouse->offers()->with('productFlat')->where('is_active', true)->get() as $offer) {
                if ((int) $offer->productFlat->active_supplier_offer_id === $offer->getKey()) {
                    $setFulfillment->handle($offer->productFlat, FulfillmentType::OwnedStock);
                }
                $updateOffer->handle($offer, $warehouse, [
                    'supplier_sku' => $offer->supplier_sku,
                    'cost_price' => $offer->cost_price,
                    'available_stock' => $offer->available_stock,
                    'is_available' => false,
                    'is_active' => false,
                ]);
            }
        }

        $this->refreshLists($isActive ? 'Gudang diaktifkan.' : 'Gudang dinonaktifkan beserta sumber dropshipnya.');
    }

    public function deleteSupplier(int $supplierId, DeleteSupplierAction $action): void
    {
        try {
            $action->handle(Supplier::query()->findOrFail($supplierId));
            $this->refreshLists('Supplier berhasil dihapus.');
        } catch (ValidationException $exception) {
            $this->addError('supplier', $exception->getMessage());
        }
    }

    public function deleteWarehouse(int $warehouseId, DeleteSupplierWarehouseAction $action): void
    {
        try {
            $action->handle(SupplierWarehouse::query()->findOrFail($warehouseId));
            $this->refreshLists('Gudang berhasil dihapus.');
        } catch (ValidationException $exception) {
            $this->addError('warehouse', $exception->getMessage());
        }
    }

    public function deleteOffer(int $offerId, DeleteSupplierOfferAction $action, SetProductFulfillmentAction $setFulfillment): void
    {
        try {
            $offer = SupplierOffer::query()->with('productFlat')->findOrFail($offerId);

            if ((int) $offer->productFlat->active_supplier_offer_id === $offer->getKey()) {
                $setFulfillment->handle($offer->productFlat, FulfillmentType::OwnedStock);
            }

            $action->handle($offer);
            $this->refreshLists('Penawaran supplier berhasil dihapus.');
        } catch (ValidationException $exception) {
            $this->addError('offer', $exception->getMessage());
        }
    }

    private function validateSupplier(): array
    {
        return $this->validate([
            'supplierCode' => ['required', 'alpha_dash', 'max:50', Rule::unique('suppliers', 'code')->ignore($this->editingSupplierId)],
            'supplierName' => ['required', 'string', 'max:255'],
            'contactName' => ['nullable', 'string', 'max:255'],
            'contactPhone' => ['nullable', 'string', 'max:30'],
            'orderPhone' => ['nullable', 'string', 'max:30'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
            'supplierIsActive' => ['boolean'],
        ]);
    }

    private function validateWarehouse(): array
    {
        return $this->validate([
            'warehouseSupplierId' => ['required', 'exists:suppliers,id'],
            'warehouseName' => ['required', 'string', 'max:255'],
            'warehouseContactName' => ['required', 'string', 'max:255'],
            'warehouseContactPhone' => ['required', 'string', 'max:30'],
            'warehouseAddress' => ['required', 'string', 'max:1000'],
            'warehousePostalCode' => ['required', 'string', 'max:10'],
            'warehouseAreaName' => ['required', 'string', 'max:255'],
            'primaryProvider' => ['required', Rule::in(['biteship', 'mengantar'])],
            'fallbackProvider' => ['nullable', Rule::in(['biteship', 'mengantar'])],
            'biteshipAreaId' => ['nullable', 'string', 'max:255'],
            'mengantarAreaId' => ['nullable', 'string', 'max:255'],
            'biteshipAddressId' => ['nullable', 'string', 'max:255'],
            'mengantarAddressId' => ['nullable', 'string', 'max:255'],
            'warehouseIsActive' => ['boolean'],
        ]);
    }

    private function validateOffer(): array
    {
        return $this->validate([
            'offerSupplierId' => ['required', 'exists:suppliers,id'],
            'offerWarehouseId' => ['required', 'exists:supplier_warehouses,id'],
            'productFlatId' => ['required', 'exists:product_flats,id'],
            'supplierSku' => ['required', 'string', 'max:255'],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'availableStock' => ['nullable', 'integer', 'min:0'],
            'offerIsAvailable' => ['boolean'],
            'offerIsActive' => ['boolean'],
        ]);
    }

    private function warehousePayload(array $validated): array
    {
        return [
            'name' => $validated['warehouseName'],
            'contact_name' => $validated['warehouseContactName'],
            'contact_phone' => $validated['warehouseContactPhone'],
            'address' => $validated['warehouseAddress'],
            'postal_code' => $validated['warehousePostalCode'],
            'area_name' => $validated['warehouseAreaName'],
            'primary_shipping_provider' => $validated['primaryProvider'],
            'fallback_shipping_provider' => $validated['fallbackProvider'] ?: null,
            'provider_area_ids' => array_filter(['biteship' => $validated['biteshipAreaId'], 'mengantar' => $validated['mengantarAreaId']]),
            'provider_address_ids' => array_filter(['biteship' => $validated['biteshipAddressId'], 'mengantar' => $validated['mengantarAddressId']]),
            'is_active' => $validated['warehouseIsActive'],
        ];
    }

    private function offerPayload(array $validated): array
    {
        return [
            'supplier_sku' => $validated['supplierSku'],
            'cost_price' => $validated['costPrice'],
            'available_stock' => $validated['availableStock'] === '' ? null : $validated['availableStock'],
            'is_available' => $validated['offerIsAvailable'],
            'is_active' => $validated['offerIsActive'],
        ];
    }

    private function deactivateWarehouseOffers(SupplierWarehouse $warehouse, SetProductFulfillmentAction $setFulfillment, UpdateSupplierOfferAction $updateOffer): void
    {
        foreach ($warehouse->offers()->with('productFlat')->where('is_active', true)->get() as $offer) {
            if ((int) $offer->productFlat->active_supplier_offer_id === $offer->getKey()) {
                $setFulfillment->handle($offer->productFlat, FulfillmentType::OwnedStock);
            }
            $updateOffer->handle($offer, $warehouse, [
                'supplier_sku' => $offer->supplier_sku,
                'cost_price' => $offer->cost_price,
                'available_stock' => $offer->available_stock,
                'is_available' => false,
                'is_active' => false,
            ]);
        }
    }

    private function resetSupplierForm(): void
    {
        $this->reset(['editingSupplierId', 'supplierCode', 'supplierName', 'contactName', 'contactPhone', 'orderPhone', 'contactEmail']);
        $this->supplierIsActive = true;
    }

    private function resetWarehouseForm(): void
    {
        $this->reset(['editingWarehouseId', 'warehouseName', 'warehouseContactName', 'warehouseContactPhone', 'warehouseAddress', 'warehousePostalCode', 'warehouseAreaName', 'biteshipAreaId', 'mengantarAreaId', 'biteshipAddressId', 'mengantarAddressId']);
        $this->warehouseIsActive = true;
        $this->primaryProvider = 'biteship';
        $this->fallbackProvider = 'mengantar';
    }

    private function resetOfferForm(): void
    {
        $this->reset(['editingOfferId', 'productFlatId', 'supplierSku', 'costPrice', 'availableStock']);
        $this->offerIsAvailable = true;
        $this->offerIsActive = true;
    }

    private function refreshLists(string $message): void
    {
        unset($this->suppliers, $this->productFlats);
        $this->dispatch('toast', type: 'success', message: $message);
    }

    private function authorizeOperator(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->hasRole('superadmin'), 403);
    }
};
