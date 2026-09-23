<div class="space-y-8" x-data="{ activeTab: $wire.entangle('activeTab') }">
    @if ($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-200" role="alert">
            <ul class="grid gap-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-2 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <nav class="flex flex-wrap gap-2" aria-label="Tahapan supplier">
            @foreach ([['supplier', '1. Supplier'], ['warehouse', '2. Gudang'], ['offer', '3. Penawaran produk']] as [$tab, $label])
                <button
                    type="button"
                    @click="activeTab = '{{ $tab }}'"
                    :class="activeTab === '{{ $tab }}' ? 'bg-[#0c37b0] text-white shadow-sm' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800'"
                    class="min-h-11 rounded-lg px-4 py-2 text-left text-sm font-medium transition"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    <div x-show="activeTab === 'supplier'" x-cloak>
        <form wire:submit="storeSupplier" class="max-w-3xl space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ $editingSupplierId ? 'Edit supplier' : 'Tambah supplier' }}</flux:heading>
                <flux:text class="mt-1">Simpan identitas dan kontak sumber dropship.</flux:text>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="supplierCode" label="Kode" placeholder="ANEKA" />
                <flux:input wire:model="supplierName" label="Nama supplier" />
                <flux:input wire:model="contactName" label="Nama kontak" />
                <flux:input wire:model="contactPhone" label="Telepon kontak" />
                <flux:input wire:model="orderPhone" label="WhatsApp order" />
                <flux:input wire:model="contactEmail" type="email" label="Email" />
            </div>
            <label class="flex items-center gap-3 text-sm font-medium">
                <input type="checkbox" wire:model="supplierIsActive" class="size-4 rounded border-zinc-300 text-[#0c37b0] focus:ring-[#0c37b0]">
                <span>Supplier aktif sebagai sumber dropship</span>
            </label>
            <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                @if ($editingSupplierId)
                    <flux:button type="button" wire:click="cancelEdit">Batal</flux:button>
                @endif
                <flux:button type="submit" variant="primary">{{ $editingSupplierId ? 'Simpan perubahan' : 'Tambah supplier' }}</flux:button>
            </div>
        </form>
    </div>

    <div x-show="activeTab === 'warehouse'" x-cloak>
        <form wire:submit="storeWarehouse" class="max-w-5xl space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ $editingWarehouseId ? 'Edit gudang' : 'Tambah gudang' }}</flux:heading>
                <flux:text class="mt-1">Tentukan alamat pickup dan provider pengiriman.</flux:text>
            </div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="space-y-4">
                    <flux:select wire:model.live="warehouseSupplierId" label="Supplier">
                        <flux:select.option value="">-- Pilih supplier --</flux:select.option>
                        @foreach ($this->suppliers as $supplier)
                            <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->code }}){{ $supplier->is_active ? '' : ' — nonaktif' }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:input wire:model="warehouseName" label="Nama gudang" />
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <flux:input wire:model="warehouseContactName" label="Kontak" />
                        <flux:input wire:model="warehouseContactPhone" label="Telepon" />
                    </div>
                    <flux:textarea wire:model="warehouseAddress" label="Alamat pickup" rows="3" />
                </div>
                <div class="space-y-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
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
            <label class="flex items-center gap-3 text-sm font-medium">
                <input type="checkbox" wire:model="warehouseIsActive" class="size-4 rounded border-zinc-300 text-[#0c37b0] focus:ring-[#0c37b0]">
                <span>Gudang aktif sebagai lokasi pickup</span>
            </label>
            <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                @if ($editingWarehouseId)
                    <flux:button type="button" wire:click="cancelEdit">Batal</flux:button>
                @endif
                <flux:button type="submit" variant="primary">{{ $editingWarehouseId ? 'Simpan perubahan' : 'Tambah gudang' }}</flux:button>
            </div>
        </form>
    </div>

    <div x-show="activeTab === 'offer'" x-cloak>
        <form wire:submit="storeOffer" class="max-w-4xl space-y-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="lg">{{ $editingOfferId ? 'Edit penawaran produk' : 'Tambah penawaran produk' }}</flux:heading>
                <flux:text class="mt-1">Pilih satu penawaran sebagai sumber fulfillment varian.</flux:text>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="offerSupplierId" label="Supplier">
                    <flux:select.option value="">-- Pilih supplier --</flux:select.option>
                    @foreach ($this->suppliers as $supplier)
                        <flux:select.option value="{{ $supplier->id }}">{{ $supplier->name }} ({{ $supplier->code }}){{ $supplier->is_active ? '' : ' — nonaktif' }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="offerWarehouseId" label="Gudang">
                    <flux:select.option value="">-- Pilih gudang --</flux:select.option>
                    @foreach ($this->suppliers->firstWhere('id', (int) $offerSupplierId)?->warehouses ?? [] as $warehouse)
                        <flux:select.option value="{{ $warehouse->id }}">{{ $warehouse->name }}{{ $warehouse->is_active ? '' : ' — nonaktif' }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="productFlatId" label="Varian produk" :disabled="$editingOfferId !== null">
                    <flux:select.option value="">-- Pilih varian --</flux:select.option>
                    @foreach ($this->productFlats as $flat)
                        <flux:select.option value="{{ $flat->id }}">{{ $flat->product->name }} — {{ $flat->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="supplierSku" label="SKU supplier" />
                <flux:input wire:model="costPrice" type="number" min="0" label="Harga modal" />
                <flux:input wire:model="availableStock" type="number" min="0" label="Stok (kosong = ∞)" />
            </div>
            <div class="flex flex-wrap gap-6 border-t border-zinc-100 pt-4 text-sm dark:border-zinc-800">
                <label class="flex items-center gap-3 font-medium">
                    <input type="checkbox" wire:model="offerIsAvailable" class="size-4 rounded border-zinc-300 text-[#0c37b0] focus:ring-[#0c37b0]">
                    <span>Stok tersedia</span>
                </label>
                <label class="flex items-center gap-3 font-medium">
                    <input type="checkbox" wire:model="offerIsActive" class="size-4 rounded border-zinc-300 text-[#0c37b0] focus:ring-[#0c37b0]">
                    <span>Sumber aktif</span>
                </label>
            </div>
            <div class="flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                @if ($editingOfferId)
                    <flux:button type="button" wire:click="cancelEdit">Batal</flux:button>
                @endif
                <flux:button type="submit" variant="primary">{{ $editingOfferId ? 'Simpan perubahan' : 'Tambah & aktifkan' }}</flux:button>
            </div>
        </form>
    </div>

    <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-col gap-2 border-b border-zinc-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700">
            <div>
                <flux:heading size="lg">Sumber dropship</flux:heading>
                <flux:text class="mt-1">Kelola status supplier, gudang, dan penawaran tanpa menghapus riwayat.</flux:text>
            </div>
            <flux:badge color="zinc">{{ $this->suppliers->sum(fn ($supplier) => $supplier->offers->where('is_active', true)->count()) }} penawaran aktif</flux:badge>
        </div>
        @forelse ($this->suppliers as $supplier)
            <div class="space-y-4 border-b border-zinc-200 p-5 last:border-b-0 dark:border-zinc-700" wire:key="supplier-{{ $supplier->id }}">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold">{{ $supplier->name }}</p>
                            <span class="text-sm text-zinc-500">({{ $supplier->code }})</span>
                            <flux:badge :color="$supplier->is_active ? 'green' : 'zinc'" size="sm">{{ $supplier->is_active ? 'Aktif' : 'Nonaktif' }}</flux:badge>
                        </div>
                        <p class="mt-1 text-sm text-zinc-500">{{ $supplier->warehouses->count() }} gudang · {{ $supplier->offers->count() }} penawaran</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <flux:button size="sm" wire:click="editSupplier({{ $supplier->id }})">Edit</flux:button>
                        <flux:button size="sm" wire:click="toggleSupplier({{ $supplier->id }})">{{ $supplier->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="deleteSupplier({{ $supplier->id }})" wire:confirm="Hapus supplier ini? Supplier dengan gudang atau penawaran tidak dapat dihapus.">Hapus</flux:button>
                    </div>
                </div>

                @if ($supplier->warehouses->isNotEmpty())
                    <div class="grid gap-3 lg:grid-cols-2">
                        @foreach ($supplier->warehouses as $warehouse)
                            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700" wire:key="warehouse-{{ $warehouse->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-medium">{{ $warehouse->name }}</p>
                                            <flux:badge :color="$warehouse->is_active ? 'green' : 'zinc'" size="sm">{{ $warehouse->is_active ? 'Aktif' : 'Nonaktif' }}</flux:badge>
                                        </div>
                                        <p class="mt-1 text-sm text-zinc-500">{{ $warehouse->area_name ?: 'Area belum diisi' }} · {{ $warehouse->offers->count() }} penawaran</p>
                                    </div>
                                    <div class="flex shrink-0 gap-1">
                                        <flux:button size="sm" wire:click="editWarehouse({{ $warehouse->id }})">Edit</flux:button>
                                        <flux:button size="sm" wire:click="toggleWarehouse({{ $warehouse->id }})">{{ $warehouse->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</flux:button>
                                        <flux:button size="sm" variant="danger" wire:click="deleteWarehouse({{ $warehouse->id }})" wire:confirm="Hapus gudang ini? Gudang dengan penawaran tidak dapat dihapus.">Hapus</flux:button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($supplier->offers->isNotEmpty())
                    <div class="grid gap-3 md:grid-cols-2">
                        @foreach ($supplier->offers as $offer)
                            <div class="flex flex-col gap-3 rounded-lg bg-zinc-50 p-4 text-sm dark:bg-zinc-800" wire:key="offer-{{ $offer->id }}">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">{{ $offer->productFlat->name }}</p>
                                        <p class="mt-1 text-zinc-500">{{ $offer->supplier_sku }} · Rp {{ number_format((float) $offer->cost_price, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="flex flex-wrap justify-end gap-1">
                                        @if ((int) $offer->productFlat->active_supplier_offer_id === $offer->id)
                                            <flux:badge color="green" size="sm">Dipakai produk</flux:badge>
                                        @endif
                                        <flux:badge :color="$offer->is_active && $offer->is_available ? 'blue' : 'zinc'" size="sm">{{ $offer->is_active && $offer->is_available ? 'Aktif' : 'Nonaktif' }}</flux:badge>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if ($offer->is_active && $offer->is_available && $offer->productFlat->active_supplier_offer_id !== $offer->id)
                                        <flux:button size="sm" wire:click="activateOffer({{ $offer->id }})">Jadikan sumber</flux:button>
                                    @endif
                                    @if ((int) $offer->productFlat->active_supplier_offer_id === $offer->id)
                                        <flux:button size="sm" wire:click="deactivateOffer({{ $offer->id }})">Nonaktifkan</flux:button>
                                    @endif
                                    <flux:button size="sm" wire:click="editOffer({{ $offer->id }})">Edit</flux:button>
                                    <flux:button size="sm" variant="danger" wire:click="deleteOffer({{ $offer->id }})" wire:confirm="Hapus penawaran ini? Jika sedang dipakai, fulfillment akan dikembalikan ke stok sendiri.">Hapus</flux:button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <p class="p-8 text-center text-sm text-zinc-500">Belum ada supplier.</p>
        @endforelse
    </section>
</div>
