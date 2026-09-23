<?php

use App\Actions\Cms\Banner\DeleteBannerAction;
use App\Livewire\BaseComponent;
use App\Models\Marketing\Banner;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

new class extends BaseComponent
{
    #[Locked]
    public string $modelInstance = Banner::class;

    /** @var array<int, array{name: string, field: string, no_search?: bool}> */
    public array $searchBy = [
        [
            'name' => 'Judul',
            'field' => 'title',
        ],
        [
            'name' => 'Urutan',
            'field' => 'sort_order',
            'no_search' => true,
        ],
        [
            'name' => 'Status',
            'field' => 'is_active',
            'no_search' => true,
        ],
    ];

    public function mount(): void
    {
        $this->authorizeOperator();
        $this->paginationOrderBy = 'sort_order';
        $this->paginationOrder = 'asc';
    }

    public function render(): View
    {
        $this->authorizeOperator();

        if ($this->search !== '') {
            $this->resetPage();
        }

        return $this->view([
            'data' => $this->getDataWithFilter(
                model: Banner::query()->with('media'),
                searchBy: $this->searchBy,
                orderBy: $this->paginationOrderBy,
                order: $this->paginationOrder,
                paginate: $this->paginate,
                s: $this->search,
            ),
        ]);
    }

    #[On('delete')]
    public function delete(int $id, DeleteBannerAction $deleteAction): void
    {
        $this->authorizeOperator();
        $deleteAction->handle(Banner::query()->findOrFail($id));

        $this->dispatch('toast', type: 'success', message: 'Banner berhasil dihapus.');
    }

    private function authorizeOperator(): void
    {
        abort_unless(auth()->user()?->hasRole('superadmin'), 403);
    }
};