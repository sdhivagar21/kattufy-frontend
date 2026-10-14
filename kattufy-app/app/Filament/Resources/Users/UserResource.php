<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Modules\Accounts\Enums\Role;
use App\Support\Mask;
use App\Support\Phone;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

/**
 * Accounts and staff (Super Admin only). Customer and provider roles are
 * chosen by the account holder; here staff roles are granted and accounts can
 * be blocked. There are no passwords.
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Accounts and staff';

    protected static ?string $modelLabel = 'account';

    protected static ?int $navigationSort = 1;

    public static function staffRoleOptions(): array
    {
        return collect(Role::staff())->mapWithKeys(fn ($r) => [$r => Role::from($r)->label()])->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(150),
                TextInput::make('phone')->label('Mobile number')->required()->tel()
                    ->helperText('10-digit number. Staff sign in with this number and a code.')
                    ->formatStateUsing(fn ($state) => $state ? Phone::national($state) : null)
                    ->dehydrateStateUsing(fn ($state) => Phone::normalize($state))
                    ->rule('regex:/^(\+91)?[6-9]\d{9}$/')
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, $state) => $rule)
                    ->disabledOn('edit'),
                TextInput::make('email')->label('Email address')->email()->required()->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('Staff receive the second sign-in code here.'),
                Select::make('status')->options(['active' => 'Active', 'blocked' => 'Blocked'])->required()->default('active'),
                Select::make('staff_roles')->label('Staff role')
                    ->options(self::staffRoleOptions())
                    ->multiple()
                    ->helperText('Leave empty for customer and provider accounts.')
                    ->afterStateHydrated(fn ($component, ?User $record) => $component->state($record?->roles->pluck('name')->intersect(Role::staff())->values()->all() ?? []))
                    ->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold')->placeholder('—'),
                TextColumn::make('phone')->label('Mobile')->formatStateUsing(fn ($state) => Phone::mask($state))->searchable(),
                TextColumn::make('email')->formatStateUsing(fn ($state) => Mask::email($state))->searchable(),
                TextColumn::make('roles.name')->label('Role')->badge()
                    ->formatStateUsing(fn ($state) => Role::tryFrom($state)?->label() ?? $state)
                    ->placeholder('Not chosen'),
                TextColumn::make('status')->badge()->color(fn ($state) => $state === 'active' ? 'success' : 'danger'),
                TextColumn::make('phone_verified_at')->label('Mobile verified')->date('j M Y')->placeholder('Not verified'),
                TextColumn::make('last_login_at')->label('Last login')->since('Asia/Kolkata')->placeholder('Never')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->relationship('roles', 'name')->label('Role'),
                SelectFilter::make('status')->options(['active' => 'Active', 'blocked' => 'Blocked']),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
