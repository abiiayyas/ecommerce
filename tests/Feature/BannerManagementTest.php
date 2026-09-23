<?php

use App\Models\Spatie\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Role::findOrCreate('superadmin', 'api');
    Role::findOrCreate('user', 'api');
});

test('only superadmins may access banner management', function (): void {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($superadmin)
        ->get('/cms/banner')
        ->assertSuccessful()
        ->assertSeeLivewire('cms.banner.table');

    $this->actingAs($user)
        ->get('/cms/banner')
        ->assertForbidden();
});
