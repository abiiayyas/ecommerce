<div x-data="pageBuilder(@js($builderData))" class="flex flex-col h-screen overflow-hidden bg-[#f3f4f6]">
    <!-- Top Bar -->
    <header class="flex items-center justify-between px-4 py-3 bg-white border-b border-gray-200 shrink-0 z-20">
        <div class="flex items-center gap-3">
            <a href="{{ route('cms.landing-page') }}" class="flex items-center gap-1 text-gray-600 hover:text-gray-900 font-medium text-sm border px-3 py-1.5 rounded-md">
                <flux:icon.arrow-left class="w-4 h-4"/> Kembali
            </a>
            <div class="flex items-center gap-2">
                <span class="font-bold text-gray-800 text-lg">{{ $landingPage->slug }}</span>
                <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full flex items-center gap-1">
                    <div class="w-1.5 h-1.5 rounded-full bg-green-500"></div> Saved
                </span>
            </div>
        </div>
        
        <div class="flex items-center gap-2 bg-gray-100 p-1 rounded-lg">
            <button @click="device = 'desktop'" :class="{'bg-white shadow': device === 'desktop', 'text-gray-500': device !== 'desktop'}" class="flex items-center gap-2 px-4 py-1.5 rounded-md text-sm font-medium transition">
                <flux:icon.computer-desktop class="w-4 h-4"/> Desktop
            </button>
            <button @click="device = 'mobile'" :class="{'bg-white shadow': device === 'mobile', 'text-gray-500': device !== 'mobile'}" class="flex items-center gap-2 px-4 py-1.5 rounded-md text-sm font-medium transition">
                <flux:icon.device-phone-mobile class="w-4 h-4"/> Mobile
                <span x-show="device === 'mobile'" class="text-xs text-gray-400 font-normal ml-1">max-width: 430px</span>
            </button>
        </div>

        <div class="flex items-center gap-2">
            <button class="flex items-center gap-1.5 px-4 py-1.5 border rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                <flux:icon.eye class="w-4 h-4"/> Preview
            </button>
            <button @click="save" class="flex items-center gap-1.5 px-4 py-1.5 rounded-md text-sm font-medium text-white bg-[#0f4989] hover:bg-blue-800 transition">
                <flux:icon.document-check class="w-4 h-4"/> Save
            </button>
            <button class="flex items-center gap-1 px-2 py-1.5 border rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                <flux:icon.ellipsis-horizontal class="w-5 h-5"/> Lainnya
            </button>
        </div>
    </header>

    <div class="flex flex-1 overflow-hidden">
        <!-- Left Sidebar (Components) -->
        <aside class="w-80 bg-white border-r border-gray-200 flex flex-col shrink-0 z-10">
            <div class="flex border-b border-gray-200">
                <button class="flex-1 py-3 text-sm font-medium border-b-2 border-[#0f4989] text-[#0f4989] flex items-center justify-center gap-2">
                    <flux:icon.plus class="w-4 h-4"/> Components
                </button>
                <button class="flex-1 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 flex items-center justify-center gap-2">
                    <flux:icon.square-3-stack-3d class="w-4 h-4"/> Structure
                </button>
            </div>
            
            <div class="p-4 flex gap-2 overflow-x-auto hide-scrollbar border-b border-gray-100">
                <span class="bg-[#0f4989] text-white text-xs px-3 py-1 rounded cursor-pointer whitespace-nowrap">Semua</span>
                <span class="border border-gray-300 text-gray-600 text-xs px-3 py-1 rounded cursor-pointer whitespace-nowrap hover:bg-gray-50">Sering Digunakan</span>
                <span class="border border-gray-300 text-gray-600 text-xs px-3 py-1 rounded cursor-pointer whitespace-nowrap hover:bg-gray-50">Form Pemesanan Online</span>
                <span class="border border-gray-300 text-gray-600 text-xs px-3 py-1 rounded cursor-pointer whitespace-nowrap hover:bg-gray-50">Sales Page</span>
            </div>

            <div class="flex-1 overflow-y-auto p-4">
                <div class="grid grid-cols-2 gap-3">
                    <template x-for="comp in availableComponents" :key="comp.type">
                        <div @click="addBlock(comp.type)" class="border border-gray-200 rounded-lg p-4 flex flex-col items-center justify-center gap-2 cursor-pointer hover:border-[#0f4989] hover:bg-blue-50/50 transition group">
                            <div class="text-[#0f4989] group-hover:scale-110 transition-transform" x-html="comp.icon"></div>
                            <div class="text-center">
                                <p class="text-sm font-bold text-gray-800" x-text="comp.label"></p>
                                <p class="text-[10px] text-gray-400 mt-0.5" x-text="comp.desc"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </aside>

        <!-- Center Canvas -->
        <main class="flex-1 overflow-y-auto bg-[#f3f4f6] relative flex justify-center p-8">
            <div 
                class="bg-white shadow-sm transition-all duration-300 min-h-full flex flex-col relative"
                :class="device === 'mobile' ? 'w-[430px]' : (data.layout === 'boxed' ? 'w-[1000px]' : 'w-full')"
                :style="`border-radius: ${data.radius}px; padding: ${data.paddingY}px ${data.paddingX}px; font-family: ${data.font}`"
            >
                <div class="absolute top-8 left-0 right-0 flex justify-center pointer-events-none" x-show="data.blocks.length === 0">
                    <div class="text-center mt-20 pointer-events-auto">
                        <flux:icon.plus class="w-12 h-12 text-blue-200 mx-auto mb-4"/>
                        <h2 class="text-2xl font-light text-blue-400 mb-2">Start Building Your Landing Page</h2>
                        <p class="text-sm text-blue-300 mb-6">Drag components from the left sidebar to<br>start creating your page</p>
                        <div class="flex items-center justify-center gap-3">
                            <button @click="addBlock('text')" class="px-4 py-2 bg-[#0f4989] text-white rounded text-sm font-medium hover:bg-blue-800">Tambah Teks</button>
                            <button @click="addBlock('image')" class="px-4 py-2 border border-[#0f4989] text-[#0f4989] rounded text-sm font-medium hover:bg-blue-50">Tambah Gambar</button>
                        </div>
                    </div>
                </div>

                <div 
                    x-sortable
                    x-on:sort.stop="reorderBlocks($event.detail.oldIndex, $event.detail.newIndex)"
                    class="flex flex-col min-h-[200px] relative z-10"
                    :style="`gap: ${data.componentMargin}px;`"
                >
                    <template x-for="(block, index) in data.blocks" :key="block.id">
                        <div 
                            x-sortable-item="block.id"
                            class="group relative border border-transparent hover:border-blue-300 rounded p-2 transition cursor-move"
                            @click="activeBlockIndex = index"
                            :class="{'ring-2 ring-[#0f4989] bg-blue-50/20': activeBlockIndex === index}"
                        >
                            <!-- Block actions overlay -->
                            <div class="absolute -top-3 -right-3 hidden group-hover:flex items-center bg-white shadow border rounded-lg overflow-hidden z-20">
                                <button @click.stop="duplicateBlock(index)" class="p-1.5 text-gray-500 hover:text-[#0f4989] hover:bg-blue-50" title="Duplicate">
                                    <flux:icon.document-duplicate class="w-4 h-4"/>
                                </button>
                                <button @click.stop="removeBlock(index)" class="p-1.5 text-red-500 hover:bg-red-50" title="Delete">
                                    <flux:icon.trash class="w-4 h-4"/>
                                </button>
                            </div>

                            <!-- Placeholder Content Based on Type -->
                            <div class="pointer-events-none">
                                <div x-show="block.type === 'text'" class="text-gray-800" x-html="block.content || 'Blok teks sederhana'"></div>
                                <div x-show="block.type === 'image'" class="bg-gray-100 h-40 flex items-center justify-center text-gray-400 rounded border border-dashed border-gray-300">
                                    <flux:icon.photo class="w-8 h-8"/>
                                </div>
                                <div x-show="block.type === 'button'" class="flex justify-center">
                                    <button class="px-6 py-3 bg-[#ca4a2c] text-white rounded font-bold" x-text="block.content || 'Tombol aksi'"></button>
                                </div>
                                <div x-show="block.type === 'divider'" class="border-t border-gray-300 my-4"></div>
                                <div x-show="['list', 'testimonial', 'faq', 'slider', 'youtube', 'gif', 'countdown', 'html', 'form'].includes(block.type)" class="bg-gray-50 border border-dashed border-gray-300 p-8 text-center text-gray-500 rounded">
                                    <span x-text="block.type.toUpperCase() + ' Placeholder'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </main>

        <!-- Right Sidebar (Settings) -->
        <aside class="w-72 bg-white border-l border-gray-200 flex flex-col shrink-0 z-10 overflow-y-auto">
            <div class="flex border-b border-gray-200">
                <button @click="settingsTab = 'design'" :class="{'border-b-2 border-[#0f4989] text-[#0f4989]': settingsTab === 'design', 'text-gray-500 hover:text-gray-700': settingsTab !== 'design'}" class="flex-1 py-3 text-sm font-medium flex items-center justify-center gap-2">
                    <flux:icon.paint-brush class="w-4 h-4"/> Desain
                </button>
                <button @click="settingsTab = 'settings'" :class="{'border-b-2 border-[#0f4989] text-[#0f4989]': settingsTab === 'settings', 'text-gray-500 hover:text-gray-700': settingsTab !== 'settings'}" class="flex-1 py-3 text-sm font-medium flex items-center justify-center gap-2">
                    <flux:icon.cog-8-tooth class="w-4 h-4"/> Pengaturan
                </button>
            </div>
            
            <div class="p-5 space-y-6" x-show="settingsTab === 'design'">
                <!-- Global Settings -->
                <div x-show="activeBlockIndex === null" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Layout Landing Page</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="data.layout" value="boxed" class="text-[#0f4989] focus:ring-[#0f4989]">
                                <span class="text-sm">Boxed</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="data.layout" value="fullwidth" class="text-[#0f4989] focus:ring-[#0f4989]">
                                <span class="text-sm">Fullwidth</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Container Radius (px)</label>
                        <input type="number" x-model.number="data.radius" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-[#0f4989] focus:ring focus:ring-[#0f4989] focus:ring-opacity-50">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Pengaturan Font</label>
                        <select x-model="data.font" class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-[#0f4989] focus:ring focus:ring-[#0f4989] focus:ring-opacity-50 mb-2">
                            <option value="Inter">Inter</option>
                            <option value="Manrope">Manrope</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Poppins">Poppins</option>
                        </select>
                        <div class="text-xs text-gray-500 mt-1">Google Font (search & enter)</div>
                        <input type="text" placeholder="Cari font, contoh: Manrope" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm focus:border-[#0f4989] focus:ring focus:ring-[#0f4989] focus:ring-opacity-50">
                        <p class="text-[10px] text-gray-400 mt-1">Tekan Enter atau klik suggestion untuk memuat & menerapkan.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Padding</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Atas/Bawah</label>
                                <input type="number" x-model.number="data.paddingY" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Kiri/Kanan</label>
                                <input type="number" x-model.number="data.paddingX" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Margin</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Atas/Bawah</label>
                                <input type="number" x-model.number="data.marginY" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Kiri/Kanan</label>
                                <input type="number" x-model.number="data.marginX" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Margin Antar Komponen</label>
                        <input type="number" x-model.number="data.componentMargin" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                </div>

                <!-- Block Specific Settings -->
                <div x-show="activeBlockIndex !== null" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-gray-800">Block Settings</h3>
                        <button @click="activeBlockIndex = null" class="text-xs text-[#0f4989] hover:underline">Back to Page</button>
                    </div>
                    
                    <div class="bg-blue-50 p-3 rounded text-sm text-blue-800">
                        Editing: <span class="font-bold uppercase" x-text="data.blocks[activeBlockIndex]?.type"></span>
                    </div>

                    <div x-show="data.blocks[activeBlockIndex]?.type === 'text'">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Content</label>
                        <textarea x-model="data.blocks[activeBlockIndex].content" rows="4" class="w-full border-gray-300 rounded-md shadow-sm text-sm"></textarea>
                    </div>

                    <div x-show="data.blocks[activeBlockIndex]?.type === 'button'">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Button Text</label>
                        <input type="text" x-model="data.blocks[activeBlockIndex].content" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    
                    <!-- Other generic settings placeholders -->
                    <div x-show="!['text', 'button'].includes(data.blocks[activeBlockIndex]?.type)">
                        <p class="text-sm text-gray-500">Settings for this block will be available soon.</p>
                    </div>
                </div>
            </div>
            
            <div class="p-5" x-show="settingsTab === 'settings'">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Pilih Produk (Data & Harga)</label>
                        <select wire:model="productId" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                            @foreach($this->products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Headline Internal</label>
                        <input type="text" wire:model="headline" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Slug URL (/p/...)</label>
                        <input type="text" wire:model="slug" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Meta Pixel ID</label>
                        <input type="text" wire:model="metaPixelId" class="w-full border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <div class="space-y-3 rounded-lg bg-zinc-50 p-4 border border-zinc-200 mt-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="onlinePaymentEnabled" class="rounded text-[#0f4989] focus:ring-[#0f4989]">
                            <span class="text-sm font-medium">Pembayaran Online Aktif</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="codEnabled" class="rounded text-[#0f4989] focus:ring-[#0f4989]">
                            <span class="text-sm font-medium">Bayar di Tempat (COD) Aktif</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer pt-2 border-t border-zinc-200">
                            <input type="checkbox" wire:model="isActive" class="rounded text-[#0f4989] focus:ring-[#0f4989]">
                            <span class="text-sm font-bold text-green-700">Publikasikan Landing Page</span>
                        </label>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- Help Bubble -->
    <div class="fixed bottom-6 right-6 z-50">
        <button class="w-12 h-12 bg-[#0f4989] text-white rounded-full flex items-center justify-center shadow-lg hover:bg-blue-800 transition">
            <flux:icon.chat-bubble-bottom-center-text class="w-6 h-6"/>
        </button>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('pageBuilder', (initialData) => ({
        data: initialData,
        device: 'desktop',
        settingsTab: 'design',
        activeBlockIndex: null,
        
        availableComponents: [
            { type: 'text', label: 'Teks', desc: 'Blok teks sederhana', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7"/></svg>' },
            { type: 'image', label: 'Gambar', desc: 'Gambar sederhana', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' },
            { type: 'form', label: 'Form Pemesanan', desc: 'Form pemesanan', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>' },
            { type: 'list', label: 'List Item', desc: 'Daftar / List item', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>' },
            { type: 'testimonial', label: 'Testimoni', desc: 'Blok testimoni', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>' },
            { type: 'faq', label: 'FAQ', desc: 'Pertanyaan umum', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            { type: 'slider', label: 'Gambar Slider', desc: 'Slider gambar', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>' },
            { type: 'button', label: 'Button', desc: 'Tombol aksi', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/></svg>' },
            { type: 'youtube', label: 'YouTube', desc: 'Video YouTube', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            { type: 'gif', label: 'GIF Animation', desc: 'Animasi GIF', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>' },
            { type: 'countdown', label: 'Countdown', desc: 'Penghitung mundur', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
            { type: 'divider', label: 'Divider', desc: 'Garis pemisah', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 12H4"/></svg>' },
            { type: 'html', label: 'HTML', desc: 'Custom HTML', icon: '<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>' }
        ],

        addBlock(type) {
            this.data.blocks.push({
                id: 'block_' + Math.random().toString(36).substr(2, 9),
                type: type,
                content: ''
            });
            this.activeBlockIndex = this.data.blocks.length - 1;
        },

        removeBlock(index) {
            if (this.activeBlockIndex === index) {
                this.activeBlockIndex = null;
            } else if (this.activeBlockIndex > index) {
                this.activeBlockIndex--;
            }
            this.data.blocks.splice(index, 1);
        },

        duplicateBlock(index) {
            const block = JSON.parse(JSON.stringify(this.data.blocks[index]));
            block.id = 'block_' + Math.random().toString(36).substr(2, 9);
            this.data.blocks.splice(index + 1, 0, block);
            this.activeBlockIndex = index + 1;
        },

        reorderBlocks(oldIndex, newIndex) {
            const block = this.data.blocks.splice(oldIndex, 1)[0];
            this.data.blocks.splice(newIndex, 0, block);
            
            // Adjust active index
            if (this.activeBlockIndex === oldIndex) {
                this.activeBlockIndex = newIndex;
            } else if (this.activeBlockIndex !== null) {
                if (oldIndex < this.activeBlockIndex && newIndex >= this.activeBlockIndex) {
                    this.activeBlockIndex--;
                } else if (oldIndex > this.activeBlockIndex && newIndex <= this.activeBlockIndex) {
                    this.activeBlockIndex++;
                }
            }
        },

        save() {
            this.$wire.save(this.data);
        }
    }));
});
</script>
