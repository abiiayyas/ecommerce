<?php

use App\Models\Marketing\LandingPage;
use App\Models\User;
use Illuminate\View\View;

use function Laravel\Folio\name;
use function Laravel\Folio\render;

name('cms.landing-page.builder');

render(function (View $view, string $id) {
    $user = auth()->user();
    abort_unless($user instanceof User && $user->hasRole('superadmin'), 403);

    $landingPage = LandingPage::query()->findOrFail($id);

    $view->with([
        'title' => 'Builder - ' . $landingPage->headline,
        'landingPage' => $landingPage,
    ]);
});
?>

<x-layouts.builder :$title>
    <livewire:cms.landing-page.builder :landing-page="$landingPage" />
</x-layouts.builder>
