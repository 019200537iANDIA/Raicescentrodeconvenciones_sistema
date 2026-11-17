<?php

namespace App\Filament\Resources\ServiceRates;

use App\Filament\Resources\ServiceRates\Pages\CreateServiceRate;
use App\Filament\Resources\ServiceRates\Pages\EditServiceRate;
use App\Filament\Resources\ServiceRates\Pages\ListServiceRates;
use App\Models\ServiceRate;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceRateResource extends Resource
{
    protected static ?string $model = ServiceRate::class;
    protected static ?string $modelLabel = 'tarifa de servicio';
    protected static ?string $pluralModelLabel = 'tarifas de servicios';
    protected static ?string $navigationLabel = 'Tarifas';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogo';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('service_id')->label('Servicio')->relationship('service', 'name')->searchable()->preload()->required(),
            Select::make('package_id')->label('Solo para el paquete')->relationship('package', 'name')->searchable()->preload(),
            Select::make('event_type_id')->label('Solo para el tipo de evento')->relationship('eventType', 'name')->searchable()->preload(),
            TextInput::make('amount')->label('Precio')->numeric()->minValue(0)->required(),
            TextInput::make('currency_code')->label('Moneda (código de 3 letras)')->length(3)->required(),
            DatePicker::make('valid_from')->label('Válida desde')->native(false)->required(),
            DatePicker::make('valid_until')->label('Válida hasta')->native(false)->afterOrEqual('valid_from'),
            Select::make('approved_by')->label('Aprobada por')->relationship('approver', 'name')->searchable()->preload(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('service.name')->label('Servicio')->searchable()->sortable(),
            TextColumn::make('package.name')->label('Paquete')->placeholder('Tarifa general'),
            TextColumn::make('eventType.name')->label('Tipo de evento')->placeholder('Todos'),
            TextColumn::make('amount')->label('Precio')->money(fn (ServiceRate $record): string => $record->currency_code),
            TextColumn::make('valid_from')->label('Desde')->date('d/m/Y')->sortable(),
            TextColumn::make('valid_until')->label('Hasta')->date('d/m/Y')->placeholder('Sin vencimiento'),
            TextColumn::make('approver.name')->label('Aprobada por')->placeholder('Sin registrar'),
        ])->recordActions([
            EditAction::make()->label('Editar'),
            DeleteAction::make()->label('Eliminar'),
        ])->defaultSort('valid_from', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServiceRates::route('/'),
            'create' => CreateServiceRate::route('/create'),
            'edit' => EditServiceRate::route('/{record}/edit'),
        ];
    }
}
