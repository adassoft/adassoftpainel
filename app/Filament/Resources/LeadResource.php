<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Catálogo';
    protected static ?string $modelLabel = 'Lead Capturado';
    protected static ?string $pluralModelLabel = 'Leads Capturados';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações do Lead')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('empresa')
                            ->label('Empresa')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp/Telefone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('Endereço IP')
                            ->readOnly(),
                    ])->columns(2),

                Forms\Components\Section::make('Interesse e Status')
                    ->schema([
                        Forms\Components\Select::make('download_id')
                            ->relationship('download', 'titulo')
                            ->label('Download de Interesse')
                            ->readOnly(),
                        Forms\Components\Toggle::make('converted')
                            ->label('Convertido em Cliente?')
                            ->helperText('Marque se este lead virou uma venda.')
                            ->columnSpanFull(),
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Data de Captura')
                            ->content(fn(Lead $record): string => $record->created_at->format('d/m/Y H:i:s')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('empresa')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->searchable(),
                Tables\Columns\TextColumn::make('download.titulo')
                    ->label('Interesse')
                    ->limit(30)
                    ->sortable(),
                Tables\Columns\IconColumn::make('converted')
                    ->label('Conv.')
                    ->boolean()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('download_id')
                    ->relationship('download', 'titulo')
                    ->label('Filtrar por Download'),
                Tables\Filters\Filter::make('converted')
                    ->query(fn(Builder $query): Builder => $query->where('converted', true))
                    ->label('Apenas Convertidos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            // 'create' => Pages\CreateLead::route('/create'), // Disable create
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
