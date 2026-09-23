<div x-data="pageBuilder(@js($builderData))" class="flex min-h-screen flex-col overflow-hidden bg-zinc-100">
    <header class="z-20 flex shrink-0 flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-white px-4 py-3">
        <div class="flex min-w-0 items-center gap-3">
            <a href="{{ route('cms.landing-page') }}" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-zinc-200 px-3 text-sm font-medium text-zinc-600 transition hover:bg-zinc-50 hover:text-zinc-900"><flux:icon.arrow-left class="size-4" /> Kembali</a>
            <div class="flex min-w-0 items-center gap-2"><span class="truncate font-bold text-zinc-800">{{ $landingPage->slug }}</span><span class="hidden items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600 sm:inline-flex"><span class="size-1.5 rounded-full bg-emerald-500"></span><span x-text="saveState"></span></span></div>
        </div>
        <div class="order-3 flex w-full justify-center gap-1 rounded-lg bg-zinc-100 p-1 sm:order-none sm:w-auto">
            <button type="button" @click="device = 'desktop'" :class="device === 'desktop' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500'" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-sm font-medium transition"><flux:icon.computer-desktop class="size-4" /> Desktop</button>
            <button type="button" @click="device = 'mobile'" :class="device === 'mobile' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500'" class="inline-flex min-h-9 items-center gap-2 rounded-md px-3 text-sm font-medium transition"><flux:icon.device-phone-mobile class="size-4" /> Mobile</button>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('landing.show', ['slug' => $landingPage->slug]) }}" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-2 rounded-md border border-zinc-200 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50"><flux:icon.eye class="size-4" /> Preview</a>
            <button type="button" @click="save" :disabled="isSaving" class="inline-flex min-h-10 items-center gap-2 rounded-md bg-[#0c37b0] px-4 text-sm font-medium text-white transition hover:bg-[#092b8d] disabled:cursor-wait disabled:opacity-60"><flux:icon.document-check class="size-4" /><span x-text="isSaving ? 'Menyimpan…' : 'Simpan'"></span></button>
        </div>
    </header>

    @if ($errors->any())
        <div class="z-10 border-b border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><ul class="mx-auto grid max-w-7xl gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="flex min-h-0 flex-1 overflow-auto">
        <aside class="flex w-80 shrink-0 flex-col border-r border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-5 py-4"><flux:heading size="lg">Komponen</flux:heading><flux:text class="mt-1">Klik komponen untuk menambahkannya ke kanvas.</flux:text></div>
            <div class="flex-1 overflow-y-auto p-4"><div class="grid grid-cols-2 gap-3"><template x-for="component in availableComponents" :key="component.type"><button type="button" @click="addBlock(component.type)" class="group flex min-h-28 flex-col items-center justify-center gap-2 rounded-lg border border-zinc-200 p-3 text-center transition hover:border-[#0c37b0] hover:bg-blue-50/50"><span class="text-2xl text-zinc-700" x-text="component.icon"></span><span class="text-sm font-semibold text-zinc-800" x-text="component.label"></span><span class="text-[10px] text-zinc-400" x-text="component.desc"></span></button></template></div></div>
        </aside>

        <main class="min-w-[min(100vw,640px)] flex-1 overflow-y-auto bg-zinc-100 p-4 sm:p-8">
            <div class="mx-auto min-h-full bg-white shadow-sm transition-all duration-300" :class="device === 'mobile' ? 'w-[430px] max-w-full' : (data.layout === 'boxed' ? 'w-[1000px] max-w-full' : 'w-full')" :style="`border-radius: ${data.radius}px; padding: ${data.paddingY}px ${data.paddingX}px; font-family: ${data.font}; margin: ${data.marginY}px ${data.marginX}px`">
                <div x-show="data.blocks.length === 0" class="flex min-h-[320px] items-center justify-center text-center"><div><flux:icon.plus class="mx-auto mb-4 size-12 text-zinc-300" /><h2 class="text-2xl font-light text-zinc-500">Mulai susun landing page</h2><p class="mb-6 mt-2 text-sm text-zinc-400">Tambahkan blok dari panel komponen atau gunakan contoh cepat.</p><div class="flex justify-center gap-3"><button type="button" @click="addBlock('text')" class="rounded bg-[#0c37b0] px-4 py-2 text-sm font-medium text-white hover:bg-[#092b8d]">Tambah teks</button><button type="button" @click="addBlock('button')" class="rounded border border-[#0c37b0] px-4 py-2 text-sm font-medium text-[#0c37b0] hover:bg-blue-50">Tambah tombol</button></div></div></div>
                <div x-sortable x-on:sort.stop="reorderBlocks($event.detail.oldIndex, $event.detail.newIndex)" class="relative z-10 flex min-h-[200px] flex-col" :style="`gap: ${data.componentMargin}px;`"><template x-for="(block, index) in data.blocks" :key="block.id"><div x-sortable-item="block.id" class="group relative rounded border border-transparent p-2 transition hover:border-zinc-300" @click="activeBlockIndex = index" :class="activeBlockIndex === index ? 'bg-zinc-50 ring-2 ring-[#0c37b0]' : ''"><div class="absolute -right-3 -top-3 z-20 hidden items-center overflow-hidden rounded-lg border bg-white shadow group-hover:flex"><button type="button" @click.stop="duplicateBlock(index)" class="p-1.5 text-zinc-500 hover:bg-zinc-50 hover:text-[#0c37b0]" title="Duplikat"><flux:icon.document-duplicate class="size-4" /></button><button type="button" @click.stop="removeBlock(index)" class="p-1.5 text-red-500 hover:bg-red-50" title="Hapus"><flux:icon.trash class="size-4" /></button></div><div class="pointer-events-none"><div x-show="block.type === 'text'" class="whitespace-pre-line text-zinc-800" x-text="block.content || 'Blok teks sederhana'"></div><div x-show="block.type === 'image'" class="flex h-40 items-center justify-center rounded border border-dashed border-zinc-300 bg-zinc-100 text-zinc-400"><flux:icon.photo class="size-8" /></div><div x-show="block.type === 'button'" class="flex justify-center"><span class="rounded bg-[#0c37b0] px-6 py-3 font-bold text-white" x-text="block.content || 'Tombol aksi'"></span></div><div x-show="block.type === 'divider'" class="my-4 border-t border-zinc-300"></div><div x-show="!['text', 'image', 'button', 'divider'].includes(block.type)" class="rounded border border-dashed border-zinc-300 bg-zinc-50 p-8 text-center text-sm text-zinc-500" x-text="block.type.toUpperCase() + ' — isi di panel pengaturan'"></div></div></div></template></div>
            </div>
        </main>

        <aside class="flex w-80 shrink-0 flex-col overflow-y-auto border-l border-zinc-200 bg-white">
            <div class="border-b border-zinc-200 px-5 py-4"><flux:heading size="lg">Pengaturan</flux:heading><flux:text class="mt-1" x-text="activeBlockIndex === null ? 'Atur halaman atau pilih blok.' : 'Edit isi blok terpilih.'"></flux:text></div>
            <div x-show="activeBlockIndex === null" class="space-y-6 p-5"><div class="space-y-3"><label class="block text-sm font-semibold text-zinc-700">Layout</label><div class="flex gap-4 text-sm"><label class="flex items-center gap-2"><input type="radio" x-model="data.layout" value="boxed"> Boxed</label><label class="flex items-center gap-2"><input type="radio" x-model="data.layout" value="fullwidth"> Fullwidth</label></div></div><div class="grid grid-cols-2 gap-3"><label class="grid gap-1 text-xs text-zinc-500">Radius<input type="number" min="0" max="48" x-model.number="data.radius" class="min-h-10 rounded-md border-zinc-300 text-sm"></label><label class="grid gap-1 text-xs text-zinc-500">Jarak blok<input type="number" min="0" max="96" x-model.number="data.componentMargin" class="min-h-10 rounded-md border-zinc-300 text-sm"></label><label class="grid gap-1 text-xs text-zinc-500">Padding vertikal<input type="number" min="0" max="160" x-model.number="data.paddingY" class="min-h-10 rounded-md border-zinc-300 text-sm"></label><label class="grid gap-1 text-xs text-zinc-500">Padding horizontal<input type="number" min="0" max="160" x-model.number="data.paddingX" class="min-h-10 rounded-md border-zinc-300 text-sm"></label></div><label class="grid gap-1 text-sm font-semibold text-zinc-700">Font<select x-model="data.font" class="min-h-10 rounded-md border-zinc-300 text-sm font-normal"><option>Inter</option><option>Manrope</option><option>Roboto</option><option>Poppins</option></select></label></div>
            <div x-show="activeBlockIndex !== null" class="space-y-5 p-5"><button type="button" @click="activeBlockIndex = null" class="text-sm font-medium text-zinc-600 hover:text-zinc-900">← Kembali ke pengaturan halaman</button><div class="rounded-lg bg-zinc-100 p-3 text-sm text-zinc-700">Blok <span class="font-semibold uppercase" x-text="data.blocks[activeBlockIndex]?.type"></span></div><label class="grid gap-2 text-sm font-semibold text-zinc-700">Isi blok<textarea x-model="data.blocks[activeBlockIndex].content" rows="7" maxlength="12000" class="rounded-md border-zinc-300 text-sm font-normal" placeholder="Tulis konten blok di sini..."></textarea></label><p class="text-xs leading-5 text-zinc-500">Konten ditampilkan sebagai teks aman di halaman publik. HTML mentah tidak diproses.</p></div>
            <div class="space-y-5 border-t border-zinc-200 p-5"><flux:heading size="sm">Publikasi</flux:heading><label class="grid gap-1 text-sm font-semibold text-zinc-700">Produk<select wire:model="productId" class="min-h-10 rounded-md border-zinc-300 text-sm font-normal">@foreach ($this->products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label><label class="grid gap-1 text-sm font-semibold text-zinc-700">Headline<input type="text" wire:model="headline" class="min-h-10 rounded-md border-zinc-300 text-sm font-normal"></label><label class="grid gap-1 text-sm font-semibold text-zinc-700">Slug URL<input type="text" wire:model="slug" class="min-h-10 rounded-md border-zinc-300 text-sm font-normal"></label><label class="grid gap-1 text-sm font-semibold text-zinc-700">Meta Pixel ID<input type="text" wire:model="metaPixelId" inputmode="numeric" class="min-h-10 rounded-md border-zinc-300 text-sm font-normal"></label><div class="grid gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-4 text-sm"><label class="flex items-center gap-3"><input type="checkbox" wire:model="onlinePaymentEnabled" class="size-4 rounded border-zinc-300 text-[#0c37b0] focus:ring-[#0c37b0]"> Pembayaran online</label><label class="flex items-center gap-3"><input type="checkbox" wire:model="codEnabled" class="size-4 rounded border-zinc-300 text-[#0c37b0] focus:ring-[#0c37b0]"> Bayar di tempat (COD)</label><label class="flex items-center gap-3 border-t border-zinc-200 pt-3 font-semibold text-[#0c37b0]"><input type="checkbox" wire:model="isActive" class="size-4 rounded border-zinc-300 text-[#0c37b0] focus:ring-[#0c37b0]"> Publikasikan landing page</label></div></div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pageBuilder', (initialData) => ({
        data: initialData,
        device: 'desktop',
        activeBlockIndex: null,
        isSaving: false,
        saveState: 'Belum disimpan',
        availableComponents: [
            { type: 'text', label: 'Teks', desc: 'Konten teks', icon: 'T' },
            { type: 'image', label: 'Gambar', desc: 'Placeholder gambar', icon: '▧' },
            { type: 'form', label: 'Form', desc: 'Form pemesanan', icon: '▤' },
            { type: 'list', label: 'List', desc: 'Daftar manfaat', icon: '☷' },
            { type: 'testimonial', label: 'Testimoni', desc: 'Kutipan pelanggan', icon: '❝' },
            { type: 'faq', label: 'FAQ', desc: 'Pertanyaan umum', icon: '?' },
            { type: 'button', label: 'Tombol', desc: 'Aksi ke checkout', icon: '↗' },
            { type: 'youtube', label: 'Video', desc: 'Tautan video', icon: '▶' },
            { type: 'countdown', label: 'Countdown', desc: 'Penawaran terbatas', icon: '◷' },
            { type: 'divider', label: 'Divider', desc: 'Garis pemisah', icon: '—' },
        ],
        addBlock(type) { this.data.blocks.push({ id: 'block_' + Math.random().toString(36).slice(2, 11), type, content: '' }); this.activeBlockIndex = this.data.blocks.length - 1; },
        removeBlock(index) { this.data.blocks.splice(index, 1); this.activeBlockIndex = this.data.blocks.length ? Math.min(index, this.data.blocks.length - 1) : null; },
        duplicateBlock(index) { const block = JSON.parse(JSON.stringify(this.data.blocks[index])); block.id = 'block_' + Math.random().toString(36).slice(2, 11); this.data.blocks.splice(index + 1, 0, block); this.activeBlockIndex = index + 1; },
        reorderBlocks(oldIndex, newIndex) { if (oldIndex === newIndex) return; const block = this.data.blocks.splice(oldIndex, 1)[0]; this.data.blocks.splice(newIndex, 0, block); if (this.activeBlockIndex === oldIndex) this.activeBlockIndex = newIndex; },
        async save() { this.isSaving = true; this.saveState = 'Menyimpan…'; try { await this.$wire.save(this.data); this.saveState = 'Tersimpan'; } catch (error) { this.saveState = 'Gagal menyimpan'; throw error; } finally { this.isSaving = false; } },
    }));
});
</script>
