<?php

use App\Actions\Cms\Banner\StoreBannerAction;
use App\Actions\Cms\Banner\UpdateBannerAction;
use App\Models\Marketing\Banner;
use Flux\Flux;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public string $modelInstance = Banner::class;

    public bool $isUpdate = false;

    #[Locked]
    public ?int $id = null;

    public ?string $title = null;
    public ?string $description = null;
    public ?string $button_text = null;
    public ?string $button_url = null;
    public int $sort_order = 0;
    public bool $is_active = true;
    public ?string $oldImage = null;
    public mixed $image = null;

    #[On('set-action')]
    public function setAction(?int $id = null): void
    {
        $this->authorizeOperator();
        $this->resetValidation();

        if ($id) {
            $this->isUpdate = true;
            $this->getRecordData($id);

            return;
        }

        $this->isUpdate = false;
        $this->resetRecordData();
    }

    public function submit(StoreBannerAction $storeAction, UpdateBannerAction $updateAction): void
    {
        $this->authorizeOperator();

        $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'button_url' => ['nullable', 'string', 'max:500', 'regex:/^(\/(?!\/)|https?:\/\/)/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
            'image' => [$this->isUpdate ? 'nullable' : 'required', 'image', 'max:5120'],
        ]);

        if ($this->isUpdate) {
            $updateAction->handle(
                Banner::query()->findOrFail($this->id),
                $this->bannerData(),
            );
        } else {
            $storeAction->handle($this->bannerData());
        }

        $this->dispatch(
            'toast',
            type: 'success',
            message: $this->isUpdate ? 'Banner berhasil diperbarui.' : 'Banner berhasil dibuat.',
        );
        $this->dispatch('reset-parent-page');
        $this->resetRecordData();
        Flux::modal('banner-form')->close();
    }

    private function getRecordData(int $id): void
    {
        $banner = Banner::query()->findOrFail($id);

        $this->fill($banner->only([
            'id',
            'title',
            'description',
            'button_text',
            'button_url',
            'sort_order',
            'is_active',
        ]));
        $this->oldImage = $banner->getFirstMediaUrl('image');
        $this->image = null;
    }

    private function resetRecordData(): void
    {
        $this->reset([
            'id',
            'title',
            'description',
            'button_text',
            'button_url',
            'oldImage',
            'image',
        ]);
        $this->sort_order = 0;
        $this->is_active = true;
    }

    /** @return array{title: ?string, description: ?string, button_text: ?string, button_url: ?string, sort_order: int, is_active: bool, image: mixed} */
    private function bannerData(): array
    {
        return [
            'title' => $this->title,
            'description' => blank($this->description) ? null : $this->description,
            'button_text' => blank($this->button_text) ? null : $this->button_text,
            'button_url' => blank($this->button_url) ? null : $this->button_url,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'image' => $this->image,
        ];
    }

    private function authorizeOperator(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }
};