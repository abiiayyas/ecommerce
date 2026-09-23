<?php

namespace App\Actions\Cms\Banner;

use App\Models\Marketing\Banner;

class DeleteBannerAction
{
    public function handle(Banner $banner): bool
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);

        return $banner->delete();
    }
}
