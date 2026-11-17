<?php

namespace App\Filament\Resources\QuoteVersions;

use App\Filament\Resources\QuoteVersions\Pages\CreateQuoteVersion;
use App\Filament\Resources\QuoteVersions\Pages\EditQuoteVersion;
use App\Filament\Resources\QuoteVersions\Pages\ListQuoteVersions;
use App\Models\QuoteVersion;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuoteVersionResource extends Resource
{
    protected static ?string $model = QuoteVersion::class;
    protected static ?string $modelLabel = 'detalle de cotización';
    protected static ?string $pluralModelLabel = 'detalles de cotizaciones';
    protected static ?string $navigationLabel = 'Conceptos y versiones';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string | \UnitEnum | null $navigationGroup = 'Comercial';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('quote_id')->label('Cotización')->relationship('quote', 'quote_number')->searchable()->preload()->required(),
            Select::make('package_id')->label('Paquete')->relationship('package', 'name')->searchable()->preload(),
            TextInput::make('currency_code')->label('Moneda (código de 3 letras)')->length(3)->required(),
            TextInput::make('advance_percent')->label('Adelanto (%)')->numeric()->minValue(10)->maxValue(30)->default(10)->required()
                ->helperText('La regla vigente permite entre 10% y 30%.'),
            Textarea::make('reason')->label('Motivo de esta versión o cambio')->rows(2)->columnSpanFull(),
            Repeater::make('lines')->label('Conceptos y servicios cotizados')->relationship('lines')
                ->schema([
                    Select::make('service_id')->label('Servicio del catálogo')->relationship('service', 'name')->searchable()->preload(),
                    TextInput::make('description_snapshot')->label('Descripción para esta cotización')->required()->maxLength(500),
                    TextInput::make('quantity')->label('Cantidad')->numeric()->minValue(0.01)->default(1)->required(),
                    TextInput::make('internal_unit_price')->label('Precio unitario interno')->numeric()->minValue(0)->required(),
                    TextInput::make('line_discount')->label('Descuento en este concepto')->numeric()->minValue(0)->default(0),
                    Toggle::make('display_quantity_to_customer')->label('Mostrar cantidad al cliente')->default(true),
                ])->columns(3)->columnSpanFull()->addActionLabel('Agregar concepto'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('quote.quote_number')->label('Cotización')->searchable()->sortable(),
            TextColumn::make('version_number')->label('Versión'),
            TextColumn::make('package.name')->label('Paquete')->placeholder('Personalizado'),
            TextColumn::make('total')->label('Total')->money(fn (QuoteVersion $record): string => $record->currency_code),
            TextColumn::make('advance_percent')->label('Adelanto %')->suffix('%'),
            TextColumn::make('advance_amount')->label('Adelanto')->money(fn (QuoteVersion $record): string => $record->currency_code),
            TextColumn::make('valid_until')->label('Vence')->dateTime('d/m/Y H:i')->sortable(),
            IconColumn::make('is_current')->label('Vigente')->boolean(),
        ])->recordActions([
            EditAction::make()->label('Editar conceptos'),
            DeleteAction::make()->label('Eliminar'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuoteVersions::route('/'),
            'create' => CreateQuoteVersion::route('/create'),
            'edit' => EditQuoteVersion::route('/{record}/edit'),
        ];
    }
}
