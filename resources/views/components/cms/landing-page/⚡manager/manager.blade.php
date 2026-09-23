<div class="mx-auto max-w-6xl">
    <section class="space-y-6">
        <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <flux:heading size="xl">Landing page iklan</flux:heading>
                <flux:text class="mt-2 max-w-2xl">Buat halaman penjualan yang fokus pada kampanye, checkout, dan pelacakan tanpa mengubah storefront utama.</flux:text>
            </div>
            <flux:button wire:click="create" icon="plus" variant="primary">Landing page baru</flux:button>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Total halaman</p>
                <p class="mt-2 text-2xl font-semibold">{{ $this->landingPages->count() }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Sedang tayang</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ $this->landingPages->where('is_active', true)->count() }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <p class="text-sm text-zinc-500">Pesanan dari landing page</p>
                <p class="mt-2 text-2xl font-semibold">{{ $this->landingPages->sum('orders_count') }}</p>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            @forelse ($this->landingPages as $page)
                <article class="flex flex-col gap-5 border-b border-zinc-200 p-6 last:border-b-0 dark:border-zinc-700" wire:key="landing-{{ $page->id }}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="truncate text-lg font-semibold">{{ $page->headline }}</h2>
                                <flux:badge :color="$page->is_active ? 'green' : 'zinc'" size="sm">{{ $page->is_active ? 'Tayang' : 'Draft' }}</flux:badge>
                            </div>
                            <p class="mt-1 text-sm text-zinc-500">{{ $page->product->name ?? 'Produk dihapus' }} · {{ $page->orders_count }} pesanan · /p/{{ $page->slug }}</p>
                            @if ($page->is_active)
                                <a class="mt-2 inline-flex text-sm font-medium text-zinc-700 underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-900" href="{{ route('landing.show', ['slug' => $page->slug]) }}" target="_blank" rel="noopener">Buka halaman publik ↗</a>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <flux:button size="sm" variant="primary" href="{{ route('cms.landing-page.builder', ['id' => $page->id]) }}">Buka builder</flux:button>
                            <flux:button size="sm" wire:click="toggleActive({{ $page->id }})">{{ $page->is_active ? 'Nonaktifkan' : 'Publikasikan' }}</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $page->id }})" wire:confirm="Hapus landing page ini? Halaman dengan pesanan hanya akan dinonaktifkan.">Hapus</flux:button>
                        </div>
                    </div>
                    <div class="grid gap-3 text-sm text-zinc-600 sm:grid-cols-3">
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800"><span class="block text-xs uppercase tracking-wide text-zinc-400">Slug</span><span class="mt-1 block truncate font-medium">{{ $page->slug }}</span></div>
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800"><span class="block text-xs uppercase tracking-wide text-zinc-400">Status checkout</span><span class="mt-1 block font-medium">{{ $page->online_payment_enabled ? 'Online' : '' }}{{ $page->online_payment_enabled && $page->cod_enabled ? ' + ' : '' }}{{ $page->cod_enabled ? 'COD' : '' }}</span></div>
                        <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-800"><span class="block text-xs uppercase tracking-wide text-zinc-400">Dibuat</span><span class="mt-1 block font-medium">{{ $page->created_at->format('d M Y') }}</span></div>
                    </div>
                </article>
            @empty
                <div class="p-12 text-center">
                    <flux:heading size="lg">Belum ada landing page</flux:heading>
                    <flux:text class="mx-auto mt-2 max-w-md">Mulai dari produk aktif, lalu susun halaman kampanye melalui builder visual.</flux:text>
                    <flux:button class="mt-5" wire:click="create" variant="primary">Buat landing page pertama</flux:button>
                </div>
            @endforelse
        </div>
    </section>
</div>
