<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationGroup = 'Scheduling';

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Service details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Scheduling & pricing')
                    ->schema([
                        Forms\Components\TextInput::make('duration_minutes')
                            ->label('Duration (minutes)')
                            ->numeric()
                            ->minValue(5)
                            ->step(5)
                            ->required(),
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'GBP' => 'GBP',
                                'EUR' => 'EUR',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('price_cents')
                            ->label('Base price')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->suffix(fn (Get $get) => $get('currency'))
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format(((int) $state) / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn (?string $state) => (int) round(((float) ($state ?? 0)) * 100)),
                        Forms\Components\TextInput::make('deposit_cents')
                            ->label('Deposit (optional)')
                            ->numeric()
                            ->step(0.01)
                            ->suffix(fn (Get $get) => $get('currency'))
                            ->nullable()
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format(((int) $state) / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn (?string $state) => $state === null || $state === '' ? null : (int) round(((float) $state) * 100)),
                        Forms\Components\TextInput::make('buffer_before_minutes')
                            ->label('Buffer before (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->step(5)
                            ->default(0),
                        Forms\Components\TextInput::make('buffer_after_minutes')
                            ->label('Buffer after (minutes)')
                            ->numeric()
                            ->minValue(0)
                            ->step(5)
                            ->default(0),
                    ])->columns(2),
                Forms\Components\Toggle::make('is_active')
                    ->label('Service is bookable')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Price')
                    ->formatStateUsing(fn ($state, Service $record) => number_format($record->price_cents / 100, 2) . ' ' . $record->currency)
                    ->sortable(),
                Tables\Columns\TextColumn::make('deposit_cents')
                    ->label('Deposit')
                    ->formatStateUsing(fn ($state, Service $record) => $record->deposit_cents !== null
                        ? number_format($record->deposit_cents / 100, 2) . ' ' . $record->currency
                        : '—'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
