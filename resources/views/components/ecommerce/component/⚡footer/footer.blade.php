<div>
    <footer class="bg-white border-t mt-16 pb-16 md:pb-8">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
            <!-- Kolom 1: Profil & Kepercayaan -->
            <div class="space-y-4">
                <a href="{{ route('home') }}" class="inline-block" wire:navigate>
                    <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name', 'Diginiaga') }}" class="h-8 md:h-9 w-auto object-contain">
                </a>
                <p class="text-sm text-gray-500 leading-relaxed max-w-sm">
                    Platform belanja online terpercaya dengan kurasi produk berkualitas dan pengiriman langsung dari gudang ke seluruh Indonesia.
                </p>
                <div class="pt-1 flex flex-wrap gap-2 text-xs text-gray-600">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 border border-gray-200 px-2.5 py-1 font-medium">
                        <flux:icon.shield-check class="size-3.5 text-[#0c37b0]" /> Belanja Aman
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 border border-gray-200 px-2.5 py-1 font-medium">
                        <flux:icon.truck class="size-3.5 text-[#0c37b0]" /> Resi Terlacak
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-50 border border-gray-200 px-2.5 py-1 font-medium">
                        <flux:icon.arrow-path class="size-3.5 text-[#0c37b0]" /> Jaminan Kualitas
                    </span>
                </div>
            </div>

            <!-- Kolom 2: Navigasi Belanja -->
            <div>
                <flux:heading size="lg" class="mb-4">
                    Belanja
                </flux:heading>
                <ul class="space-y-3 text-sm text-gray-500">
                    <li>
                        <a href="{{ route('explore.index') }}" class="hover:text-[#0c37b0] transition font-medium" wire:navigate>
                            Jelajahi Produk
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('orders.check') }}" class="hover:text-[#0c37b0] transition font-medium" wire:navigate>
                            Cek Status Pesanan
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('cart') }}" class="hover:text-[#0c37b0] transition font-medium" wire:navigate>
                            Keranjang Belanja
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('orders.index') }}" class="hover:text-[#0c37b0] transition font-medium" wire:navigate>
                            Riwayat Transaksi
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Kolom 3: Bantuan & Layanan -->
            <div>
                <flux:heading size="lg" class="mb-4">
                    Bantuan & Layanan
                </flux:heading>
                <ul class="space-y-3.5 text-sm text-gray-500">
                    <li class="flex items-start gap-3">
                        <flux:icon.clock class="size-4 text-[#0c37b0] shrink-0 mt-0.5" />
                        <div>
                            <span class="font-medium text-gray-700 block">Jam Operasional</span>
                            <span class="text-xs text-gray-500">Senin – Sabtu, 08:00 – 21:00 WIB</span>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <flux:icon.credit-card class="size-4 text-[#0c37b0] shrink-0 mt-0.5" />
                        <div>
                            <span class="font-medium text-gray-700 block">Metode Pembayaran</span>
                            <span class="text-xs text-gray-500">QRIS, Transfer Bank (VA) & COD</span>
                        </div>
                    </li>
                    <li class="flex items-start gap-3">
                        <flux:icon.cube class="size-4 text-[#0c37b0] shrink-0 mt-0.5" />
                        <div>
                            <span class="font-medium text-gray-700 block">Pengiriman</span>
                            <span class="text-xs text-gray-500">Ekspedisi resmi terintegrasi & resi otomatis</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-6 pt-8 border-t flex flex-col md:flex-row items-center justify-between gap-4">
            <flux:text>© {{ date('Y') }} {{ config('app.name', 'Diginiaga') }}. All rights reserved.</flux:text>
            <div class="flex gap-4 text-gray-400">
                <flux:icon.chat-bubble-oval-left class="w-5 h-5 hover:text-[#0c37b0] cursor-pointer" />
                <flux:icon.camera class="w-5 h-5 hover:text-[#0c37b0] cursor-pointer" />
                <flux:icon.globe-alt class="w-5 h-5 hover:text-[#0c37b0] cursor-pointer" />
            </div>
        </div>
    </footer>
</div>
