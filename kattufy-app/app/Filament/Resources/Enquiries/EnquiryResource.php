<?php

namespace App\Filament\Resources\Enquiries;

use App\Filament\Resources\Enquiries\Pages\EditEnquiry;
use App\Filament\Resources\Enquiries\Pages\ListEnquiries;
use App\Filament\Resources\Enquiries\Pages\ViewEnquiry;
use App\Models\User;
use App\Modules\Accounts\Enums\Role;
use App\Modules\Enquiries\Models\Enquiry;
use App\Modules\Providers\Models\Provider;
use App\Support\Phone;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Fallback enquiries: staff assign them, match a provider by hand and record the outcome. */
class EnquiryResource extends Resource
{
    protected static ?string $model = Enquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference_no';

    public static function getNavigationBadge(): ?string
    {
        $new = Enquiry::where('status', 'new')->count();

        return $new ? (string) $new : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Handling')->columns(2)->schema([
                Select::make('status')->options(Enquiry::STATUSES)->required(),
                Select::make('assigned_to')->label('Assigned to')
                    ->options(fn () => User::role(Role::staff())->pluck('name', 'id')),
                Select::make('matched_provider_id')->label('Matched provider')
                    ->options(fn () => Provider::published()->orderBy('business_name_en')->pluck('business_name_en', 'id'))
                    ->searchable(),
                TextInput::make('outcome')->maxLength(30)->helperText('Short result, e.g. "provider called", "no match".'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Enquiry')->columns(['default' => 1, 'md' => 3])->schema([
                TextEntry::make('reference_no')->label('Reference')->copyable(),
                TextEntry::make('status')->badge()->formatStateUsing(fn ($state) => Enquiry::STATUSES[$state] ?? $state),
                TextEntry::make('created_at')->label('Received')->dateTime('j M Y, g:i a', 'Asia/Kolkata'),
                TextEntry::make('name'),
                TextEntry::make('phone')->label('Verified mobile')->formatStateUsing(fn ($state) => Phone::display($state)),
                TextEntry::make('locale')->label('Language')->formatStateUsing(fn ($state) => $state === 'ta' ? 'Tamil' : 'English'),
                TextEntry::make('category.name_en')->label('Service')->placeholder('—'),
                TextEntry::make('locality.name_en')->label('Area')->placeholder('—'),
                TextEntry::make('source')->formatStateUsing(fn ($state) => str($state)->replace('_', ' ')->ucfirst()),
                TextEntry::make('budget')->placeholder('—'),
                TextEntry::make('preferred_date')->date('j M Y')->placeholder('—'),
                TextEntry::make('message')->columnSpanFull()->prose(),
            ]),
            Section::make('Handling')->columns(['default' => 1, 'md' => 3])->schema([
                TextEntry::make('assignee.name')->label('Assigned to')->placeholder('Unassigned'),
                TextEntry::make('matchedProvider.business_name_en')->label('Matched provider')->placeholder('—'),
                TextEntry::make('outcome')->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['category', 'locality']))
            ->columns([
                TextColumn::make('reference_no')->label('Reference')->searchable(),
                TextColumn::make('name')->searchable()->description(fn (Enquiry $record) => Phone::mask($record->phone)),
                TextColumn::make('category.name_en')->label('Service')->placeholder('—'),
                TextColumn::make('locality.name_en')->label('Area')->placeholder('—'),
                TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => Enquiry::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) { 'new' => 'warning', 'closed' => 'gray', default => 'success' }),
                TextColumn::make('created_at')->label('Received')->since('Asia/Kolkata')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(Enquiry::STATUSES),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()->label('Handle')])
            ->emptyStateHeading('No enquiries yet')
            ->emptyStateDescription('Customers send enquiries when a search has no results or a category is Coming soon.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEnquiries::route('/'),
            'view' => ViewEnquiry::route('/{record}'),
            'edit' => EditEnquiry::route('/{record}/edit'),
        ];
    }
}
