<?php

namespace App\Filament\Resources\Quotes;

use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Models\EventType;
use App\Models\Quote;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;
    protected static ?string $modelLabel = 'cotización';
    protected static ?string $pluralModelLabel = 'cotizaciones';
    protected static ?string $navigationLabel = 'Cotizaciones';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static string | \UnitEnum | null $navigationGroup = 'Comercial';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('inquiry_id')->label('Consulta de origen')->relationship('inquiry', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record): string => $record->client?->full_name . ' · ' . $record->created_at?->format('d/m/Y'))
                ->searchable()->preload()->required(),
            Select::make('event_type_id')->label('Tipo de evento')->relationship('eventType', 'name')->searchable()->preload()->live()
                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                    self::distributeAttendees((int) ($get('total_attendees') ?: 100), $state, $set);
                }),
            DatePicker::make('event_date')->label('Fecha del evento')->native(false)->required(),
            Select::make('venue_configuration_id')->label('Configuración del local')
                ->relationship('venueConfiguration', 'name')->searchable()->preload(),
            TextInput::make('total_attendees')->label('Total de asistentes')->numeric()->minValue(1)->maxValue(500)->default(100)->required()->live()
                ->afterStateHydrated(function ($state, Get $get, Set $set): void {
                    if ($state === null) {
                        $set('total_attendees', (int) $get('adult_count') + (int) $get('child_count'));
                    }
                })
                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                    self::distributeAttendees((int) $state, $get('event_type_id'), $set);
                }),
            TextInput::make('adult_count')->label('Adultos')->numeric()->minValue(0)->default(70)->required()->live()
                ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total_attendees', (int) $state + (int) $get('child_count'))),
            TextInput::make('child_count')->label('Niños')->numeric()->minValue(0)->default(30)->required()->live()
                ->afterStateUpdated(fn ($state, Get $get, Set $set) => $set('total_attendees', (int) $get('adult_count') + (int) $state)),
            Select::make('quote_status')->label('Estado')->options([
                'borrador' => 'Borrador', 'enviada' => 'Enviada', 'aceptada' => 'Aceptada',
                'rechazada' => 'Rechazada', 'vencida' => 'Vencida', 'convertida' => 'Convertida en reserva', 'cancelada' => 'Cancelada',
            ])->default('borrador')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('quote_number')->label('N.º')->searchable()->sortable(),
            TextColumn::make('inquiry.client.full_name')->label('Cliente')->searchable(),
            TextColumn::make('eventType.name')->label('Evento')->placeholder('Sin definir'),
            TextColumn::make('event_date')->label('Fecha del evento')->date('d/m/Y')->sortable(),
            TextColumn::make('total_attendees')->label('Asistentes'),
            TextColumn::make('quote_status')->label('Estado')->badge(),
            TextColumn::make('created_at')->label('Elaborada')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('quote_status')->label('Estado')->options([
                'borrador' => 'Borrador', 'enviada' => 'Enviada', 'aceptada' => 'Aceptada',
                'rechazada' => 'Rechazada', 'vencida' => 'Vencida', 'convertida' => 'Convertida en reserva', 'cancelada' => 'Cancelada',
            ]),
        ])->recordActions([
            EditAction::make()->label('Editar'),
            DeleteAction::make()->label('Eliminar'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'edit' => EditQuote::route('/{record}/edit'),
        ];
    }

    private static function distributeAttendees(int $total, mixed $eventTypeId, Set $set): void
    {
        $eventName = EventType::query()->whereKey($eventTypeId)->value('name');
        $usesChildrenSplit = in_array($eventName, [
            'Baby Shower', 'Fiesta Infantil', 'Graduación colegio inicial/primaria', 'Reunión familiar',
        ], true);

        if ($usesChildrenSplit) {
            $adults = (int) round($total * 0.7);
            $set('adult_count', $adults);
            $set('child_count', $total - $adults);

            return;
        }

        $set('adult_count', $total);
        $set('child_count', 0);
    }
}
