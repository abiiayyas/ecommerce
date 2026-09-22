<?php

use App\Actions\Supplier\ConfirmSupplierDispatchAction;
use App\Enums\SupplierDispatchStatus;
use App\Models\Supplier\SupplierDispatch;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public function confirm(int $dispatchId, ConfirmSupplierDispatchAction $action)
    {
        $dispatch = SupplierDispatch::query()->findOrFail($dispatchId);
        
        try {
            $action->handle($dispatch);
            $this->dispatch('toast', type: 'success', message: 'Antrean dispatch berhasil dikonfirmasi.');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: $e->getMessage());
        }
    }

    public function with()
    {
        return [
            'dispatches' => SupplierDispatch::query()
                ->with(['orderShop.order', 'supplier'])
                ->where('status', SupplierDispatchStatus::Pending)
                ->orderBy('created_at', 'desc')
                ->paginate(10),
        ];
    }
};