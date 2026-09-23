<div>
@if ($this->banners->isNotEmpty())
    <div
        class="mb-8"
        x-data="{
            active: 0,
            count: {{ $this->banners->count() }},
            previous() {
                this.active = (this.active - 1 + this.count) % this.count;
            },
            next() {
                this.active = (this.active + 1) % this.count;
            },
        }"
        role="region"
        aria-label="Banner promosi"
    >
        <div class="relative h-[200px] overflow-hidden rounded-2xl bg-zinc-900 md:h-[350px]">
            @foreach ($this->banners as $banner)
                @php
                    $buttonUrl = $banner->button_url;
                    $isInternalUrl = $buttonUrl && str_starts_with($buttonUrl, '/') && ! str_starts_with($buttonUrl, '//');
                    $isExternalUrl = $buttonUrl && in_array(parse_url($buttonUrl, PHP_URL_SCHEME), ['http', 'https'], true);
                @endphp

                <article
                    x-show="active === {{ $loop->index }}"
                    x-bind:aria-hidden="active !== {{ $loop->index }}"
                    @if (! $loop->first) style="display: none" @endif
                    class="absolute inset-0"
                    wire:key="storefront-banner-{{ $banner->id }}"
                >
                    @if ($banner->getFirstMediaUrl('image', 'banner') !== '')
                        <img
                            src="{{ $banner->getFirstMediaUrl('image', 'banner') }}"
                            alt=""
                            class="h-full w-full object-cover opacity-80"
                        />
                    @endif

                    <div class="absolute inset-0 flex bg-gradient-to-r from-black/70 via-black/35 to-transparent">
                        <div class="flex max-w-2xl flex-col justify-center gap-3 px-6 py-8 md:px-16">
                            <h2 class="text-3xl font-black tracking-tight text-white md:text-5xl">{{ $banner->title }}</h2>

                            @if ($banner->description)
                                <p class="text-base text-white/90 md:text-lg">{{ $banner->description }}</p>
                            @endif

                            @if ($banner->button_text && $isInternalUrl)
                                <div class="pt-1">
                                    <flux:button href="{{ $buttonUrl }}" wire:navigate>
                                        {{ $banner->button_text }}
                                    </flux:button>
                                </div>
                            @elseif ($banner->button_text && $isExternalUrl)
                                <div class="pt-1">
                                    <flux:button href="{{ $buttonUrl }}" target="_blank" rel="noopener noreferrer">
                                        {{ $banner->button_text }}
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($this->banners->count() > 1)
            <div class="mt-3 flex items-center justify-between gap-4">
                <div class="flex gap-2">
                    <button type="button" @click="previous" class="rounded-lg border border-zinc-200 bg-white p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800" aria-label="Banner sebelumnya">
                        <flux:icon.chevron-left class="size-4" />
                    </button>
                    <button type="button" @click="next" class="rounded-lg border border-zinc-200 bg-white p-2 text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800" aria-label="Banner berikutnya">
                        <flux:icon.chevron-right class="size-4" />
                    </button>
                </div>

                <div class="flex gap-2" role="tablist" aria-label="Pilih banner">
                    @foreach ($this->banners as $banner)
                        <button
                            type="button"
                            @click="active = {{ $loop->index }}"
                            :aria-selected="active === {{ $loop->index }}"
                            :class="active === {{ $loop->index }} ? 'bg-[#0c37b0] dark:bg-white' : 'bg-zinc-300 dark:bg-zinc-600'"
                            class="h-2.5 w-2.5 rounded-full transition focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2"
                            aria-label="Tampilkan banner {{ $loop->iteration }}"
                        ></button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
</div>