<?php

use App\Models\Marketing\LandingPage;
use App\Models\Product\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public LandingPage $landingPage;
    /** @var array<string, mixed> */
    public array $builderData = [];

    public ?int $productId = null;
    public string $slug = '';
    public string $headline = '';
    public bool $onlinePaymentEnabled = true;
    public bool $codEnabled = false;
    public string $metaPixelId = '';
    public bool $isActive = false;

    public function mount(LandingPage $landingPage): void
    {
        $this->authorizeOperator();
        $this->landingPage = $landingPage;
        $this->productId = $landingPage->product_id;
        $this->slug = $landingPage->slug;
        $this->headline = $landingPage->headline;
        $this->onlinePaymentEnabled = $landingPage->online_payment_enabled;
        $this->codEnabled = $landingPage->cod_enabled;
        $this->metaPixelId = $landingPage->meta_pixel_id ?? '';
        $this->isActive = $landingPage->is_active;
        $this->builderData = $this->normalizeBuilderData($landingPage->content['builder'] ?? $this->defaultBuilderData());
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

    public function save(array $data): void
    {
        $this->authorizeOperator();
        $validated = $this->validate([
            'productId' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', true)),
            ],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('landing_pages', 'slug')->ignore($this->landingPage->getKey())],
            'headline' => ['required', 'string', 'max:255'],
            'metaPixelId' => ['nullable', 'regex:/^[0-9]{5,30}$/'],
            'onlinePaymentEnabled' => ['boolean'],
            'codEnabled' => ['boolean'],
            'isActive' => ['boolean'],
        ]);

        if (! $this->onlinePaymentEnabled && ! $this->codEnabled) {
            $this->addError('onlinePaymentEnabled', 'Aktifkan minimal satu metode pembayaran.');

            return;
        }

        $builderData = $this->normalizeBuilderData($data);
        $content = $this->landingPage->content ?? [];
        $content['builder'] = $builderData;

        $this->landingPage->update([
            'product_id' => $validated['productId'],
            'slug' => Str::slug($validated['slug']),
            'headline' => $validated['headline'],
            'online_payment_enabled' => $validated['onlinePaymentEnabled'],
            'cod_enabled' => $validated['codEnabled'],
            'meta_pixel_id' => $validated['metaPixelId'] ?: null,
            'is_active' => $validated['isActive'],
            'published_at' => $validated['isActive'] ? now() : null,
            'content' => $content,
        ]);

        $this->builderData = $builderData;
        $this->landingPage->refresh();
        $this->dispatch('toast', type: 'success', message: 'Landing page layout dan pengaturan berhasil disimpan.');
    }

    /** @return array<string, mixed> */
    private function defaultBuilderData(): array
    {
        return [
            'layout' => 'boxed',
            'radius' => 12,
            'font' => 'Inter',
            'paddingY' => 24,
            'paddingX' => 24,
            'marginY' => 16,
            'marginX' => 0,
            'componentMargin' => 16,
            'blocks' => [],
        ];
    }

    /** @param array<string, mixed> $data */
    private function normalizeBuilderData(array $data): array
    {
        $defaults = $this->defaultBuilderData();
        $allowedTypes = ['text', 'image', 'form', 'list', 'testimonial', 'faq', 'slider', 'button', 'youtube', 'gif', 'countdown', 'divider', 'html'];
        $blocks = is_array($data['blocks'] ?? null) ? array_slice($data['blocks'], 0, 50) : [];

        return [
            'layout' => in_array($data['layout'] ?? null, ['boxed', 'fullwidth'], true) ? $data['layout'] : $defaults['layout'],
            'radius' => $this->boundedInteger($data['radius'] ?? null, 0, 48, $defaults['radius']),
            'font' => in_array($data['font'] ?? null, ['Inter', 'Manrope', 'Roboto', 'Poppins'], true) ? $data['font'] : $defaults['font'],
            'paddingY' => $this->boundedInteger($data['paddingY'] ?? null, 0, 160, $defaults['paddingY']),
            'paddingX' => $this->boundedInteger($data['paddingX'] ?? null, 0, 160, $defaults['paddingX']),
            'marginY' => $this->boundedInteger($data['marginY'] ?? null, 0, 160, $defaults['marginY']),
            'marginX' => $this->boundedInteger($data['marginX'] ?? null, 0, 160, $defaults['marginX']),
            'componentMargin' => $this->boundedInteger($data['componentMargin'] ?? null, 0, 96, $defaults['componentMargin']),
            'blocks' => collect($blocks)
                ->map(function (mixed $block) use ($allowedTypes): ?array {
                    if (! is_array($block) || ! in_array($block['type'] ?? null, $allowedTypes, true)) {
                        return null;
                    }

                    return [
                        'id' => is_string($block['id'] ?? null) && preg_match('/^block_[a-z0-9]+$/', $block['id'])
                            ? $block['id']
                            : 'block_' . Str::lower(Str::random(12)),
                        'type' => $block['type'],
                        'content' => $this->sanitizeBlockContent($block['content'] ?? ''),
                    ];
                })
                ->filter()
                ->values()
                ->all(),
        ];
    }

    private function sanitizeBlockContent(mixed $value): string
    {
        $value = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', (string) $value) ?? '';

        return Str::limit(trim(strip_tags($value)), 12_000, '');
    }

    private function boundedInteger(mixed $value, int $min, int $max, int $default): int
    {
        return is_numeric($value) ? min($max, max($min, (int) $value)) : $default;
    }

    private function authorizeOperator(): void
    {
        $user = auth()->user();
        abort_unless($user instanceof User && $user->hasRole('superadmin'), 403);
    }
};
