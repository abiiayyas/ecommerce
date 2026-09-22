<?php

use App\Models\User;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.landing-page');

render(function (View $view) {
    $user = auth()->user();
    abort_unless($user instanceof User && $user->hasRole('superadmin'), 403);

    $view->with([
        'title' => 'Landing Page Iklan',
        'description' => 'Kelola halaman penjualan ringan, metode pembayaran, dan pelacakan kampanye.',
    ]);
});
?>

<x-layouts.app :$title>
    <div class="mb-7">
        <h1 class="text-3xl font-bold">{{ $title }}</h1>
        <flux:text class="mt-2">{{ $description }}</flux:text>
    </div>

    <livewire:cms.landing-page.manager />
</x-layouts.app>
