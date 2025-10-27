<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvailabilityRuleResource\Pages;
use App\Models\AvailabilityRule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use DateTimeZone;

class AvailabilityRuleResource extends Resource
{
    protected static ?string $model = AvailabilityRule::class;

    protected static ?string $navigationGroup = 'Scheduling';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Service (optional)')
                    ->helperText('Leave blank to apply this rule to every active service.'),
                Forms\Components\Select::make('day_of_week')
                    ->options(self::dayOfWeekOptions())
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->seconds(false)
                    ->required(),
                Forms\Components\TimePicker::make('end_time')
                    ->seconds(false)
                    ->required()
                    ->after('start_time'),
                // Forms\Components\TextInput::make('capacity')
                //     ->numeric()
                //     ->minValue(1)
                //     ->default(1)
                //     ->required()
                //     ->helperText('Number of bookings allowed in this time range.'),
                Forms\Components\Select::make('timezone')
                    ->options(self::timezoneOptions())
                    ->searchable()
                    ->default(config('app.timezone'))
                    ->required()
                    ->helperText('Usually your business timezone.'),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->label('Available'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->placeholder('All services')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->formatStateUsing(fn (int $state) => self::dayOfWeekOptions()[$state] ?? $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('H:i'),
                // Tables\Columns\TextColumn::make('capacity')
                //     ->sortable(),
                Tables\Columns\TextColumn::make('timezone')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvailabilityRules::route('/'),
            'create' => Pages\CreateAvailabilityRule::route('/create'),
            'edit' => Pages\EditAvailabilityRule::route('/{record}/edit'),
        ];
    }

    protected static function dayOfWeekOptions(): array
    {
        return [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];
    }

    protected static function timezoneOptions(): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        return array_combine($timezones, $timezones);
    }
}
