<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Service;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;
    protected static ?string $modelLabel = 'servicio';
    protected static ?string $pluralModelLabel = 'servicios';
    protected static ?string $navigationLabel = 'Servicios';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogo';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre del servicio')->required()->maxLength(160),
            TextInput::make('category')->label('Categoría')->required()->maxLength(100),
            TextInput::make('selection_group')->label('Grupo de selección (menús)')->maxLength(80)
                ->helperText('Opcional; sirve para identificar alternativas de menú.'),
            TextInput::make('unit_label')->label('Unidad de medida')->default('unidad')->required()->maxLength(50),
            Toggle::make('is_active')->label('Disponible')->default(true),
            Textarea::make('description')->label('Descripción')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Servicio')->searchable()->sortable(),
            TextColumn::make('category')->label('Categoría')->searchable()->sortable(),
            TextColumn::make('unit_label')->label('Unidad'),
            TextColumn::make('selection_group')->label('Grupo de menú')->placeholder('—')->toggleable(),
            IconColumn::make('is_active')->label('Disponible')->boolean(),
        ])->recordActions([
            EditAction::make()->label('Editar'),
            DeleteAction::make()->label('Eliminar'),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
