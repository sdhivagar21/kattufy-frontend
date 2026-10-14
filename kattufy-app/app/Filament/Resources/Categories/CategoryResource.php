<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Modules\Directory\Models\Category;
use App\Modules\Directory\Services\CategoryGateService;
use App\Modules\Geography\Models\City;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;

/**
 * Category names and descriptions, and the Live / Coming soon state per city.
 * A pair goes Live by itself at the configured number of published providers;
 * staff can hold it at Coming soon.
 */
class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Directory';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Names')->columns(2)->schema([
                TextInput::make('name_en')->label('Name (English)')->required()->maxLength(150),
                TextInput::make('name_ta')->label('Name (Tamil)')->maxLength(150),
                TextInput::make('slug')->disabled()->helperText('The URL segment. Changing it would break links, so it is fixed here.'),
                TextInput::make('sort_order')->numeric()->minValue(0)->required(),
                Toggle::make('is_active')->label('Shown on the site'),
            ]),
            Section::make('Descriptions')->columns(2)->schema([
                Textarea::make('description_en')->label('Description (English)')->rows(4),
                Textarea::make('description_ta')->label('Description (Tamil)')->rows(4),
                TextInput::make('meta_description_en')->label('Search snippet (English)')->maxLength(170),
                TextInput::make('meta_description_ta')->label('Search snippet (Tamil)')->maxLength(170),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $city = City::launch();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['group', 'cities' => fn ($q) => $q->where('cities.id', $city?->id)]))
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name_en')->label('Category')->weight('semibold')->searchable()
                    ->description(fn (Category $record) => $record->name_ta),
                TextColumn::make('group.name_en')->label('Group'),
                TextColumn::make('live')->label('In '.($city?->name_en ?? 'launch city'))->badge()
                    ->state(fn (Category $record) => $record->cities->first()?->pivot->status === 'live' ? 'Live' : 'Coming soon')
                    ->color(fn ($state) => $state === 'Live' ? 'success' : 'gray'),
                TextColumn::make('published')->label('Published providers')->numeric()
                    ->state(fn (Category $record) => $record->cities->first()?->pivot->published_provider_count ?? 0),
                IconColumn::make('held')->label('Held')->boolean()
                    ->state(fn (Category $record) => (bool) $record->cities->first()?->pivot->is_held),
                IconColumn::make('is_active')->label('Shown')->boolean(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('hold')
                    ->label(fn (Category $record) => $record->cities->first()?->pivot->is_held ? 'Release hold' : 'Hold at Coming soon')
                    ->icon(Heroicon::OutlinedPauseCircle)
                    ->color('gray')
                    ->schema(fn (Category $record) => $record->cities->first()?->pivot->is_held ? [] : [
                        Textarea::make('reason')->label('Why hold it')->required()->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->action(function (Category $record, array $data) use ($city) {
                        $held = (bool) $record->cities->first()?->pivot->is_held;

                        DB::table('category_city_status')->updateOrInsert(
                            ['category_id' => $record->id, 'city_id' => $city->id],
                            ['is_held' => ! $held, 'hold_reason' => $held ? null : $data['reason'], 'updated_at' => now(), 'updated_by' => auth()->id()],
                        );
                        app(CategoryGateService::class)->refresh($record, $city);

                        activity('directory')->performedOn($record)->causedBy(auth()->user())->event($held ? 'hold_released' : 'held')
                            ->withProperties(['reason' => $data['reason'] ?? null])->log($held ? 'Category hold released' : 'Category held at Coming soon');

                        Notification::make()->success()->title($held ? 'Hold released' : 'Held at Coming soon')->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
