<div>
    <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">Antrean Dispatch</flux:heading>
                <flux:text>Konfirmasi pengiriman pesanan dropship ke supplier (opsional jika tidak otomatis).</flux:text>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            @forelse($dispatches as $dispatch)
                <div class="flex flex-col gap-4 border-b border-zinc-200 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between dark:border-zinc-700" wire:key="dispatch-{{ $dispatch->id }}">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="truncate font-semibold">
                                Order #{{ $dispatch->orderShop->order->id ?? 'Unknown' }}
                            </p>
                            <flux:badge color="amber" size="sm">Menunggu Konfirmasi</flux:badge>
                        </div>
                        <p class="mt-1 text-sm text-zinc-500">
                            Supplier: {{ $dispatch->supplier->name ?? 'Unknown' }}
                        </p>
                        <p class="mt-1 text-sm text-zinc-500">
                            Dibuat: {{ $dispatch->created_at->format('d M Y H:i') }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <flux:button 
                            size="sm" 
                            variant="primary" 
                            wire:click="confirm({{ $dispatch->id }})" 
                            wire:confirm="Konfirmasi dispatch ini ke supplier? Order akan diteruskan dan diproses pengiriman."
                        >
                            Konfirmasi
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-sm text-zinc-500">Tidak ada antrean dispatch.</div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $dispatches->links() }}
        </div>
    </section>
</div>
