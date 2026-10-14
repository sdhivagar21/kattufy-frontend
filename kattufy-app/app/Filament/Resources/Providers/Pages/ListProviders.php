<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Resources\Providers\ProviderResource;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    public function getTabs(): array
    {
        $count = fn (array $statuses) => Provider::whereIn('status', $statuses)->count();

        return [
            'pending' => Tab::make('Pending review')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProviderStatus::UnderReview->value))
                ->badge($count([ProviderStatus::UnderReview->value]))->badgeColor('warning'),
            'returned' => Tab::make('Needs update')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [ProviderStatus::NeedsUpdate->value, ProviderStatus::Consented->value])
                    ->whereHas('events', fn ($e) => $e->whereNotNull('reason')->whereIn('to_status', [ProviderStatus::NeedsUpdate->value, ProviderStatus::Consented->value])))
                ->badge($count([ProviderStatus::NeedsUpdate->value]) ?: null),
            'published' => Tab::make('Approved')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProviderStatus::Published->value)),
            'suspended' => Tab::make('Suspended')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProviderStatus::Suspended->value)),
            'draft' => Tab::make('Not submitted')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', [ProviderStatus::Found->value, ProviderStatus::Consented->value])),
            'all' => Tab::make('All'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'pending';
    }
}
