<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Providers\ProviderResource;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Support\Phone;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Oldest submissions first, so nothing waits too long. */
class ReviewQueue extends TableWidget
{
    protected static ?int $sort = 2;

    // Rendered with the page, so the numbers are there on first paint.
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Waiting for review';

    public function table(Table $table): Table
    {
        return $table
            ->query(Provider::query()->where('status', ProviderStatus::UnderReview->value)->with(['categories', 'credentials']))
            ->defaultSort('status_changed_at', 'asc')
            ->paginated([5, 10])
            ->columns([
                TextColumn::make('business_name_en')->label('Business')->weight('semibold')
                    ->description(fn (Provider $record) => ProviderResource::reference($record).' · '.$record->categories->pluck('name_en')->join(', ')),
                TextColumn::make('phone')->label('Mobile')->formatStateUsing(fn ($state) => Phone::mask($state)),
                TextColumn::make('documents')->label('Documents')
                    ->state(fn (Provider $record) => $record->credentials->count().' submitted'),
                TextColumn::make('status_changed_at')->label('Waiting since')->since('Asia/Kolkata'),
            ])
            ->recordActions([
                Action::make('review')->label('Review')->url(fn (Provider $record) => ProviderResource::getUrl('view', ['record' => $record]))->button()->size('sm'),
            ])
            ->emptyStateHeading('Nothing waiting')
            ->emptyStateDescription('New submissions will appear here.');
    }
}
