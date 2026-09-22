<?php

use App\Actions\Cms\Product\Product\SetProductFulfillmentAction;
use App\Actions\Cms\Supplier\Offer\StoreSupplierOfferAction;
use App\Actions\Cms\Supplier\StoreSupplierAction;
use App\Actions\Cms\Supplier\Warehouse\StoreSupplierWarehouseAction;
use App\Enums\FulfillmentType;
use App\Models\Product\ProductFlat;
use App\Models\Supplier\Supplier;
use App\Models\Supplier\SupplierOffer;
use App\Models\Supplier\SupplierWarehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $activeTab = 'supplier';
    public string $supplierCode = '';
    public string $supplierName = '';
    public string $contactName = '';
    public string $contactPhone = '';
    public string $orderPhone = '';
    public string $contactEmail = '';

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

    public ?int $offerSupplierId = null;
    public ?int $offerWarehouseId = null;
    public ?int $productFlatId = null;
    public string $supplierSku = '';
    public string $costPrice = '';
    public string $availableStock = '';

    public function mount(): void
    {
        $this->authorizeOperator();

        if ($firstSupplier = Supplier::query()->first()) {
            $this->warehouseSupplierId = $firstSupplier->id;
            $this->offerSupplierId = $firstSupplier->id;
            if ($firstWarehouse = $firstSupplier->warehouses()->first()) {
                $this->offerWarehouseId = $firstWarehouse->id;
            }
        }
    }

    #[Computed]
    public function suppliers(): Collection
    {
        return Supplier::query()->with(['warehouses', 'offers.productFlat'])->latest()->get();
    }

    #[Computed]
    public function productFlats(): Collection
    {
        return ProductFlat::query()->with('product')->where('status', true)->orderBy('name')->get();
    }

    public function storeSupplier(StoreSupplierAction $action): void
    {
        $validated = $this->validate([
            'supplierCode' => ['required', 'alpha_dash', 'max:50', Rule::unique('suppliers', 'code')],
            'supplierName' => ['required', 'string', 'max:255'],
            'contactName' => ['nullable', 'string', 'max:255'],
            'contactPhone' => ['nullable', 'string', 'max:30'],
            'orderPhone' => ['nullable', 'string', 'max:30'],
            'contactEmail' => ['nullable', 'email', 'max:255'],
        ]);

        $supplier = $action->handle([
            'code' => strtoupper($validated['supplierCode']),
            'name' => $validated['supplierName'],
            'contact_name' => $validated['contactName'] ?: null,
            'contact_phone' => $validated['contactPhone'] ?: null,
            'order_phone' => $validated['orderPhone'] ?: null,
            'contact_email' => $validated['contactEmail'] ?: null,
            'is_active' => true,
        ]);

        $this->reset(['supplierCode', 'supplierName', 'contactName', 'contactPhone', 'orderPhone', 'contactEmail']);
        $this->warehouseSupplierId = $supplier->getKey();
        $this->offerSupplierId = $supplier->getKey();
        $this->activeTab = 'warehouse';
        $this->refreshLists('Supplier berhasil ditambahkan. Silakan tambahkan gudang.');
    }

    public function storeWarehouse(StoreSupplierWarehouseAction $action): void
    {
        $validated = $this->validate([
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
        ]);

        $supplier = Supplier::query()->findOrFail($validated['warehouseSupplierId']);
        $warehouse = $action->handle($supplier, [
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
            'is_active' => true,
        ]);

        $this->reset(['warehouseName', 'warehouseContactName', 'warehouseContactPhone', 'warehouseAddress', 'warehousePostalCode', 'warehouseAreaName', 'biteshipAreaId', 'mengantarAreaId', 'biteshipAddressId', 'mengantarAddressId']);
        $this->offerSupplierId = $supplier->getKey();
        $this->offerWarehouseId = $warehouse->getKey();
        $this->activeTab = 'offer';
        $this->refreshLists('Gudang supplier berhasil ditambahkan. Silakan tambahkan penawaran varian produk.');
    }

    public function storeOffer(StoreSupplierOfferAction $store, SetProductFulfillmentAction $setFulfillment): void
    {
        $validated = $this->validate([
            'offerSupplierId' => ['required', 'exists:suppliers,id'],
            'offerWarehouseId' => ['required', 'exists:supplier_warehouses,id'],
            'productFlatId' => ['required', 'exists:product_flats,id'],
            'supplierSku' => ['required', 'string', 'max:255'],
            'costPrice' => ['required', 'numeric', 'min:0'],
            'availableStock' => ['nullable', 'integer', 'min:0'],
        ]);

        $supplier = Supplier::query()->findOrFail($validated['offerSupplierId']);
        $warehouse = SupplierWarehouse::query()->findOrFail($validated['offerWarehouseId']);
        $productFlat = ProductFlat::query()->findOrFail($validated['productFlatId']);
        $offer = $store->handle($supplier, $warehouse, $productFlat, [
            'supplier_sku' => $validated['supplierSku'],
            'cost_price' => $validated['costPrice'],
            'available_stock' => $validated['availableStock'] === '' ? null : $validated['availableStock'],
            'is_available' => true,
            'is_active' => true,
        ]);
        $setFulfillment->handle($productFlat, FulfillmentType::SupplierDropship, $offer);

        $this->reset(['productFlatId', 'supplierSku', 'costPrice', 'availableStock']);
        $this->refreshLists('Penawaran dibuat dan diaktifkan untuk varian produk.');
    }

    public function activateOffer(int $offerId, SetProductFulfillmentAction $action): void
    {
        $offer = SupplierOffer::query()->with('productFlat')->findOrFail($offerId);
        $action->handle($offer->productFlat, FulfillmentType::SupplierDropship, $offer);
        $this->refreshLists('Sumber fulfillment aktif diperbarui.');
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
