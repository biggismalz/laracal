<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        [$pending, $confirmed] = $this->bookingCounts();

        $upcomingCount = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereBetween('scheduled_start', [now(), now()->addWeek()])
            ->count();

        [$currentRevenue, $previousRevenue] = $this->revenueByMonth();

        return [
            Stat::make('Pending payment', $pending)
                ->description('Awaiting Stripe confirmation')
                ->color($pending > 5 ? 'warning' : 'success'),

            Stat::make('Confirmed', $confirmed)
                ->description('Paid and scheduled bookings'),

            Stat::make('Upcoming 7 days', $upcomingCount)
                ->description('Confirmed appointments'),

            Stat::make('Revenue (this month)', $this->formatCurrency($currentRevenue))
                ->description('Last month: ' . $this->formatCurrency($previousRevenue)),
        ];
    }

    protected function bookingCounts(): array
    {
        return [
            Booking::query()->where('status', BookingStatus::PendingPayment)->count(),
            Booking::query()->where('status', BookingStatus::Confirmed)->count(),
        ];
    }

    protected function revenueByMonth(): array
    {
        $now = CarbonImmutable::now();

        $current = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereBetween('paid_at', [$now->startOfMonth(), $now->endOfMonth()])
            ->sum('amount_paid_cents') / 100;

        $previous = Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->whereBetween('paid_at', [$now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth()])
            ->sum('amount_paid_cents') / 100;

        return [$current, $previous];
    }

    protected function formatCurrency(float $amount, string $currency = 'GBP'): string
    {
        return number_format($amount, 2) . ' ' . strtoupper($currency);
    }
}
