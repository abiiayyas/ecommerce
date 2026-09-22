<?php

namespace App\Http\Controllers;

use App\Actions\Ecommerce\Checkout\CreateLandingOrderAction;
use App\Http\Requests\StoreLandingCheckoutRequest;
use App\Models\Marketing\LandingPage;
use Illuminate\Http\RedirectResponse;

class LandingCheckoutController extends Controller
{
    public function store(
        StoreLandingCheckoutRequest $request,
        LandingPage $landingPage,
        CreateLandingOrderAction $createLandingOrder,
    ): RedirectResponse {
        abort_unless($landingPage->is_active && $landingPage->published_at?->isPast(), 404);

        $order = $createLandingOrder->handle($landingPage, $request->validated());
        $route = $order->payment_mode === 'online' ? 'payment.show' : 'orders.detail';

        return redirect()->route($route, [
            'reference' => $order->reference,
            ...$order->guestRouteParameters(),
        ]);
    }
}
