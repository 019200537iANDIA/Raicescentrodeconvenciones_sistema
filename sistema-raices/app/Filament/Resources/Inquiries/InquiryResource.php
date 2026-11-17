<?php

namespace App\Filament\Resources\Inquiries;

use App\Filament\Resources\Inquiries\Pages\CreateInquiry;
use App\Filament\Resources\Inquiries\Pages\EditInquiry;
use App\Filament\Resources\Inquiries\Pages\ListInquiries;
use App\Models\Inquiry;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;
    protected static ?string $modelLabel = 'consulta';
    protected static ?string $pluralModelLabel = 'consultas';
    protected static ?string $navigationLabel = 'Consultas';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static string | \UnitEnum | null $navigationGroup = 'Comercial';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('client_id')->label('Cliente')->relationship('client', 'full_name')
                ->searchable()->preload()->required(),
            Select::make('event_type_id')->label('Tipo de evento')->relationship('eventType', 'name')
                ->searchable()->preload(),
            Select::make('source_channel')->label('Canal de contacto')->options([
                'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'instagram' => 'Instagram',
                'tiktok' => 'TikTok', 'llamada' => 'Llamada', 'presencial' => 'Presencial', 'otro' => 'Otro',
            ])->required(),
            DatePicker::make('event_date_requested')->label('Fecha solicitada')->native(false),
            TextInput::make('budget_reference')->label('Presupuesto referencial')->numeric()->minValue(0),
            Select::make('status')->label('Estado')->options([
                'nueva' => 'Nueva', 'en_atencion' => 'En atención', 'cotizada' => 'Cotizada',
                'reservada' => 'Reservada', 'ganada' => 'Ganada', 'perdida' => 'Perdida', 'cerrada' => 'Cerrada',
            ])->default('nueva')->required(),
            Select::make('assigned_user_id')->label('Responsable')->relationship('assignedUser', 'name')
                ->searchable()->preload()->default(fn () => auth()->id()),
            Textarea::make('summary')->label('Detalle de la consulta')->rows(4)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('client.full_name')->label('Cliente')->searchable()->sortable(),
            TextColumn::make('eventType.name')->label('Tipo de evento')->placeholder('Sin definir'),
            TextColumn::make('source_channel')->label('Canal')->badge(),
            TextColumn::make('event_date_requested')->label('Fecha solicitada')->date('d/m/Y')->sortable(),
            TextColumn::make('status')->label('Estado')->badge(),
            TextColumn::make('assignedUser.name')->label('Responsable')->placeholder('Sin asignar'),
            TextColumn::make('created_at')->label('Registrada')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->label('Estado')->options([
                'nueva' => 'Nueva', 'en_atencion' => 'En atención', 'cotizada' => 'Cotizada',
                'reservada' => 'Reservada', 'ganada' => 'Ganada', 'perdida' => 'Perdida', 'cerrada' => 'Cerrada',
            ]),
        ])->recordActions([
            EditAction::make()->label('Editar'),
            DeleteAction::make()->label('Eliminar'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInquiries::route('/'),
            'create' => CreateInquiry::route('/create'),
            'edit' => EditInquiry::route('/{record}/edit'),
        ];
    }
}
