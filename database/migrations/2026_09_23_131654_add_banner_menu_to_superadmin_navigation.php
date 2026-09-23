<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $superadminRoleId = DB::table('roles')
            ->where('name', 'superadmin')
            ->value('id');

        if ($superadminRoleId === null) {
            return;
        }

        DB::table('menus')->updateOrInsert(
            [
                'role_id' => $superadminRoleId,
                'url' => 'cms.banner',
            ],
            [
                'name' => 'Banners',
                'icon' => 'photo',
                'order' => 250,
                'active_pattern' => 'cms.banner',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        Cache::forget('menu:'.$superadminRoleId);
    }

    public function down(): void
    {
        $superadminRoleId = DB::table('roles')
            ->where('name', 'superadmin')
            ->value('id');

        if ($superadminRoleId === null) {
            return;
        }

        DB::table('menus')
            ->where('role_id', $superadminRoleId)
            ->where('url', 'cms.banner')
            ->delete();

        Cache::forget('menu:'.$superadminRoleId);
    }
};
