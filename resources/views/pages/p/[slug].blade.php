<?php

use App\Actions\Marketing\RecordLandingPageVisitAction;
use App\Models\Marketing\LandingPage;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('landing.show');

render(function (View $view, string $slug, RecordLandingPageVisitAction $recordVisit): View {
    $landingPage = LandingPage::query()
        ->published()
        ->with(['product.productFlats' => fn ($query) => $query->where('status', true)->with('media')])
        ->where('slug', $slug)
        ->firstOrFail();

    abort_if($landingPage->product->productFlats->isEmpty(), 404);

    return $view->with([
        'landingPage' => $landingPage,
        'productFlats' => $landingPage->product->productFlats,
        'defaultFlat' => $landingPage->product->productFlats->first(),
        'content' => $landingPage->content ?? [],
        'metaEventId' => $recordVisit->handle($landingPage, request()),
    ]);
});
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $landingPage->subheadline ?: str($landingPage->product->description)->stripTags()->limit(155) }}">
    <meta name="robots" content="index, follow">
    <meta name="meta-pixel-id" content="{{ $landingPage->meta_pixel_id ?: config('marketing.providers.meta.pixel_id') }}">
    <meta name="meta-event-id" content="{{ $metaEventId }}">
    <title>{{ $landingPage->headline }} | {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/logo3.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('img/logo3.png') }}">
    @vite(['resources/css/app.css', 'resources/js/landing-page.js'])
