<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Resources\Providers\ProviderResource;
use App\Modules\Providers\Services\CompletenessCalculator;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    public function getTitle(): string
    {
        return 'Listing details: '.$this->record->business_name_en;
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()->label('Back to review')];
    }

    protected function afterSave(): void
    {
        $this->record->forceFill(['completeness_score' => app(CompletenessCalculator::class)->score($this->record->fresh())])->save();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
