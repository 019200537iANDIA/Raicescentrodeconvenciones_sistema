<?php

namespace App\Filament\Resources\Clients;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Models\Client;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $modelLabel = 'cliente';

    protected static ?string $pluralModelLabel = 'clientes';

    protected static ?string $navigationLabel = 'Clientes';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Comercial';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('full_name')
                ->label('Nombres y apellidos')
                ->required()
                ->maxLength(180),
            TextInput::make('mobile')
                ->label('Celular')
                ->tel()
                ->required()
                ->maxLength(30),
            TextInput::make('dni_encrypted')
                ->label('DNI')
                ->maxLength(30)
                ->helperText('Se guarda cifrado.'),
            TextInput::make('district')
                ->label('Distrito')
                ->maxLength(120),
            Textarea::make('address_encrypted')
                ->label('Dirección')
                ->rows(2)
                ->columnSpanFull(),
            Select::make('client_type')
                ->label('Tipo de cliente')
                ->options(['nuevo' => 'Nuevo', 'recurrente' => 'Recurrente'])
                ->default('nuevo')
                ->required(),
            Textarea::make('notes')
                ->label('Observaciones')
                ->rows(3)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('mobile')->label('Celular')->searchable(),
                TextColumn::make('district')->label('Distrito')->toggleable(),
                TextColumn::make('client_type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (?string $state): string => $state === 'recurrente' ? 'Recurrente' : 'Nuevo'),
                TextColumn::make('created_at')->label('Registrado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('client_type')->label('Tipo de cliente')->options([
                    'nuevo' => 'Nuevo',
                    'recurrente' => 'Recurrente',
                ]),
            ])
            ->recordActions([
                EditAction::make()->label('Editar'),
                DeleteAction::make()->label('Eliminar'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClients::route('/'),
            'create' => CreateClient::route('/create'),
            'edit' => EditClient::route('/{record}/edit'),
        ];
    }
}
