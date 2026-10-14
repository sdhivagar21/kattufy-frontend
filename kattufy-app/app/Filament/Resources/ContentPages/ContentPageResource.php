<?php

namespace App\Filament\Resources\ContentPages;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Modules\Content\Models\ContentPage;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/** Guides, legal pages and the ranking explanation (Markdown, both languages). */
class ContentPageResource extends Resource
{
    protected static ?string $model = ContentPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Content pages';

    protected static ?string $recordTitleAttribute = 'title_en';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page')->columns(['default' => 1, 'md' => 4])->schema([
                TextInput::make('slug')->required()->maxLength(150)->alphaDash()->unique(ignoreRecord: true),
                Select::make('page_type')->label('Type')->options(ContentPage::TYPES)->required(),
                Select::make('status')->options(['draft' => 'Draft', 'published' => 'Published'])->required()->default('draft'),
                Select::make('ta_status')->label('Tamil version')
                    ->options(['empty' => 'Empty', 'draft' => 'Draft', 'reviewed' => 'Reviewed — shown'])->required()->default('empty')
                    ->helperText('Tamil is shown only once reviewed.'),
            ]),
            Tabs::make('Languages')->columnSpanFull()->tabs([
                Tab::make('English')->schema([
                    TextInput::make('title_en')->label('Title')->required()->maxLength(200),
                    TextInput::make('meta_description_en')->label('Search snippet')->maxLength(170),
                    MarkdownEditor::make('body_en')->label('Body')->toolbarButtons(['heading', 'bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo']),
                ]),
                Tab::make('தமிழ்')->schema([
                    TextInput::make('title_ta')->label('Title (Tamil)')->maxLength(200),
                    TextInput::make('meta_description_ta')->label('Search snippet (Tamil)')->maxLength(170),
                    MarkdownEditor::make('body_ta')->label('Body (Tamil)')->toolbarButtons(['heading', 'bold', 'italic', 'bulletList', 'orderedList', 'link', 'undo', 'redo']),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title_en')->label('Title')->searchable()->weight('semibold')->description(fn (ContentPage $record) => '/'.$record->slug.'/'),
                TextColumn::make('page_type')->label('Type')->formatStateUsing(fn ($state) => ContentPage::TYPES[$state] ?? $state)->badge()->color('gray'),
                TextColumn::make('status')->badge()->color(fn ($state) => $state === 'published' ? 'success' : 'gray'),
                TextColumn::make('ta_status')->label('Tamil')->badge()->color(fn ($state) => $state === 'reviewed' ? 'success' : 'warning'),
                TextColumn::make('updated_at')->label('Updated')->since('Asia/Kolkata'),
            ])
            ->filters([SelectFilter::make('page_type')->options(ContentPage::TYPES)])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentPages::route('/'),
            'create' => CreateContentPage::route('/create'),
            'edit' => EditContentPage::route('/{record}/edit'),
        ];
    }
}
