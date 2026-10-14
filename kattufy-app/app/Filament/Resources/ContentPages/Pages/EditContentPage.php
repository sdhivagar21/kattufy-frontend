<?php

namespace App\Filament\Resources\ContentPages\Pages;

use App\Filament\Resources\ContentPages\ContentPageResource;
use Filament\Resources\Pages\EditRecord;

class EditContentPage extends EditRecord
{
    protected static string $resource = ContentPageResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($data['status'] === 'published' && ! $this->record->published_at) {
            $data['published_at'] = now();
        }

        if ($data['ta_status'] === 'reviewed' && $this->record->ta_status !== 'reviewed') {
            $data['ta_reviewed_by'] = auth()->id();
            $data['ta_reviewed_at'] = now();
        }

        return $data;
    }
}
