<?php

use App\Models\Marketing\Banner;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function banners(): Collection
    {
        return Banner::query()
            ->active()
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
};
