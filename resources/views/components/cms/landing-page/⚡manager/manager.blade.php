<div class="max-w-4xl mx-auto">
    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">Landing pages</flux:heading>
                <flux:text>Halaman cepat untuk traffic iklan tanpa mengubah storefront utama.</flux:text>
            </div>
            <flux:button wire:click="create" icon="plus" variant="primary">Baru</flux:button>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            @forelse($this->landingPages as $page)
                <div class="flex flex-col gap-4 border-b border-zinc-200 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700" wire:key="landing-{{ $page->id }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate font-semibold">{{ $page->headline }}</p>
                            <flux:badge :color="$page->is_active ? 'green' : 'zinc'" size="sm">{{ $page->is_active ? 'Aktif' : 'Draft' }}</flux:badge>
                        </div>
                        <p class="mt-1 text-sm text-zinc-500">{{ $page->product->name ?? 'Produk dihapus' }} · {{ $page->orders_count }} pesanan</p>
                        <a class="mt-1 inline-block text-sm text-blue-600 hover:underline" href="{{ route('landing.show', ['slug' => $page->slug]) }}" target="_blank">/p/{{ $page->slug }}</a>
                    </div>
                    <div class="flex gap-2">
                        <flux:button size="sm" variant="primary" href="{{ route('cms.landing-page.builder', ['id' => $page->id]) }}">Builder Visual</flux:button>
                        <flux:button size="sm" variant="danger" wire:click="delete({{ $page->id }})" wire:confirm="Hapus landing page ini?">Hapus</flux:button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-zinc-500">Belum ada landing page. Klik "Baru" untuk membuat visual builder.</div>
            @endforelse
        </div>
    </section>
</div>
