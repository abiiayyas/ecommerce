<?php

namespace App\Actions\Cms\Banner;

use App\Models\Marketing\Banner;
use App\Traits\WithMediaCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UpdateBannerAction
{
    use WithMediaCollection;

    /** @param array<string, mixed> $data */
    public function handle(Banner $banner, array $data): Banner
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);

        $banner->update(Arr::except($data, 'image'));
        $image = $data['image'] ?? null;

        if ($image instanceof UploadedFile || $image instanceof TemporaryUploadedFile) {
            $this->saveMedia($banner, $image, 'image');
        }

        return $banner->fresh();
    }
}
