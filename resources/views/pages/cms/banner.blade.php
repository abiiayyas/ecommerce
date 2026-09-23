<?php

use App\Models\User;
use Illuminate\View\View;

use function Laravel\Folio\{name, render};

name('cms.banner');

render(function (View $view) {
    $user = auth()->user();
    abort_unless($user instanceof User && $user->hasRole('superadmin'), 403);

    $view->with([
        'title' => 'Manajemen Banner',
        'description' => 'Atur gambar, konten, tautan, urutan, dan status banner yang tampil di beranda.',
    ]);
});
?>

<x-layouts.app :$title>
    <div class="mb-7">
        <h1 class="text-3xl font-bold">{{ $title }}</h1>
        <flux:text class="mt-2">{{ $description }}</flux:text>
    </div>

    <livewire:cms.banner.table />
</x-layouts.app>
