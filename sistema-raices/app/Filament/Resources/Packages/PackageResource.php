<?php

namespace App\Filament\Resources\Packages;

use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Packages\Pages\EditPackage;
use App\Filament\Resources\Packages\Pages\ListPackages;
use App\Models\EventType;
use App\Models\Package;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;
    protected static ?string $modelLabel = 'paquete';
    protected static ?string $pluralModelLabel = 'paquetes';
    protected static ?string $navigationLabel = 'Paquetes';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogo';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('name')->label('Nivel del paquete')->options([
                'basico' => 'Básico',
                'estandar' => 'Estándar',
                'premium' => 'Premium',
            ])->required(),
            Toggle::make('is_active')->label('Disponible')->default(true),
            Textarea::make('description')->label('Descripción para el equipo')->rows(3)->columnSpanFull(),
            Repeater::make('packageServices')->label('Servicios incluidos por defecto')
                ->relationship()
                ->schema([
                    Select::make('service_id')->label('Servicio')->relationship('service', 'name')
                        ->searchable()->preload()->required(),
                    TextInput::make('default_quantity')->label('Cantidad')->numeric()->minValue(0.01)->default(1)->required(),
                    Select::make('quantity_rule')->label('Regla de cantidad')->options([
                        'fixed' => 'Fija', 'per_person' => 'Por persona', 'tables_of_10' => 'Mesas (1 por cada 10)',
                        'per_adult' => 'Por adulto', 'per_child' => 'Por niño',
                        'staff_per_50' => 'Personal (2 por cada 50, mínimo 2)',
                    ])->default('fixed')->required(),
                    Toggle::make('is_optional')->label('Opcional'),
                    Toggle::make('display_quantity')->label('Mostrar cantidad al cliente')->default(true),
                    Select::make('event_type_names')->label('Solo para estos tipos de evento')->multiple()
                        ->options(fn (): array => EventType::query()->orderBy('name')->pluck('name', 'name')->all())
                        ->searchable()->helperText('Vacío significa que aplica a cualquier evento.'),
                    Select::make('replaces_service_id')->label('Reemplaza este servicio')
                        ->relationship('replacingService', 'name')->searchable()->preload(),
                    TextInput::make('notes')->label('Detalle')->maxLength(500),
                ])->columns(3)->columnSpanFull()->addActionLabel('Agregar servicio'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Paquete')->formatStateUsing(fn (string $state): string => match ($state) {
                'basico' => 'Básico', 'estandar' => 'Estándar', 'premium' => 'Premium', default => $state,
            })->sortable(),
            TextColumn::make('description')->label('Descripción')->limit(70),
            IconColumn::make('is_active')->label('Disponible')->boolean(),
        ])->recordActions([
            EditAction::make()->label('Editar'),
            DeleteAction::make()->label('Eliminar'),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }
}
