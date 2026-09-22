<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('cms.landing-page.manager')
        ->assertStatus(200);
});
