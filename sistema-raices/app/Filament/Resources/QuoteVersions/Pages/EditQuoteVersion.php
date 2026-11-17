<?php

namespace App\Filament\Resources\QuoteVersions\Pages;

use App\Filament\Resources\QuoteVersions\QuoteVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuoteVersion extends EditRecord
{
    protected static string $resource = QuoteVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->label('Eliminar')];
    }
}
