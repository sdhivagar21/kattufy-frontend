<?php

namespace App\Filament\Resources\Providers;

use App\Filament\Resources\Providers\Pages\EditProvider;
use App\Filament\Resources\Providers\Pages\ListProviders;
use App\Filament\Resources\Providers\Pages\ViewProvider;
use App\Modules\Directory\Models\ProviderSubtype;
use App\Modules\Providers\Enums\ProviderStatus;
use App\Modules\Providers\Models\Provider;
use App\Modules\Verification\Models\Credential;
use App\Support\Mask;
use App\Support\Phone;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Provider submissions and listings. Status is never edited as a field: it
 * changes only through the review actions on the view page, which call
 * LifecycleService.
 */
class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Verification';

    protected static ?string $navigationLabel = 'Providers';

    protected static ?string $modelLabel = 'provider';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'business_name_en';

    public static function reference(Provider $provider): string
    {
        return 'KTF-P-'.str_pad((string) $provider->id, 4, '0', STR_PAD_LEFT);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Provider::where('status', ProviderStatus::UnderReview->value)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Waiting for review';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['business_name_en', 'business_name_ta', 'slug'];
    }

    // ----- Listing details staff complete before publishing ------------------

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Listing details')
                ->description('What the public profile shows once published. Business details and documents come from the provider\'s own submission.')
                ->columns(2)
                ->schema([
                    Select::make('provider_type_id')->label('Provider type')
                        ->relationship('type', 'name_en')->preload()->live()
                        ->afterStateUpdated(fn ($set) => $set('provider_subtype_id', null)),
                    Select::make('provider_subtype_id')->label('Subtype')
                        ->options(fn (Get $get) => ProviderSubtype::where('provider_type_id', $get('provider_type_id'))->pluck('name_en', 'id'))
                        ->disabled(fn (Get $get) => blank($get('provider_type_id'))),
                    Select::make('zones')->label('Service zones')
                        ->relationship('zones', 'name_en')->multiple()->preload()
                        ->helperText('Customers in any locality of these zones will find the provider.'),
                    Select::make('services')->label('Specialisations')
                        ->relationship('services', 'name_en', fn (Builder $query, ?Model $record) => $query->whereIn('category_id', $record?->categories->pluck('id') ?? []))
                        ->multiple()->preload(),
                    TextInput::make('established_year')->label('Established (year)')->numeric()->minValue(1900)->maxValue((int) date('Y')),
                    Select::make('portfolio_status')->label('Portfolio status')
                        ->options(['not_recorded' => 'Not recorded', 'none' => 'No portfolio yet', 'shown' => 'Portfolio shown'])->required(),
                ]),
            Section::make('Description')
                ->columns(2)
                ->schema([
                    Textarea::make('description_en')->label('Description (English)')->rows(5)->maxLength(2000),
                    Textarea::make('description_ta')->label('Description (Tamil)')->rows(5)->maxLength(2000),
                    Select::make('description_ta_status')->label('Tamil description')
                        ->options(['empty' => 'Empty', 'draft' => 'Draft', 'pending_review' => 'Waiting for review', 'reviewed' => 'Reviewed — shown publicly'])
                        ->helperText('Tamil text appears on the profile only once marked Reviewed.')->required(),
                ]),
            Section::make('Contact options')
                ->description('Numbers are never printed on pages; customers reveal them after logging in.')
                ->columns(2)
                ->schema([
                    Toggle::make('allow_call')->label('Show "Call"'),
                    Toggle::make('allow_whatsapp')->label('Show "WhatsApp"'),
                    Toggle::make('allow_enquiry')->label('Accept enquiries'),
                    Toggle::make('show_address')->label('Show street address on profile'),
                    TextInput::make('whatsapp_number')->label('WhatsApp number (if different)')->tel()->maxLength(16)
                        ->dehydrateStateUsing(fn ($state) => Phone::normalize($state)),
                ]),
        ]);
    }

    // ----- Review view -------------------------------------------------------

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(['default' => 1, 'lg' => 3])->schema([
                Section::make('Review')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextEntry::make('business_name_en')->label('Business name')->weight('bold')->size('lg'),
                        TextEntry::make('status')->badge()
                            ->formatStateUsing(fn (ProviderStatus $state) => $state->label())
                            ->color(fn (ProviderStatus $state) => $state->color()),
                        TextEntry::make('reference')->label('Reference')->state(fn (Provider $record) => self::reference($record))->copyable(),
                        TextEntry::make('provider_view')->label('Provider sees')->state(fn (Provider $record) => __('status.'.$record->verificationState()->value, [], 'en')),
                        TextEntry::make('submitted_at')->label('Submitted')->state(fn (Provider $record) => $record->submittedAt())->dateTime('j M Y, g:i a', 'Asia/Kolkata')->placeholder('Not submitted yet'),
                        TextEntry::make('status_changed_at')->label('Status changed')->dateTime('j M Y, g:i a', 'Asia/Kolkata')->placeholder('—'),
                        TextEntry::make('reviewer.name')->label('Approved by')->placeholder('—'),
                        TextEntry::make('feedback')->label('Latest review note')
                            ->state(fn (Provider $record) => $record->latestLifecycleEvent()?->reason)->placeholder('No note')->columnSpanFull(),
                        TextEntry::make('unreviewed_edits_at')->label('Unreviewed text edits')->dateTime('j M Y, g:i a', 'Asia/Kolkata')
                            ->visible(fn (Provider $record) => $record->unreviewed_edits_at !== null)->color('warning'),
                    ]),
                Section::make('Publish checklist')
                    ->description('Every item must pass before the profile can be published.')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        ViewEntry::make('checklist')->hiddenLabel()->view('filament.provider-checklist'),
                    ]),
            ]),

            Section::make('Business details')
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    TextEntry::make('categories.name_en')->label('Category')->badge(),
                    TextEntry::make('address')->label('Business address')->placeholder('—'),
                    TextEntry::make('pincode')->label('PIN code')->placeholder('—'),
                    TextEntry::make('locality.name_en')->label('Locality (from PIN code)')->placeholder('PIN code not in the locality list'),
                    TextEntry::make('phone')->label('Business mobile')->formatStateUsing(fn ($state) => Phone::display($state))->placeholder('—'),
                    IconEntry::make('phone_verified')->label('Business mobile verified by OTP')->boolean()->state(fn (Provider $record) => $record->phone_verified_at !== null),
                    TextEntry::make('email')->label('Business email')->placeholder('—'),
                    TextEntry::make('user.name')->label('Account holder')->placeholder('Not claimed')
                        ->helperText(fn (Provider $record) => $record->user ? Phone::mask($record->user->phone).' · '.Mask::email($record->user->email) : null),
                    TextEntry::make('source')->label('Source')->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->ucfirst())->placeholder('—'),
                ]),

            Section::make('Identification and documents')
                ->description('ID numbers are shown masked. Opening a document creates a link that works for 5 minutes and is logged.')
                ->schema([
                    RepeatableEntry::make('credentials')->hiddenLabel()
                        ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                        ->schema([
                            TextEntry::make('type.name_en')->label('Credential')->weight('semibold'),
                            TextEntry::make('masked_number')->label('Number')
                                ->state(fn (Credential $record) => $record->credential_number ? $record->maskedNumber() : null)->placeholder('—')->fontFamily('mono'),
                            TextEntry::make('status')->badge()
                                ->formatStateUsing(fn ($state) => __('status.credential_'.$state->value, [], 'en'))
                                ->color(fn ($state) => $state->color()),
                            TextEntry::make('document')->label('Document')
                                ->state(fn (Credential $record) => $record->document ? $record->document->original_filename.' · '.$record->document->humanSize() : null)
                                ->url(fn (Credential $record) => $record->document && auth()->user()->can('viewDocuments', $record->provider) ? $record->document->temporaryUrl() : null, shouldOpenInNewTab: true)
                                ->icon(fn (Credential $record) => $record->document ? Heroicon::OutlinedDocumentText : null)
                                ->color('primary')->placeholder('No file'),
                            TextEntry::make('verified_at')->label('Checked')->dateTime('j M Y', 'Asia/Kolkata')->placeholder('Not yet'),
                            TextEntry::make('rejection_reason')->label('Reason')->placeholder('—')->visible(fn (?Credential $record) => filled($record?->rejection_reason)),
                        ]),
                ]),

            Section::make('Review history')
                ->collapsible()
                ->schema([
                    RepeatableEntry::make('events')->hiddenLabel()
                        ->columns(['default' => 1, 'md' => 4])
                        ->schema([
                            TextEntry::make('created_at')->label('When')->dateTime('j M Y, g:i a', 'Asia/Kolkata'),
                            TextEntry::make('what')->label('What')->state(fn ($record) => $record->to_status
                                ? ProviderStatus::from($record->from_status ?? 'found')->label().' → '.ProviderStatus::from($record->to_status)->label()
                                : 'Credential '.($record->result ?? 'checked').($record->credential?->type ? ' — '.$record->credential->type->name_en : '')),
                            TextEntry::make('author.name')->label('By')->placeholder('Provider / system'),
                            TextEntry::make('reason')->label('Note')->placeholder('—'),
                        ]),
                ]),
        ]);
    }

    // ----- Table -------------------------------------------------------------

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['categories', 'locality']))
            ->defaultSort('status_changed_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('Ref.')->formatStateUsing(fn ($state, Provider $record) => self::reference($record))->sortable()->toggleable(),
                TextColumn::make('business_name_en')->label('Business')->searchable(['business_name_en', 'business_name_ta'])->sortable()->weight('semibold')
                    ->description(fn (Provider $record) => $record->categories->pluck('name_en')->join(', ')),
                TextColumn::make('status')->badge()->sortable()
                    ->formatStateUsing(fn (ProviderStatus $state) => $state->label())
                    ->color(fn (ProviderStatus $state) => $state->color()),
                TextColumn::make('locality.name_en')->label('Locality')->placeholder('—')->toggleable(),
                TextColumn::make('phone')->label('Mobile')->formatStateUsing(fn ($state) => Phone::mask($state))->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('source')->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->ucfirst())->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('unreviewed_edits_at')->label('Edits')->tooltip('Text edits waiting for staff review')
                    ->state(fn (Provider $record) => $record->unreviewed_edits_at !== null)->boolean()
                    ->trueIcon(Heroicon::OutlinedPencilSquare)->falseIcon('')->trueColor('warning'),
                TextColumn::make('status_changed_at')->label('Updated')->since('Asia/Kolkata')->sortable()
                    ->dateTimeTooltip('j M Y, g:i a', 'Asia/Kolkata'),
            ])
            ->filters([
                SelectFilter::make('status')->options(collect(ProviderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
                SelectFilter::make('source')->options(['self_registration' => 'Self registration', 'sample_seed' => 'Sample data']),
                SelectFilter::make('categories')->relationship('categories', 'name_en')->label('Category'),
            ])
            ->recordActions([
                ViewAction::make()->label('Review'),
                EditAction::make()->label('Listing details'),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('No providers here')
            ->emptyStateDescription('Submissions appear when a provider submits details for verification.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProviders::route('/'),
            'view' => ViewProvider::route('/{record}'),
            'edit' => EditProvider::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
