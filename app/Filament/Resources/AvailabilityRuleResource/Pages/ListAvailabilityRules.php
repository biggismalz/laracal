<?php

namespace App\Filament\Resources\AvailabilityRuleResource\Pages;

use App\Filament\Resources\AvailabilityRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAvailabilityRules extends ListRecords
{
    protected static string $resource = AvailabilityRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
