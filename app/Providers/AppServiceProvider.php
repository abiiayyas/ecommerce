<?php

namespace App\Providers;

use App\Contracts\Marketing\MarketingEventPublisher;
use App\Contracts\Payments\PaymentGateway;
use App\Contracts\Shipping\ShippingProvider;
use App\Models\User;
use App\Services\Marketing\MarketingTracker;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Shipping\ShippingManager;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MarketingEventPublisher::class, MarketingTracker::class);

        $this->app->bind(
            PaymentGateway::class,
            fn (): PaymentGateway => $this->app->make(PaymentGatewayManager::class)->driver(),
        );

        $this->app->bind(
            ShippingProvider::class,
            fn (): ShippingProvider => $this->app->make(ShippingManager::class)
                ->driver(config('shipping.default', 'biteship')),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::automaticallyEagerLoadRelationships();

        // Production force HTTPS`
        if ($this->app->isProduction()) {
            URL::forceHttps();
            $this->app['request']->server->set('HTTPS', true);
        }

        // Gate pulse
        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('superadmin');
        });

        // Timezone
        Carbon::macro('inApplicationTimezone', function () {
            return $this->tz(config('app.timezone_display'));
        });

        // In user timezone
        Carbon::macro('inUserTimezone', function () {
            return $this->tz(auth()->user()?->timezone ?? config('app.timezone_display'));
        });
    }
}
