<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

/** Read-only audit log (table audit_logs): who changed what, when, before and after. */
class AuditLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Audit log';

    protected static ?string $modelLabel = 'audit entry';

    protected static ?int $navigationSort = 2;

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->label('When')->dateTime('j M Y, g:i:s a', 'Asia/Kolkata'),
            TextEntry::make('causer.name')->label('Who')->placeholder('System'),
            TextEntry::make('log_name')->label('Area'),
            TextEntry::make('event')->placeholder('—'),
            TextEntry::make('description')->columnSpanFull(),
            TextEntry::make('subject_type')->label('Record')->formatStateUsing(fn ($state, Activity $record) => $state ? str($state)->headline().' #'.$record->subject_id : '—'),
            KeyValueEntry::make('properties')->label('Details')->columnSpanFull()
                ->state(fn (Activity $record) => collect($record->properties->toArray())->map(fn ($v) => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v)->all()),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('causer'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('When')->dateTime('j M Y, g:i a', 'Asia/Kolkata')->sortable(),
                TextColumn::make('causer.name')->label('Who')->placeholder('System'),
                TextColumn::make('log_name')->label('Area')->badge()->color('gray'),
                TextColumn::make('description')->wrap()->searchable(),
                TextColumn::make('subject_type')->label('Record')->formatStateUsing(fn ($state, Activity $record) => $state ? str($state)->headline().' #'.$record->subject_id : '—'),
            ])
            ->filters([
                SelectFilter::make('log_name')->label('Area')->options(fn () => Activity::query()->distinct()->pluck('log_name', 'log_name')->filter()->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
