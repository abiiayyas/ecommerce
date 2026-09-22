<?php

namespace App\Actions\Marketing;

use App\Contracts\Marketing\MarketingEventPublisher;
use App\Data\Marketing\MarketingEventData;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageDailyStat;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecordLandingPageVisitAction
{
    public function __construct(
        private readonly MarketingEventPublisher $marketingEvents,
    ) {}

    public function handle(LandingPage $landingPage, Request $request): string
    {
        $stats = LandingPageDailyStat::query()->firstOrCreate([
            'landing_page_id' => $landingPage->getKey(),
            'campaign_id' => 0,
            'date' => today(),
        ]);
        $stats->increment('page_views');

        $eventId = $this->marketingEvents->newEventId();
        $this->marketingEvents->publish(new MarketingEventData(
            eventId: $eventId,
            name: 'ViewContent',
            occurredAt: now(),
            sourceUrl: $request->fullUrl(),
            customerData: [
                'client_ip_address' => $request->ip(),
                'client_user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                'fbp' => $request->cookie('_fbp'),
                'fbc' => $request->cookie('_fbc'),
            ],
            customData: [
                'content_ids' => [(string) $landingPage->product_id],
                'content_type' => 'product',
                'currency' => 'IDR',
            ],
            attribution: array_filter($request->only([
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_content',
                'fbclid',
            ])),
        ));

        return $eventId;
    }
}
