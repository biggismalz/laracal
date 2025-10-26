<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvailabilityOverrideResource\Pages;
use App\Models\AvailabilityOverride;
use App\Enums\AvailabilityOverrideType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use DateTimeZone;

class AvailabilityOverrideResource extends Resource
{
    protected static ?string $model = AvailabilityOverride::class;

    protected static ?string $navigationGroup = 'Scheduling';

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('service_id')
                    ->relationship('service', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Service (optional)')
                    ->helperText('Leave blank to affect all services.'),
                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\Select::make('type')
                    ->options(self::typeOptions())
                    ->default(AvailabilityOverrideType::Closed->value)
                    ->required()
                    ->reactive(),
                Forms\Components\TimePicker::make('start_time')
                    ->seconds(false)
                    ->visible(fn (Get $get) => $get('type') === AvailabilityOverrideType::Open->value)
                    ->required(fn (Get $get) => $get('type') === AvailabilityOverrideType::Open->value),
                Forms\Components\TimePicker::make('end_time')
                    ->seconds(false)
                    ->visible(fn (Get $get) => $get('type') === AvailabilityOverrideType::Open->value)
                    ->required(fn (Get $get) => $get('type') === AvailabilityOverrideType::Open->value)
                    ->after('start_time'),
                Forms\Components\Select::make('timezone')
                    ->options(self::timezoneOptions())
                    ->searchable()
                    ->default(config('app.timezone'))
                    ->nullable()
                    ->label('Timezone override')
                    ->helperText('Leave empty to use the default timezone.'),
                Forms\Components\Textarea::make('notes')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->badge()
                    ->placeholder('All services'),
                Tables\Columns\TextColumn::make('date')
                    ->date(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (AvailabilityOverride $record) => ucfirst($record->type->value)),
                Tables\Columns\TextColumn::make('start_time')
                    ->time('H:i')
                    ->label('Start')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('end_time')
                    ->time('H:i')
                    ->label('End')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('timezone')
                    ->label('Timezone')
                    ->badge()
                    ->placeholder(config('app.timezone')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_id')
                    ->label('Service')
                    ->relationship('service', 'name'),
                Tables\Filters\SelectFilter::make('type')
                    ->options(self::typeOptions()),
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
            'index' => Pages\ListAvailabilityOverrides::route('/'),
            'create' => Pages\CreateAvailabilityOverride::route('/create'),
            'edit' => Pages\EditAvailabilityOverride::route('/{record}/edit'),
        ];
    }

    protected static function typeOptions(): array
    {
        return [
            AvailabilityOverrideType::Open->value => 'Open availability',
            AvailabilityOverrideType::Closed->value => 'Closed / blocked',
        ];
    }

    protected static function timezoneOptions(): array
    {
        $timezones = DateTimeZone::listIdentifiers();

        return array_combine($timezones, $timezones);
    }
}
