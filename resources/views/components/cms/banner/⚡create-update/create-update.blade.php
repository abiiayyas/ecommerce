<div>
    <flux:modal name="banner-form" class="max-w-2xl md:min-w-2xl" flyout>
        <form class="space-y-6" wire:submit="submit">
            <div>
                <flux:heading size="lg">{{ $isUpdate ? 'Ubah' : 'Tambah' }} Banner</flux:heading>
                <flux:text class="mt-2">Gambar wajib saat membuat banner baru. Tautan dapat berupa path internal atau URL HTTPS.</flux:text>
            </div>

            <flux:field>
                <flux:label badge="Required">Gambar</flux:label>
                <flux:text>Gunakan JPG, PNG, atau WEBP hingga 5 MB. Rasio lebar direkomendasikan agar banner tidak terpotong.</flux:text>
                <x-file-preview :file="$image" :form_file="$oldImage" />
                <x-file-upload model="image" accept="image/jpeg, image/png, image/webp, .jpg, .jpeg, .png, .webp" />
                <flux:error name="image" />
            </flux:field>

            <flux:field>
                <flux:label badge="Required">Judul</flux:label>
                <flux:input wire:model="title" />
                <flux:error name="title" />
            </flux:field>

            <flux:field>
                <flux:label>Deskripsi</flux:label>
                <flux:textarea wire:model="description" rows="3" />
                <flux:error name="description" />
            </flux:field>

            <div class="grid gap-4 md:grid-cols-2">
                <flux:field>
                    <flux:label>Teks tombol</flux:label>
                    <flux:input wire:model="button_text" placeholder="Belanja Sekarang" />
                    <flux:error name="button_text" />
                </flux:field>

                <flux:field>
                    <flux:label>Tautan tombol</flux:label>
                    <flux:input wire:model="button_url" placeholder="/explore atau https://example.com" />
                    <flux:error name="button_url" />
                </flux:field>
            </div>

            <div class="grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                <flux:field>
                    <flux:label badge="Required">Urutan tampil</flux:label>
                    <flux:text>Angka lebih kecil tampil lebih dahulu.</flux:text>
                    <flux:input wire:model="sort_order" type="number" min="0" max="999" />
                    <flux:error name="sort_order" />
                </flux:field>

                <flux:switch wire:model="is_active" label="Aktif" description="Tampilkan di beranda." />
            </div>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Simpan Banner</span>
                    <span wire:loading wire:target="submit">Menyimpan…</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>