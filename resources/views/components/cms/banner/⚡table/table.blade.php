<div>
    <div class="mb-4 flex items-center justify-between gap-4">
        <div>
            <flux:heading size="lg">Banner beranda</flux:heading>
            <flux:text class="mt-1">Banner aktif tampil berdasarkan urutan terkecil.</flux:text>
        </div>

        <flux:button
            variant="primary"
            icon="plus"
            @click="
                $flux.modal('banner-form').show();
                $wire.dispatch('set-action');
            "
        >
            Tambah Banner
        </flux:button>
    </div>

    <div class="mb-4 mt-5 flex items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <span class="text-sm text-zinc-600 dark:text-zinc-300">Tampilkan</span>
            <flux:select size="sm" wire:model.live.debounce="paginate" aria-label="Jumlah banner per halaman">
                <option value="10">10 per halaman</option>
                <option value="25">25 per halaman</option>
                <option value="50">50 per halaman</option>
                <option value="100">100 per halaman</option>
            </flux:select>
        </div>

        <flux:input
            size="sm"
            icon="magnifying-glass"
            type="search"
            placeholder="Cari judul banner"
            wire:model.live.debounce="search"
            class="max-w-xs"
            aria-label="Cari banner"
        />
    </div>

    <flux:table :paginate="$data" class="min-w-full">
        <flux:table.columns>
            <flux:table.column>Aksi</flux:table.column>
            <flux:table.column>Gambar</flux:table.column>
            <x-loop-th :$searchBy :$paginationOrder :$paginationOrderBy />
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($data as $banner)
                <flux:table.row wire:key="banner-{{ $banner->id }}">
                    <flux:table.cell>
                        <flux:dropdown>
                            <flux:button icon:trailing="chevron-down" size="sm">Opsi</flux:button>
                            <flux:menu>
                                <flux:menu.item
                                    icon="pencil"
                                    @click="
                                        $flux.modal('banner-form').show();
                                        $wire.dispatch('set-action', { id: '{{ $banner->id }}' });
                                    "
                                >
                                    Ubah
                                </flux:menu.item>
                                <flux:menu.item
                                    variant="danger"
                                    icon="trash"
                                    @click="$wire.dispatch('confirm', { function: 'delete', id: '{{ $banner->id }}' })"
                                >
                                    Hapus
                                </flux:menu.item>
                            </flux:menu>
                        </flux:dropdown>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if ($banner->getFirstMediaUrl('image') !== '')
                            <img src="{{ $banner->getFirstMediaUrl('image') }}" alt="" class="h-16 w-28 rounded object-cover" />
                        @else
                            <div class="flex h-16 w-28 items-center justify-center rounded bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-300">
                                <flux:icon.photo class="size-5" />
                            </div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="font-medium">{{ $banner->title }}</div>
                        @if ($banner->description)
                            <div class="mt-1 line-clamp-2 text-sm text-zinc-500">{{ $banner->description }}</div>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>{{ $banner->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$banner->is_active ? 'green' : 'zinc'" size="sm">
                            {{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}
                        </flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="999" align="center" variant="strong">
                        Belum ada banner.
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <livewire:cms.banner.create-update lazy />
</div>