<?php

namespace App\Filament\Resources\ServiceRates\Pages;

use App\Filament\Resources\ServiceRates\ServiceRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditServiceRate extends EditRecord
{
    protected static string $resource = ServiceRateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Eliminar')];
    }
}
