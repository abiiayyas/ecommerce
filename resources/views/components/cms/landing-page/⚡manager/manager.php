<?php

use App\Models\Marketing\LandingPage;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;
    public ?int $productId = null;
    public string $slug = '';
    public string $headline = '';
    public string $subheadline = '';
    public string $benefits = '';
    public string $description = '';
    public string $faqs = '';
    public string $videoUrl = '';
    public string $ctaText = 'Pesan sekarang';
    public string $accentColor = '#ca4a2c';
    public bool $onlinePaymentEnabled = true;
    public bool $codEnabled = false;
    public string $metaPixelId = '';
    public bool $isActive = false;
    public $heroImage;

    /** @var array<int, mixed> */
    public array $galleryImages = [];

    #[Computed]
    public function landingPages(): Collection
    {
        return LandingPage::query()->with(['product', 'media'])->withCount('orders')->latest()->get();
    }

    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->where('status', true)
            ->whereHas('productFlats', fn ($query) => $query->where('status', true))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create()
    {
        $this->authorizeOperator();
        $product = \App\Models\Product\Product::query()->first();
        if (! $product) {
            $this->dispatch('toast', type: 'error', message: 'Silakan buat produk terlebih dahulu.');
            return;
        }
        $page = LandingPage::query()->create([
            'product_id' => $product->id,
            'slug' => 'draft-' . uniqid(),
            'headline' => 'Draft Landing Page',
            'is_active' => false,
        ]);
        return redirect()->route('cms.landing-page.builder', ['id' => $page->id]);
    }

    public function edit(int $id): void
    {
        $this->authorizeOperator();
        $page = LandingPage::query()->findOrFail($id);

        $this->editingId = $page->getKey();
        $this->productId = $page->product_id;
        $this->slug = $page->slug;
        $this->headline = $page->headline;
        $this->subheadline = $page->subheadline ?? '';
        $this->benefits = implode("\n", $page->content['benefits'] ?? []);
        $this->description = $page->content['description'] ?? '';
        $this->faqs = collect($page->content['faqs'] ?? [])
            ->map(fn (array $faq): string => ($faq['question'] ?? '').'|'.($faq['answer'] ?? ''))
            ->implode("\n");
        $this->videoUrl = $page->content['video_url'] ?? '';
        $this->ctaText = $page->cta_text;
        $this->accentColor = $page->accent_color;
        $this->onlinePaymentEnabled = $page->online_payment_enabled;
        $this->codEnabled = $page->cod_enabled;
        $this->metaPixelId = $page->meta_pixel_id ?? '';
        $this->isActive = $page->is_active;
        $this->heroImage = null;
        $this->galleryImages = [];
        $this->resetValidation();
    }

    public function updatedHeadline(string $value): void
    {
        if ($this->editingId === null || blank($this->slug)) {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        $this->authorizeOperator();
        $validated = $this->validate([
            'productId' => ['required', 'integer', 'exists:products,id'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('landing_pages', 'slug')->ignore($this->editingId)],
            'headline' => ['required', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string', 'max:500'],
            'benefits' => ['nullable', 'string', 'max:4000'],
            'description' => ['nullable', 'string', 'max:12000'],
            'faqs' => ['nullable', 'string', 'max:8000'],
            'videoUrl' => ['nullable', 'url', 'max:500'],
            'ctaText' => ['required', 'string', 'max:80'],
            'accentColor' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'onlinePaymentEnabled' => ['boolean'],
            'codEnabled' => ['boolean'],
            'metaPixelId' => ['nullable', 'regex:/^[0-9]{5,30}$/'],
            'isActive' => ['boolean'],
            'heroImage' => ['nullable', 'image', 'max:5120'],
            'galleryImages.*' => ['image', 'max:5120'],
        ]);

        if (! $this->onlinePaymentEnabled && ! $this->codEnabled) {
            $this->addError('onlinePaymentEnabled', 'Aktifkan minimal satu metode pembayaran.');

            return;
        }

        $page = LandingPage::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'product_id' => $validated['productId'],
                'slug' => Str::slug($validated['slug']),
                'headline' => $validated['headline'],
                'subheadline' => $validated['subheadline'] ?: null,
                'content' => [
                    'benefits' => collect(preg_split('/\r\n|\r|\n/', $validated['benefits'] ?? ''))->map(fn (string $line): string => trim($line))->filter()->values()->all(),
                    'description' => $validated['description'] ?: null,
                    'faqs' => $this->parsedFaqs($validated['faqs'] ?? ''),
                    'video_url' => $validated['videoUrl'] ?: null,
                ],
                'cta_text' => $validated['ctaText'],
                'accent_color' => strtolower($validated['accentColor']),
                'online_payment_enabled' => $validated['onlinePaymentEnabled'],
                'cod_enabled' => $validated['codEnabled'],
                'meta_pixel_id' => $validated['metaPixelId'] ?: null,
                'is_active' => $validated['isActive'],
                'published_at' => $validated['isActive'] ? now() : null,
            ],
        );

        if ($this->heroImage) {
            $page->addMedia($this->heroImage->getRealPath())
                ->usingFileName($this->heroImage->getClientOriginalName())
                ->toMediaCollection('hero');
        }

        foreach ($this->galleryImages as $image) {
            $page->addMedia($image->getRealPath())
                ->usingFileName($image->getClientOriginalName())
                ->toMediaCollection('gallery');
        }

        unset($this->landingPages);
        $this->dispatch('toast', type: 'success', message: 'Landing page berhasil disimpan.');
        $this->resetForm();
    }

    public function removeGalleryImage(int $mediaId): void
    {
        $this->authorizeOperator();
        $page = LandingPage::query()->findOrFail($this->editingId);
        $page->media()->whereKey($mediaId)->where('collection_name', 'gallery')->firstOrFail()->delete();
        unset($this->landingPages);
    }

    public function delete(int $id): void
    {
        $this->authorizeOperator();
        $page = LandingPage::query()->withCount('orders')->findOrFail($id);

        if ($page->orders_count > 0) {
            $page->update(['is_active' => false, 'published_at' => null]);
            $message = 'Landing page memiliki pesanan dan telah dinonaktifkan.';
        } else {
            $page->delete();
            $message = 'Landing page berhasil dihapus.';
        }

        unset($this->landingPages);
        $this->resetForm();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'productId', 'slug', 'headline', 'subheadline', 'benefits', 'description',
            'faqs', 'videoUrl', 'metaPixelId', 'isActive', 'heroImage', 'galleryImages',
        ]);
        $this->ctaText = 'Pesan sekarang';
        $this->accentColor = '#ca4a2c';
        $this->onlinePaymentEnabled = true;
        $this->codEnabled = false;
        $this->resetValidation();
    }

    /** @return array<int, array{question: string, answer: string}> */
    private function parsedFaqs(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value))
            ->map(function (string $line): ?array {
                [$question, $answer] = array_pad(explode('|', $line, 2), 2, '');
                $question = trim($question);
                $answer = trim($answer);

                return filled($question) && filled($answer) ? compact('question', 'answer') : null;
            })
            ->filter()->values()->all();
    }

    private function authorizeOperator(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->hasRole('superadmin'), 403);
    }
};