</head>
<body class="bg-stone-50 text-stone-950 antialiased">
    <main class="mx-auto min-h-screen max-w-6xl overflow-x-clip bg-stone-50">
        <header class="flex min-h-14 items-center justify-between gap-4 border-b border-stone-200 px-4 sm:px-8">
            <a href="{{ route('home') }}" class="inline-flex min-h-11 items-center font-semibold tracking-tight focus-visible:outline-2 focus-visible:outline-offset-4">
                {{ config('app.name') }}
            </a>
            <span class="text-sm text-stone-600">Pengiriman ke seluruh Indonesia</span>
        </header>

        <section class="grid gap-8 px-4 py-8 sm:px-8 lg:grid-cols-[1.1fr_0.9fr] lg:gap-14 lg:py-14">
            <div class="space-y-5">
                @php($hero = $landingPage->getFirstMedia('hero') ?? $defaultFlat->getFirstMedia())
                @if ($hero)
                    <picture class="block overflow-hidden bg-stone-200">
                        <img
                            src="{{ $hero->getUrl($hero->collection_name === 'hero' ? 'hero' : '') }}"
                            @if ($hero->hasGeneratedConversion('hero')) srcset="{{ $hero->getSrcset('hero') }}" @endif
                            alt="{{ $landingPage->headline }}"
                            width="900"
                            height="900"
                            fetchpriority="high"
                            class="aspect-square size-full object-cover"
                        >
                    </picture>
                @else
                    <div class="flex aspect-square items-center justify-center bg-stone-200 px-8 text-center text-stone-600">Gambar produk belum tersedia.</div>
                @endif

                @if ($landingPage->getMedia('gallery')->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2" aria-label="Galeri produk">
                        @foreach ($landingPage->getMedia('gallery') as $image)
                            <img src="{{ $image->getUrl('card') }}" alt="Galeri {{ $landingPage->headline }}" width="320" height="320" loading="lazy" class="aspect-square size-full object-cover">
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="self-start lg:sticky lg:top-6">
                <p class="mb-3 text-sm font-semibold uppercase tracking-[0.14em] text-stone-600">Pilihan langsung dari gudang</p>
                <h1 class="max-w-xl text-4xl font-semibold leading-[1.05] tracking-[-0.035em] sm:text-5xl">{{ $landingPage->headline }}</h1>
                @if ($landingPage->subheadline)
                    <p class="mt-5 max-w-xl text-lg leading-8 text-stone-700">{{ $landingPage->subheadline }}</p>
                @endif
                <p id="landing-price" class="mt-7 text-3xl font-semibold" data-price="{{ (float) $defaultFlat->price }}">Rp{{ number_format((float) $defaultFlat->price, 0, ',', '.') }}</p>

                <a href="#pesan" class="mt-7 inline-flex min-h-12 items-center justify-center px-6 font-semibold text-white focus-visible:outline-2 focus-visible:outline-offset-4" style="background-color: {{ $landingPage->accent_color }}">
                    {{ $landingPage->cta_text }}
                </a>
            </div>
        </section>

        @php($builderBlocks = is_array($builder['blocks'] ?? null) ? array_slice($builder['blocks'], 0, 50) : [])
        @if (filled($builderBlocks))
            <section
                class="mx-auto grid max-w-5xl gap-5 px-4 sm:px-8"
                style="padding-top: {{ min(160, max(0, (int) ($builder['paddingY'] ?? 24))) }}px; padding-bottom: {{ min(160, max(0, (int) ($builder['paddingY'] ?? 24))) }}px; font-family: {{ in_array($builder['font'] ?? null, ['Inter', 'Manrope', 'Roboto', 'Poppins'], true) ? $builder['font'] : 'Inter' }}"
            >
                @foreach ($builderBlocks as $block)
                    @php($blockType = $block['type'] ?? 'text')
                    @php($blockContent = trim((string) ($block['content'] ?? '')))
                    @if ($blockType === 'text')
                        <p class="whitespace-pre-line text-lg leading-8 text-stone-700">{{ $blockContent }}</p>
                    @elseif ($blockType === 'button')
                        <div><a href="#pesan" class="inline-flex min-h-12 items-center justify-center px-6 font-semibold text-white" style="background-color: {{ $landingPage->accent_color }}">{{ $blockContent ?: $landingPage->cta_text }}</a></div>
                    @elseif ($blockType === 'list')
                        <ul class="grid gap-3 sm:grid-cols-2">
                            @foreach (preg_split('/\r\n|\r|\n/', $blockContent) as $item)
                                @if (filled(trim($item)))
                                    <li class="flex gap-3 border-t border-stone-200 pt-3 leading-7"><span aria-hidden="true">✓</span><span>{{ trim($item) }}</span></li>
                                @endif
                            @endforeach
                        </ul>
                    @elseif ($blockType === 'testimonial')
                        <blockquote class="border-l-2 border-stone-900 pl-5 text-xl italic leading-8 text-stone-700">“{{ $blockContent }}”</blockquote>
                    @elseif ($blockType === 'faq')
                        @php([$question, $answer] = array_pad(explode('|', $blockContent, 2), 2, ''))
                        @if (filled(trim($question)) && filled(trim($answer)))
                            <details class="border-y border-stone-200 py-4"><summary class="cursor-pointer font-semibold">{{ trim($question) }}</summary><p class="mt-3 leading-7 text-stone-700">{{ trim($answer) }}</p></details>
                        @endif
                    @elseif ($blockType === 'form')
                        <div class="rounded-xl bg-stone-100 p-6"><h2 class="text-2xl font-semibold">Siap pesan?</h2><p class="mt-2 text-stone-600">Lengkapi data pengiriman di bawah.</p><a href="#pesan" class="mt-4 inline-flex font-semibold underline underline-offset-4">Ke form pemesanan →</a></div>
                    @elseif ($blockType === 'divider')
                        <div class="border-t border-stone-200"></div>
                    @elseif (filled($blockContent))
                        <p class="whitespace-pre-line leading-7 text-stone-700">{{ $blockContent }}</p>
                    @endif
                @endforeach
            </section>
        @endif

        @if (filled($content['benefits'] ?? []))
            <section class="border-y border-stone-200 bg-white px-4 py-10 sm:px-8 lg:py-14" aria-labelledby="benefit-title">
                <h2 id="benefit-title" class="max-w-2xl text-2xl font-semibold tracking-tight">Yang membuat produk ini layak dipilih</h2>
                <ul class="mt-7 grid gap-x-10 gap-y-5 md:grid-cols-2">
                    @foreach ($content['benefits'] as $benefit)
                        <li class="flex gap-3 border-t border-stone-200 pt-4 leading-7"><span aria-hidden="true">✓</span><span>{{ $benefit }}</span></li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (filled($content['description'] ?? null))
            <section class="grid gap-5 px-4 py-10 sm:px-8 lg:grid-cols-[0.35fr_0.65fr] lg:py-14">
                <h2 class="text-2xl font-semibold tracking-tight">Detail produk</h2>
                <div class="whitespace-pre-line text-base leading-8 text-stone-700">{{ $content['description'] }}</div>
            </section>
        @endif

        <section id="pesan" class="border-t border-stone-200 bg-stone-900 px-4 py-10 text-white sm:px-8 lg:py-14">
            <div class="grid gap-9 lg:grid-cols-[0.38fr_0.62fr]">
                <div>
                    <h2 class="text-3xl font-semibold tracking-tight">Pesan tanpa pindah halaman</h2>
                    <p class="mt-4 leading-7 text-stone-300">Pilih varian, alamat, kurir, dan metode pembayaran. Total dihitung dari harga server.</p>
                </div>

                <form
                    method="POST"
                    action="{{ route('landing.checkout', $landingPage) }}"
                    class="grid gap-5"
                    data-landing-checkout
                    data-area-url="{{ route('landing.areas', $landingPage) }}"
                    data-rate-url="{{ route('landing.rates', $landingPage) }}"
                >
                    @csrf
                    @foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content'] as $utm)
                        <input type="hidden" name="{{ $utm }}" value="{{ request()->query($utm) }}">
                    @endforeach
                    <input type="hidden" name="click_id" value="{{ request()->query('fbclid') }}">
                    <input type="hidden" name="marketing_event_id" data-marketing-event-id>
                    <input type="hidden" name="destination_area_id" data-area-id value="{{ old('destination_area_id') }}">
                    <input type="hidden" name="area_string" data-area-name value="{{ old('area_string') }}">
                    <input type="hidden" name="shipping_rate_quote_id" data-rate-id value="{{ old('shipping_rate_quote_id') }}">

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium">Varian
                            <select name="product_flat_id" data-product-flat class="min-h-12 border border-stone-600 bg-stone-950 px-3 text-white focus:border-white focus:outline-none">
                                @foreach ($productFlats as $flat)
                                    <option value="{{ $flat->id }}" data-price="{{ (float) $flat->price }}" @selected(old('product_flat_id', $defaultFlat->id) == $flat->id)>{{ $flat->name }} · Rp{{ number_format((float) $flat->price, 0, ',', '.') }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="grid gap-2 text-sm font-medium">Jumlah
                            <input name="quantity" data-quantity type="number" min="1" max="100" value="{{ old('quantity', 1) }}" class="min-h-12 border border-stone-600 bg-stone-950 px-3 focus:border-white focus:outline-none" required>
                        </label>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <label class="grid gap-2 text-sm font-medium">Nama penerima
                            <input name="contact_name" value="{{ old('contact_name') }}" autocomplete="name" class="min-h-12 border border-stone-600 bg-stone-950 px-3 focus:border-white focus:outline-none" required>
                        </label>
                        <label class="grid gap-2 text-sm font-medium">Nomor WhatsApp
                            <input name="contact_phone" value="{{ old('contact_phone') }}" autocomplete="tel" inputmode="tel" class="min-h-12 border border-stone-600 bg-stone-950 px-3 focus:border-white focus:outline-none" required>
                        </label>
                    </div>
                    <label class="grid gap-2 text-sm font-medium">Email
                        <input name="contact_email" value="{{ old('contact_email') }}" type="email" autocomplete="email" class="min-h-12 border border-stone-600 bg-stone-950 px-3 focus:border-white focus:outline-none" required>
                    </label>
                    <label class="grid gap-2 text-sm font-medium">Cari kecamatan atau kode pos
                        <input data-area-search autocomplete="off" class="min-h-12 border border-stone-600 bg-stone-950 px-3 focus:border-white focus:outline-none" placeholder="Ketik minimal 3 karakter" required>
                    </label>
                    <div data-area-results class="hidden border border-stone-700 bg-stone-950" role="listbox" aria-label="Hasil pencarian area"></div>
                    <label class="grid gap-2 text-sm font-medium">Alamat lengkap
                        <textarea name="address" rows="3" autocomplete="street-address" class="border border-stone-600 bg-stone-950 p-3 focus:border-white focus:outline-none" required>{{ old('address') }}</textarea>
                    </label>
                    <label class="grid gap-2 text-sm font-medium">Kode pos
                        <input name="postal_code" value="{{ old('postal_code') }}" autocomplete="postal-code" class="min-h-12 border border-stone-600 bg-stone-950 px-3 focus:border-white focus:outline-none" required>
                    </label>

                    <fieldset class="grid gap-3">
                        <legend class="text-sm font-medium">Metode pembayaran</legend>
                        <div class="flex flex-wrap gap-3">
                            @if ($landingPage->online_payment_enabled)
                                <label class="inline-flex min-h-11 items-center gap-2 border border-stone-600 px-4"><input type="radio" name="payment_mode" value="online" @checked(old('payment_mode', 'online') === 'online')> Bayar online</label>
                            @endif
                            @if ($landingPage->cod_enabled)
                                <label class="inline-flex min-h-11 items-center gap-2 border border-stone-600 px-4"><input type="radio" name="payment_mode" value="cod" @checked(old('payment_mode') === 'cod')> COD</label>
                            @endif
                        </div>
                    </fieldset>

                    <button type="button" data-load-rates class="min-h-12 border border-[#0c37b0] bg-[#0c37b0] px-5 font-semibold text-white transition hover:bg-[#092b8d] focus-visible:outline-2 focus-visible:outline-offset-4">Hitung ongkir</button>
                    <div data-rate-results class="grid gap-2" aria-live="polite"></div>
                    @if ($errors->any())
                        <div class="border border-red-400 bg-red-950 p-4 text-sm text-red-100" role="alert">
                            <ul class="grid gap-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <button type="submit" class="min-h-12 px-6 font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-offset-4" style="background-color: {{ $landingPage->accent_color }}" disabled data-submit-order>{{ $landingPage->cta_text }}</button>
                </form>
            </div>
        </section>

        @if (filled($content['faqs'] ?? []))
            <section class="px-4 py-10 sm:px-8 lg:py-14" aria-labelledby="faq-title">
                <h2 id="faq-title" class="text-2xl font-semibold tracking-tight">Pertanyaan yang sering ditanyakan</h2>
                <div class="mt-6 divide-y divide-stone-200 border-y border-stone-200">
                    @foreach ($content['faqs'] as $faq)
                        <details class="py-4"><summary class="min-h-11 cursor-pointer py-2 font-semibold">{{ $faq['question'] ?? '' }}</summary><p class="pb-2 leading-7 text-stone-700">{{ $faq['answer'] ?? '' }}</p></details>
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</body>
</html>
