<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Gerenciar Clientes';
    protected static ?string $navigationGroup = 'Clientes e Licenças';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('cnpj')
                    ->required()
                    ->maxLength(20),
                Forms\Components\TextInput::make('razao')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('endereco')
                    ->required()
                    ->maxLength(50),
                Forms\Components\TextInput::make('cidade')
                    ->required()
                    ->maxLength(35),
                Forms\Components\TextInput::make('bairro')
                    ->required()
                    ->maxLength(35),
                Forms\Components\TextInput::make('cep')
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\TextInput::make('uf')
                    ->required()
                    ->maxLength(2),
                Forms\Components\TextInput::make('fone')
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(120)
                    ->default(null),
                Forms\Components\DateTimePicker::make('data')
                    ->required(),
                Forms\Components\TextInput::make('nterminais')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('serial')
                    ->maxLength(200)
                    ->default(null),
                Forms\Components\TextInput::make('software_principal_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\DateTimePicker::make('data_ultima_ativacao'),
                Forms\Components\DatePicker::make('validade_licenca'),
                Forms\Components\TextInput::make('bloqueado')
                    ->maxLength(1)
                    ->default(null),
                Forms\Components\TextInput::make('cnpj_representante')
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\Toggle::make('app_alerta_vencimento'),
                Forms\Components\TextInput::make('app_dias_alerta')
                    ->numeric()
                    ->default(5),
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(20)
                    ->default('Ativo'),
                Forms\Components\TextInput::make('saldo')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Toggle::make('revenda_padrao'),
                Forms\Components\TextInput::make('asaas_wallet_id')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cnpj')
                    ->searchable(),
                Tables\Columns\TextColumn::make('razao')
                    ->searchable(),
                Tables\Columns\TextColumn::make('endereco')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cidade')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bairro')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cep')
                    ->searchable(),
                Tables\Columns\TextColumn::make('uf')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('data')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nterminais')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('serial')
                    ->searchable(),
                Tables\Columns\TextColumn::make('software_principal_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_ultima_ativacao')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('validade_licenca')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bloqueado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cnpj_representante')
                    ->searchable(),
                Tables\Columns\IconColumn::make('app_alerta_vencimento')
                    ->boolean(),
                Tables\Columns\TextColumn::make('app_dias_alerta')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('saldo')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('revenda_padrao')
                    ->boolean(),
                Tables\Columns\TextColumn::make('asaas_wallet_id')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
