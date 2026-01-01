<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $modelLabel = 'Usuário';
    protected static ?string $pluralModelLabel = 'Usuários';
    protected static ?string $navigationGroup = 'Gestão de Usuários e Clientes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dados Pessoais')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('login')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('cnpj')
                            ->label('CPF/CNPJ')
                            ->required()
                            ->maxLength(20),
                        Forms\Components\Select::make('uf')
                            ->label('UF')
                            ->options([
                                'AC' => 'AC',
                                'AL' => 'AL',
                                'AP' => 'AP',
                                'AM' => 'AM',
                                'BA' => 'BA',
                                'CE' => 'CE',
                                'DF' => 'DF',
                                'ES' => 'ES',
                                'GO' => 'GO',
                                'MA' => 'MA',
                                'MT' => 'MT',
                                'MS' => 'MS',
                                'MG' => 'MG',
                                'PA' => 'PA',
                                'PB' => 'PB',
                                'PR' => 'PR',
                                'PE' => 'PE',
                                'PI' => 'PI',
                                'RJ' => 'RJ',
                                'RN' => 'RN',
                                'RS' => 'RS',
                                'RO' => 'RO',
                                'RR' => 'RR',
                                'SC' => 'SC',
                                'SP' => 'SP',
                                'SE' => 'SE',
                                'TO' => 'TO'
                            ])
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('data')
                            ->label('Data Cadastro')
                            ->default(now())
                            ->required(),
                    ]),

                Forms\Components\Section::make('Acesso e Segurança')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('acesso')
                            ->label('Nível de Acesso')
                            ->options([
                                '1' => 'Administrador',
                                '2' => 'Revenda',
                                '3' => 'Cliente',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'Ativo' => 'Ativo',
                                'Bloqueado' => 'Bloqueado',
                                'Pendente' => 'Pendente',
                            ])
                            ->default('Ativo')
                            ->required(),
                        Forms\Components\TextInput::make('senha')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => \Illuminate\Support\Facades\Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->label('Senha'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('login')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('acesso')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '1' => 'Admin',
                        '2' => 'Revenda',
                        '3' => 'Cliente',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        '1' => 'danger',
                        '2' => 'warning',
                        '3' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Ativo' => 'success',
                        'Bloqueado' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('data')
                    ->date('d/m/Y')
                    ->sortable(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
