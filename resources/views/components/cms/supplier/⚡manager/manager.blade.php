<div class="space-y-8" x-data="{ activeTab: $wire.entangle('activeTab') }">
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex space-x-6" aria-label="Tabs">
            <button 
                type="button" 
                @click="activeTab = 'supplier'" 
                :class="activeTab === 'supplier' 
                    ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white font-semibold' 
                    : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'"
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm transition"
            >
                1. Tambah Supplier
            </button>
            <button 
                type="button" 
                @click="activeTab = 'warehouse'" 
                :class="activeTab === 'warehouse' 
                    ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white font-semibold' 
                    : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'"
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm transition"
            >
                2. Tambah Gudang
            </button>
            <button 
                type="button" 
                @click="activeTab = 'offer'" 
                :class="activeTab === 'offer' 
                    ? 'border-zinc-900 text-zinc-900 dark:border-white dark:text-white font-semibold' 
                    : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'"
                class="whitespace-nowrap border-b-2 py-3 px-1 text-sm transition"
            >
                3. Penawaran Produk
            </button>
        </nav>
    </div>

    <!-- Tab 1: Supplier -->
    <div x-show="activeTab === 'supplier'" x-cloak>
        <form wire:submit="storeSupplier" class="max-w-2xl space-y-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:input wire:model="supplierCode" label="Kode" placeholder="ANEKA" />
                <flux:input wire:model="supplierName" label="Nama supplier" />
                <flux:input wire:model="contactName" label="Nama kontak" />
                <flux:input wire:model="contactPhone" label="Telepon kontak" />
                <flux:input wire:model="orderPhone" label="WhatsApp order" />
                <flux:input wire:model="contactEmail" type="email" label="Email" />
            </div>
            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">Tambah supplier</flux:button>
            </div>
        </form>
    </div>

    <!-- Tab 2: Warehouse -->
    <div x-show="activeTab === 'warehouse'" x-cloak>
        <form wire:submit="storeWarehouse" class="max-w-4xl space-y-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <flux:select wire:model.live="warehouseSupplierId" label="Supplier">
                        <flux:select.option value="">-- Pilih Supplier --</flux:select.option>
                        @foreach($this->suppliers as $supplier)
                            <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->code }})</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="warehouseName" label="Nama gudang" />
                    <div class="grid grid-cols-2 gap-3">
                        <flux:input wire:model="warehouseContactName" label="Kontak" />
                        <flux:input wire:model="warehouseContactPhone" label="Telepon" />
                    </div>
                    <flux:textarea wire:model="warehouseAddress" label="Alamat pickup" rows="3" />
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <flux:input wire:model="warehouseAreaName" label="Area" />
                        <flux:input wire:model="warehousePostalCode" label="Kode pos" />
                        <flux:select wire:model="primaryProvider" label="Provider utama">
                            <flux:select.option value="biteship">Biteship</flux:select.option>
                            <flux:select.option value="mengantar">Mengantar</flux:select.option>
                        </flux:select>
                        <flux:select wire:model="fallbackProvider" label="Fallback">
                            <flux:select.option value="">Tanpa fallback</flux:select.option>
                            <flux:select.option value="biteship">Biteship</flux:select.option>
                            <flux:select.option value="mengantar">Mengantar</flux:select.option>
                        </flux:select>
                        <flux:input wire:model="biteshipAreaId" label="Biteship area ID" />
                        <flux:input wire:model="mengantarAreaId" label="Mengantar area ID" />
                        <flux:input wire:model="biteshipAddressId" label="Biteship address ID" />
                        <flux:input wire:model="mengantarAddressId" label="Mengantar address ID" />
                    </div>
                </div>
            </div>
            <div class="flex justify-end pt-4 border-t border-zinc-100 dark:border-zinc-800 mt-4">
                <flux:button type="submit" variant="primary">Tambah gudang</flux:button>
            </div>
        </form>
    </div>

    <!-- Tab 3: Offer -->
    <div x-show="activeTab === 'offer'" x-cloak>
        <form wire:submit="storeOffer" class="max-w-3xl space-y-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:select wire:model.live="offerSupplierId" label="Supplier">
                    <flux:select.option value="">-- Pilih Supplier --</flux:select.option>
                    @foreach($this->suppliers as $supplier)
                        <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->code }})</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="offerWarehouseId" label="Gudang">
                    <flux:select.option value="">-- Pilih Gudang --</flux:select.option>
                    @foreach($this->suppliers->firstWhere('id', (int) $offerSupplierId)?->warehouses ?? [] as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="productFlatId" label="Varian produk">
                    <flux:select.option value="">-- Pilih Varian --</flux:select.option>
                    @foreach($this->productFlats as $flat)
                        <flux:select.option value="{{ $flat->id }}">{{ $flat->product->name }} — {{ $flat->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="supplierSku" label="SKU supplier" />
                <flux:input wire:model="costPrice" type="number" min="0" label="Harga modal" />
                <flux:input wire:model="availableStock" type="number" min="0" label="Stok (kosong = ∞)" />
            </div>
            <div class="flex justify-end pt-2">
                <flux:button type="submit" variant="primary">Tambah & aktifkan</flux:button>
            </div>
        </form>
    </div>

    <!-- Active Dropship Sources -->
    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-200 p-5 dark:border-zinc-700">
            <flux:heading size="lg">Sumber dropship aktif</flux:heading>
        </div>
        @forelse($this->suppliers as $supplier)
            <div class="border-b border-zinc-200 p-5 last:border-b-0 dark:border-zinc-700" wire:key="supplier-{{ $supplier->id }}">
                <p class="font-semibold">{{ $supplier->name }} <span class="font-normal text-zinc-500">({{ $supplier->code }})</span></p>
                <p class="mt-1 text-sm text-zinc-500">{{ $supplier->warehouses->count() }} gudang · {{ $supplier->offers->count() }} penawaran</p>
                <div class="mt-3 grid gap-2 md:grid-cols-2">
                    @foreach($supplier->offers as $offer)
                        <div class="flex items-center justify-between gap-3 rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-800">
                            <div>
                                <p class="font-medium">{{ $offer->productFlat->name }}</p>
                                <p class="text-zinc-500">{{ $offer->supplier_sku }} · Rp {{ number_format((float) $offer->cost_price, 0, ',', '.') }}</p>
                            </div>
                            @if($offer->productFlat->active_supplier_offer_id === $offer->id)
                                <flux:badge color="green">Aktif</flux:badge>
                            @else
                                <flux:button size="sm" wire:click="activateOffer({{ $offer->id }})">Aktifkan</flux:button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="p-8 text-center text-sm text-zinc-500">Belum ada supplier.</p>
        @endforelse
    </section>
</div>
