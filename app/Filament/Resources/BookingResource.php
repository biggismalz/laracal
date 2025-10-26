<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use App\Enums\BookingStatus;
use App\Enums\PaymentOption;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationGroup = 'Scheduling';

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Booking')
                    ->schema([
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function (?int $state, Set $set, Get $get): void {
                                if (! $state) {
                                    return;
                                }

                                $service = Service::query()->find($state);

                                if (! $service) {
                                    return;
                                }

                                $set('currency', $service->currency);

                                $set('list_price_cents', number_format($service->price_cents / 100, 2, '.', ''));

                                $preferredOption = $service->deposit_cents !== null
                                    ? PaymentOption::Deposit->value
                                    : PaymentOption::Full->value;

                                $set('payment_option', $preferredOption);

                                $chargeCents = $preferredOption === PaymentOption::Deposit->value
                                    ? $service->deposit_cents
                                    : $service->price_cents;

                                $set('amount_charged_cents', number_format(($chargeCents ?? $service->price_cents) / 100, 2, '.', ''));
                                $set('amount_paid_cents', number_format(0, 2, '.', ''));

                                $start = $get('scheduled_start');

                                if ($start) {
                                    $end = Carbon::parse($start)->addMinutes($service->duration_minutes);
                                    $set('scheduled_end', $end->toDateTimeString());
                                }
                            })
                            ->helperText('Selecting a service pre-fills pricing and duration.'),
                        Forms\Components\DateTimePicker::make('scheduled_start')
                            ->seconds(false)
                            ->required()
                            ->label('Start')
                            ->reactive()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                if (! $state) {
                                    return;
                                }

                                $serviceId = $get('service_id');

                                if (! $serviceId) {
                                    return;
                                }

                                $service = Service::query()->find($serviceId);

                                if (! $service) {
                                    return;
                                }

                                $set('scheduled_end', Carbon::parse($state)->addMinutes($service->duration_minutes)->toDateTimeString());
                            }),
                        Forms\Components\DateTimePicker::make('scheduled_end')
                            ->seconds(false)
                            ->required()
                            ->label('End')
                            ->after('scheduled_start'),
                        Forms\Components\Select::make('status')
                            ->options(self::statusOptions())
                            ->required(),
                        Forms\Components\Select::make('payment_option')
                            ->label('Payment option')
                            ->options(self::paymentOptions())
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (?string $state, Set $set, Get $get): void {
                                $serviceId = $get('service_id');

                                if (! $serviceId) {
                                    return;
                                }

                                $service = Service::query()->find($serviceId);

                                if (! $service) {
                                    return;
                                }

                                if ($state === PaymentOption::Deposit->value) {
                                    if ($service->deposit_cents === null) {
                                        $set('payment_option', PaymentOption::Full->value);
                                        $set('amount_charged_cents', number_format($service->price_cents / 100, 2, '.', ''));

                                        return;
                                    }

                                    $set('amount_charged_cents', number_format($service->deposit_cents / 100, 2, '.', ''));

                                    return;
                                }

                                $set('amount_charged_cents', number_format($service->price_cents / 100, 2, '.', ''));
                            }),
                    ])->columns(2),
                Forms\Components\Section::make('Customer')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('customer_phone')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\Textarea::make('customer_notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('Payment')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->options([
                                'USD' => 'USD',
                                'GBP' => 'GBP',
                                'EUR' => 'EUR',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('list_price_cents')
                            ->label('List price')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->suffix(fn (Get $get) => $get('currency'))
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                if ($state === null) {
                                    return;
                                }

                                if (is_string($state) && str_contains($state, '.')) {
                                    return;
                                }

                                $component->state(number_format(((float) $state) / 100, 2, '.', ''));
                            })
                            ->dehydrateStateUsing(fn (?string $state) => $state === null || $state === '' ? 0 : (int) round(((float) $state) * 100)),
                        Forms\Components\TextInput::make('amount_charged_cents')
                            ->label('Amount to charge')
                            ->numeric()
                            ->step(0.01)
                            ->required()
                            ->suffix(fn (Get $get) => $get('currency'))
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                if ($state === null) {
                                    return;
                                }

                                if (is_string($state) && str_contains($state, '.')) {
                                    return;
                                }

                                $component->state(number_format(((float) $state) / 100, 2, '.', ''));
                            })
                            ->dehydrateStateUsing(fn (?string $state) => $state === null || $state === '' ? 0 : (int) round(((float) $state) * 100)),
                        Forms\Components\TextInput::make('amount_paid_cents')
                            ->label('Amount paid')
                            ->numeric()
                            ->step(0.01)
                            ->suffix(fn (Get $get) => $get('currency'))
                            ->afterStateHydrated(function (Forms\Components\TextInput $component, $state): void {
                                if ($state === null) {
                                    return;
                                }

                                if (is_string($state) && str_contains($state, '.')) {
                                    return;
                                }

                                $component->state(number_format(((float) $state) / 100, 2, '.', ''));
                            })
                            ->dehydrateStateUsing(fn (?string $state) => $state === null || $state === '' ? 0 : (int) round(((float) $state) * 100)),
                        Forms\Components\TextInput::make('stripe_checkout_session_id')
                            ->label('Stripe checkout session')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('stripe_payment_intent_id')
                            ->label('Stripe payment intent')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->seconds(false)
                            ->label('Paid at'),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->seconds(false)
                            ->label('Cancelled at')
                            ->visible(fn (Get $get) => $get('status') === BookingStatus::Cancelled->value),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->seconds(false)
                            ->label('Completed at')
                            ->visible(fn (Get $get) => $get('status') === BookingStatus::Completed->value),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scheduled_start')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (Booking $record) => ucwords(str_replace('_', ' ', $record->status->value)))
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid_cents')
                    ->label('Paid')
                    ->formatStateUsing(fn (Booking $record) => number_format($record->amount_paid_cents / 100, 2) . ' ' . $record->currency),
                Tables\Columns\TextColumn::make('payment_option')
                    ->formatStateUsing(fn (Booking $record) => ucfirst($record->payment_option->value)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(self::statusOptions()),
                Tables\Filters\SelectFilter::make('payment_option')
                    ->label('Payment option')
                    ->options(self::paymentOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->can('delete', Booking::class)),
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
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }

    protected static function statusOptions(): array
    {
        return collect(BookingStatus::cases())
            ->mapWithKeys(fn (BookingStatus $status) => [$status->value => ucwords(str_replace('_', ' ', $status->value))])
            ->toArray();
    }

    protected static function paymentOptions(): array
    {
        return collect(PaymentOption::cases())
            ->mapWithKeys(fn (PaymentOption $option) => [$option->value => ucfirst($option->value)])
            ->toArray();
    }
}
