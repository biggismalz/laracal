<?php

namespace App\Filament\Resources\AvailabilityOverrideResource\Pages;

use App\Filament\Resources\AvailabilityOverrideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAvailabilityOverrides extends ListRecords
{
    protected static string $resource = AvailabilityOverrideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
