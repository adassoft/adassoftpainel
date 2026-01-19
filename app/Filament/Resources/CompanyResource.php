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
    protected static ?string $navigationGroup = 'Gestão de Clientes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Forms\Components\Section::make('Dados da Empresa')
                            ->description('Informações cadastrais principais.')
                            ->icon('heroicon-o-building-office')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('razao')
                                    ->label('Razão Social')
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('cnpj')
                                    ->label('CNPJ')
                                    ->mask('99.999.999/9999-99')
                                    ->required()
                                    ->unique(table: 'empresa', column: 'cnpj', ignoreRecord: true)
                                    ->maxLength(20),

                                Forms\Components\TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->unique(table: 'empresa', column: 'email', ignoreRecord: true)
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->maxLength(120),

                                Forms\Components\TextInput::make('fone')
                                    ->label('Telefone / WhatsApp')
                                    ->mask('(99) 99999-9999')
                                    ->prefixIcon('heroicon-m-phone')
                                    ->maxLength(20),
                            ]),

                        Forms\Components\Section::make('Endereço')
                            ->icon('heroicon-o-map-pin')
                            ->columns(3)
                            ->collapsed()
                            ->schema([
                                Forms\Components\TextInput::make('cidade')
                                    ->required()
                                    ->maxLength(35),
                                Forms\Components\TextInput::make('uf')
                                    ->label('UF')
                                    ->required()
                                    ->maxLength(2),
                                Forms\Components\TextInput::make('cep')
                                    ->mask('99999-999')
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('endereco')
                                    ->label('Logradouro')
                                    ->columnSpan(2)
                                    ->required()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('bairro')
                                    ->required()
                                    ->maxLength(35),
                            ]),
                    ]),

                Forms\Components\Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\Section::make('Acesso ao Painel')
                            ->icon('heroicon-o-key')
                            ->schema([
                                Forms\Components\TextInput::make('status')
                                    ->label('Status')
                                    ->required()
                                    ->maxLength(20)
                                    ->default('Ativo')
                                    ->disabled(fn($record) => $record?->revenda_padrao)
                                    ->dehydrated(),

                                Forms\Components\Select::make('bloqueado')
                                    ->label('Bloqueio')
                                    ->options(['S' => 'Sim', 'N' => 'Não'])
                                    ->default('N')
                                    ->disabled(fn($record) => $record?->revenda_padrao)
                                    ->dehydrated(),
                            ]),

                        Forms\Components\Section::make('Financeiro')
                            ->icon('heroicon-o-currency-dollar')
                            ->collapsed()
                            ->schema([
                                Forms\Components\TextInput::make('saldo')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0.00),
                                Forms\Components\TextInput::make('asaas_wallet_id')
                                    ->label('ID Carteira Asaas')
                                    ->maxLength(255),

                                Forms\Components\Toggle::make('revenda_padrao')
                                    ->label('É Revenda Padrão?')
                                    ->disabled(fn($record) => $record?->revenda_padrao)
                                    ->dehydrated(),
                            ]),
                    ]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('razao')
                    ->label('Empresa')
                    ->description(fn(Company $record) => $record->cnpj)
                    ->searchable(['razao', 'cnpj', 'usuarios.email', 'usuarios.login', 'usuarios.nome'])
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold),

                Tables\Columns\TextColumn::make('cidade')
                    ->label('Localização')
                    ->formatStateUsing(fn($state, Company $record) => "{$state} / {$record->uf}")
                    ->searchable()
                    ->icon('heroicon-m-map-pin')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('fone')
                    ->label('Contato')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->copyable(),

                // Removido Validade Licença da Tabela pois é legado

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match (strtolower($state)) {
                        'ativo' => 'success',
                        'inativo' => 'gray',
                        'bloqueado', 'cancelado' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\IconColumn::make('bloqueado')
                    ->label('Bloq.')
                    ->icon(fn(string $state): string => match ($state) {
                        'S' => 'heroicon-o-lock-closed',
                        'N' => 'heroicon-o-lock-open',
                        default => 'heroicon-o-lock-open',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'S' => 'danger',
                        'N' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('saldo')
                    ->label('Saldo')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('data')
                    ->label('Cadastro')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('data', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('login_as')
                    ->label('')
                    ->icon('heroicon-o-arrow-right-end-on-rectangle')
                    ->tooltip('Acessar Painel do Cliente')
                    ->color('gray')
                    ->url('#'), // Implementar lógica de impersonate futuramente se necessário

                // Oculta Delete para Revenda Padrão
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn(Company $record) => $record->revenda_padrao),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(fn($record) => !$record->revenda_padrao); // Impede selecionar revenda padrão em bulk
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
