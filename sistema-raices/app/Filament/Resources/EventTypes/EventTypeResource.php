<?php

namespace App\Filament\Resources\EventTypes;

use App\Filament\Resources\EventTypes\Pages\CreateEventType;
use App\Filament\Resources\EventTypes\Pages\EditEventType;
use App\Filament\Resources\EventTypes\Pages\ListEventTypes;
use App\Models\EventType;
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

class EventTypeResource extends Resource
{
    protected static ?string $model = EventType::class;
    protected static ?string $modelLabel = 'tipo de evento';
    protected static ?string $pluralModelLabel = 'tipos de evento';
    protected static ?string $navigationLabel = 'Tipos de evento';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')->required()->maxLength(100),
            Toggle::make('is_active')->label('Activo')->default(true),
            Textarea::make('description')->label('Descripción')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Tipo de evento')->searchable()->sortable(),
            TextColumn::make('description')->label('Descripción')->limit(70)->toggleable(),
            IconColumn::make('is_active')->label('Activo')->boolean(),
        ])->recordActions([
            EditAction::make()->label('Editar'),
            DeleteAction::make()->label('Eliminar'),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventTypes::route('/'),
            'create' => CreateEventType::route('/create'),
            'edit' => EditEventType::route('/{record}/edit'),
        ];
    }
}
