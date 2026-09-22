<?php

use App\Models\Marketing\LandingPage;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public LandingPage $landingPage;
    public array $builderData = [];
    
    // Page settings
    public ?int $productId = null;
    public string $slug = '';
    public string $headline = '';
    public bool $onlinePaymentEnabled = true;
    public bool $codEnabled = false;
    public string $metaPixelId = '';
    public bool $isActive = false;

    public function mount(LandingPage $landingPage)
    {
        $this->landingPage = $landingPage;
        
        $this->productId = $landingPage->product_id;
        $this->slug = $landingPage->slug;
        $this->headline = $landingPage->headline;
        $this->onlinePaymentEnabled = $landingPage->online_payment_enabled;
        $this->codEnabled = $landingPage->cod_enabled;
        $this->metaPixelId = $landingPage->meta_pixel_id ?? '';
        $this->isActive = $landingPage->is_active;

        $defaultData = [
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

        $this->builderData = $landingPage->content['builder'] ?? $defaultData;
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

    public function save(array $data)
    {
        if (! $this->onlinePaymentEnabled && ! $this->codEnabled) {
            $this->dispatch('toast', type: 'error', message: 'Aktifkan minimal satu metode pembayaran.');
            return;
        }

        $content = $this->landingPage->content ?? [];
        $content['builder'] = $data;
        
        $this->landingPage->update([
            'product_id' => $this->productId,
            'slug' => $this->slug,
            'headline' => $this->headline,
            'online_payment_enabled' => $this->onlinePaymentEnabled,
            'cod_enabled' => $this->codEnabled,
            'meta_pixel_id' => $this->metaPixelId ?: null,
            'is_active' => $this->isActive,
            'published_at' => $this->isActive ? now() : null,
            'content' => $content,
        ]);
        
        $this->dispatch('toast', type: 'success', message: 'Landing page layout & pengaturan berhasil disimpan.');
    }
};
