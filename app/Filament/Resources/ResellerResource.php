<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResellerResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;

class ResellerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Revendas';
    protected static ?string $modelLabel = 'Revenda';
    protected static ?string $slug = 'revendas';
    protected static ?string $navigationGroup = 'Gestão de Revendas';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        // Filtra apenas usuários com nível de acesso 2 (Revenda)
        return parent::getEloquentQuery()->whereIn('acesso', [1, 2]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dados da Revenda')
                    ->description('Informações de acesso e cadastro da revenda.')
                    ->schema([
                        TextInput::make('nome')
                            ->label('Nome da Revenda')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('login')
                            ->label('Login')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(100),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(150),

                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => \Illuminate\Support\Facades\Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->maxLength(255),

                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->mask('99.999.999/9999-99')
                            ->dehydrateStateUsing(fn($state) => preg_replace('/\D/', '', $state ?? ''))
                            ->maxLength(20),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'Ativo' => 'Ativo',
                                'Inativo' => 'Inativo',
                                'Bloqueado' => 'Bloqueado',
                            ])
                            ->default('Ativo')
                            ->required(),
                    ])->columns(2),

                Section::make('Dados da Empresa e Pagamento')
                    ->description('Configurações da empresa vinculada a esta revenda.')
                    ->relationship('empresa') // Edita a tabela empresa via relacionamento
                    ->schema([
                        TextInput::make('razao')
                            ->label('Razão Social')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('asaas_access_token')
                            ->label('Token de Acesso Asaas (API Key)')
                            ->helperText('Chave (começada com $aact_...) obtida no painel do Asaas. Obrigatório para receber pagamentos via Pix.')
                            ->password()
                            ->revealable()
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('revenda_padrao')
                            ->label('Revenda Padrão do Sistema')
                            ->helperText('Se marcado, essa revenda será usada como fallback para clientes sem vínculo definido.')
                            ->dehydrated(true)
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('login')
                    ->label('Login')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Ativo' => 'success',
                        'Inativo' => 'gray',
                        'Bloqueado' => 'danger',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('data')
                    ->label('Data Cadastro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Ativo' => 'Ativo',
                        'Inativo' => 'Inativo',
                        'Bloqueado' => 'Bloqueado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn(User $record) => $record->status === 'Ativo' ? 'Desativar' : 'Ativar')
                    ->icon(fn(User $record) => $record->status === 'Ativo' ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn(User $record) => $record->status === 'Ativo' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $novoStatus = $record->status === 'Ativo' ? 'Inativo' : 'Ativo';
                        $record->update(['status' => $novoStatus]);

                        \Filament\Notifications\Notification::make()
                            ->title('Status atualizado')
                            ->body("A revenda agora está {$novoStatus}")
                            ->success()
                            ->send();
                    }),

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
            // Pode adicionar relação com softwares ou licenças depois
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResellers::route('/'),
            'create' => Pages\CreateReseller::route('/create'),
            'edit' => Pages\EditReseller::route('/{record}/edit'),
        ];
    }
}
