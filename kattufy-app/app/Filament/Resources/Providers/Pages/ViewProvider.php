<?php

namespace App\Filament\Resources\Providers\Pages;

use App\Filament\Resources\Providers\ProviderResource;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Enums\CredentialStatus;
use App\Modules\Verification\Exceptions\TransitionNotAllowed;
use App\Modules\Verification\Services\CredentialReviewService;
use App\Modules\Verification\Services\LifecycleService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * The review screen. Every decision goes through LifecycleService or
 * CredentialReviewService, so the provider's status page shows the same thing.
 */
class ViewProvider extends ViewRecord
{
    protected static string $resource = ProviderResource::class;

    public function getTitle(): string
    {
        return $this->record->business_name_en;
    }

    public function getSubheading(): ?string
    {
        return ProviderResource::reference($this->record).' · '.$this->record->status->label();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->approveAction(),
            $this->requestUpdateAction(),
            $this->checkCredentialAction(),
            ActionGroup::make([
                EditAction::make()->label('Listing details')->icon(Heroicon::OutlinedPencilSquare),
                $this->showIdAction(),
                $this->markEditsReviewedAction(),
                $this->markTamilReviewedAction(),
                $this->suspendAction(),
                $this->reinstateAction(),
            ])->label('More')->icon(Heroicon::OutlinedEllipsisVertical)->button()->color('gray'),
        ];
    }

    private function canVerify(): bool
    {
        return auth()->user()->can('verify', $this->record);
    }

    private function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Approve and publish')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->visible(fn () => $this->record->status === ProviderStatus::UnderReview && $this->canVerify())
            ->modalHeading('Approve this provider?')
            ->modalDescription('The profile goes live in the directory and the provider dashboard opens. The publish checklist must pass.')
            ->schema([
                Textarea::make('note')->label('Note to the provider (optional)')->rows(3)->maxLength(1000),
            ])
            ->modalSubmitActionLabel('Approve')
            ->action(fn (array $data) => $this->moveTo(ProviderStatus::Published, $data['note'] ?? null, 'Provider approved and published'));
    }

    private function requestUpdateAction(): Action
    {
        return Action::make('requestUpdate')
            ->label('Request update')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('warning')
            ->visible(fn () => in_array($this->record->status, [ProviderStatus::UnderReview, ProviderStatus::Published], true) && $this->canVerify())
            ->modalHeading('Ask the provider to update their details')
            ->modalDescription(fn () => $this->record->status === ProviderStatus::Published
                ? 'The profile stays published and is flagged "Needs update". The provider sees your reason.'
                : 'The submission goes back to the provider with your reason, and they can correct it and submit again.')
            ->schema([
                Textarea::make('reason')->label('What needs to change')->required()->rows(4)->maxLength(1000)
                    ->helperText('Shown to the provider exactly as written.'),
            ])
            ->modalSubmitActionLabel('Send request')
            ->action(function (array $data) {
                $to = $this->record->status === ProviderStatus::Published ? ProviderStatus::NeedsUpdate : ProviderStatus::Consented;
                $this->moveTo($to, $data['reason'], 'Update requested');
            });
    }

    private function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label('Suspend')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn () => in_array($this->record->status, [ProviderStatus::Published, ProviderStatus::NeedsUpdate], true) && $this->canVerify())
            ->modalHeading('Suspend this provider?')
            ->modalDescription('The profile is removed from the directory and the provider dashboard closes until the issue is resolved.')
            ->schema([Textarea::make('reason')->label('Reason')->required()->rows(3)->maxLength(1000)])
            ->modalSubmitActionLabel('Suspend')
            ->action(fn (array $data) => $this->moveTo(ProviderStatus::Suspended, $data['reason'], 'Provider suspended'));
    }

    private function reinstateAction(): Action
    {
        return Action::make('reinstate')
            ->label('Return to review')
            ->icon(Heroicon::OutlinedArrowPath)
            ->visible(fn () => $this->record->status === ProviderStatus::Suspended && $this->canVerify())
            ->schema([Textarea::make('reason')->label('How the issue was resolved')->required()->rows(3)->maxLength(1000)])
            ->action(fn (array $data) => $this->moveTo(ProviderStatus::UnderReview, $data['reason'], 'Provider returned to review'));
    }

    private function checkCredentialAction(): Action
    {
        return Action::make('checkCredential')
            ->label('Record a check')
            ->icon(Heroicon::OutlinedShieldCheck)
            ->color('gray')
            ->visible(fn () => $this->canVerify() && $this->record->credentials()->exists())
            ->modalHeading('Record a credential check')
            ->modalDescription('Check the document or number against the issuing authority, then record the result.')
            ->schema([
                Select::make('credential_id')->label('Credential')->required()
                    ->options(fn () => $this->record->credentials()->with('type')->get()->mapWithKeys(fn ($c) => [
                        $c->id => $c->type->name_en.' — '.__('status.credential_'.$c->status->value, [], 'en'),
                    ])),
                Radio::make('result')->required()->inline()->live()
                    ->options(['verified' => 'Checked and valid', 'rejected' => 'Not valid']),
                Select::make('method')->label('How it was checked')->required()
                    ->options([
                        'document_review' => 'Document reviewed',
                        'register_lookup' => 'Checked on the authority\'s register',
                        'phone_call' => 'Confirmed by phone',
                    ])->default('document_review'),
                Textarea::make('notes')->label(fn (Get $get) => $get('result') === 'rejected' ? 'Reason (shown to the provider)' : 'Notes (optional)')
                    ->required(fn (Get $get) => $get('result') === 'rejected')->rows(3)->maxLength(500),
            ])
            ->action(function (array $data, CredentialReviewService $reviews) {
                $credential = $this->record->credentials()->findOrFail($data['credential_id']);

                $data['result'] === CredentialStatus::Verified->value
                    ? $reviews->verify($credential, $data['method'], $data['notes'] ?? null)
                    : $reviews->reject($credential, $data['notes']);

                $this->refreshRecord();
                Notification::make()->success()->title('Check recorded')->send();
            });
    }

    private function showIdAction(): Action
    {
        return Action::make('showId')
            ->label('Show full ID number')
            ->icon(Heroicon::OutlinedEye)
            ->visible(fn () => $this->canVerify() && $this->record->idCredential() !== null)
            ->requiresConfirmation()
            ->modalHeading('Show the full ID number?')
            ->modalDescription('Only do this to check it against the issuing authority. Viewing it is recorded in the audit log.')
            ->action(function () {
                $credential = $this->record->idCredential();

                activity('verification')->performedOn($credential)->causedBy(auth()->user())->event('id_number_viewed')->log('Viewed full ID number');

                Notification::make()->title($credential->type->name_en)
                    ->body($credential->credential_number)->persistent()->info()->send();
            });
    }

    private function markEditsReviewedAction(): Action
    {
        return Action::make('editsReviewed')
            ->label('Mark text edits reviewed')
            ->icon(Heroicon::OutlinedCheck)
            ->visible(fn () => $this->record->unreviewed_edits_at !== null && auth()->user()->can('update', $this->record))
            ->requiresConfirmation()
            ->action(function () {
                $this->record->forceFill(['unreviewed_edits_at' => null])->save();
                activity('providers')->performedOn($this->record)->causedBy(auth()->user())->event('edits_reviewed')->log('Provider text edits reviewed');
                Notification::make()->success()->title('Edits marked reviewed')->send();
            });
    }

    private function markTamilReviewedAction(): Action
    {
        return Action::make('tamilReviewed')
            ->label('Approve Tamil description')
            ->icon(Heroicon::OutlinedLanguage)
            ->visible(fn () => $this->record->description_ta_status === 'pending_review' && filled($this->record->description_ta) && auth()->user()->can('update', $this->record))
            ->requiresConfirmation()
            ->modalDescription('The Tamil description will be shown on the Tamil profile page.')
            ->action(function () {
                $this->record->forceFill([
                    'description_ta_status' => 'reviewed',
                    'description_ta_reviewed_by' => auth()->id(),
                    'description_ta_reviewed_at' => now(),
                ])->save();
                Notification::make()->success()->title('Tamil description approved')->send();
            });
    }

    private function moveTo(ProviderStatus $to, ?string $reason, string $done): void
    {
        abort_unless($this->canVerify(), 403);

        try {
            app(LifecycleService::class)->transition($this->record, $to, $reason ? trim($reason) : null);
        } catch (TransitionNotAllowed $e) {
            Notification::make()->danger()->title($e->getMessage())
                ->body($e->failures ? 'Still missing: '.implode('; ', $e->failures).'.' : null)
                ->persistent()->send();

            return;
        }

        $this->refreshRecord();
        Notification::make()->success()->title($done)->send();
    }

    private function refreshRecord(): void
    {
        $this->record = Provider::with(['credentials.type', 'credentials.document', 'events'])->find($this->record->id);
    }
}
