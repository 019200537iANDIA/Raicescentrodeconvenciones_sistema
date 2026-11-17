<?php

namespace App\Filament\Resources\ServiceRates\Pages;

use App\Filament\Resources\ServiceRates\ServiceRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListServiceRates extends ListRecords
{
    protected static string $resource = ServiceRateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva tarifa')];
    }
}
