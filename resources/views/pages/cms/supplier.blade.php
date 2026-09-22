<?php

use App\Models\User;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.supplier');

render(function (View $view) {
    $user = auth()->user();
    abort_unless($user instanceof User && $user->hasRole('superadmin'), 403);

    $view->with('title', 'Supplier & Gudang Dropship');
});
?>

<x-layouts.app :$title>
    <div class="mb-7">
        <h1 class="text-3xl font-bold">{{ $title }}</h1>
        <flux:text class="mt-2">Hubungkan satu sumber supplier aktif ke setiap varian produk.</flux:text>
    </div>

    <livewire:cms.supplier.manager />

    <div class="mt-12">
        <livewire:cms.supplier.dispatch-queue />
    </div>
</x-layouts.app>
