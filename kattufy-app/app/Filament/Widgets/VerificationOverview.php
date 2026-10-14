<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Enquiries\EnquiryResource;
use App\Filament\Resources\Providers\ProviderResource;
use App\Models\User;
use App\Modules\Accounts\Enums\Role;
use App\Modules\Enquiries\Models\Enquiry;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Models\VerificationEvent;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Overview counts, read straight from the lifecycle data the provider pages also use. */
class VerificationOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Rendered with the page, so the numbers are there on first paint.
    protected static bool $isLazy = false;

    protected ?string $heading = 'Overview';

    protected function getStats(): array
    {
        $count = fn (ProviderStatus ...$s) => Provider::whereIn('status', array_map(fn ($x) => $x->value, $s))->count();

        $submissions = VerificationEvent::where('to_status', ProviderStatus::UnderReview->value)->distinct()->count('provider_id');
        $returned = Provider::where('status', ProviderStatus::Consented->value)
            ->whereHas('events', fn ($q) => $q->where('from_status', ProviderStatus::UnderReview->value)->where('to_status', ProviderStatus::Consented->value))
            ->count();

        return [
            Stat::make('Provider submissions', $submissions)
                ->description('Businesses that have submitted for verification')
                ->icon(Heroicon::OutlinedInboxStack),
            Stat::make('Pending review', $count(ProviderStatus::UnderReview))
                ->description('Waiting for a decision')
                ->icon(Heroicon::OutlinedClock)
                ->color('warning')
                ->url(ProviderResource::getUrl('index', ['tab' => 'pending'])),
            Stat::make('Approved and published', $count(ProviderStatus::Published))
                ->description('Live in the directory')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->url(ProviderResource::getUrl('index', ['tab' => 'published'])),
            Stat::make('Needs update', $count(ProviderStatus::NeedsUpdate) + $returned)
                ->description('Sent back to the provider')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->url(ProviderResource::getUrl('index', ['tab' => 'returned'])),
            Stat::make('New enquiries', Enquiry::where('status', 'new')->count())
                ->description('Not yet assigned')
                ->icon(Heroicon::OutlinedInboxArrowDown)
                ->url(EnquiryResource::getUrl('index')),
            Stat::make('Customer accounts', User::role(Role::Customer->value)->count())
                ->description(User::role(Role::Provider->value)->count().' provider accounts')
                ->icon(Heroicon::OutlinedUsers),
        ];
    }
}
