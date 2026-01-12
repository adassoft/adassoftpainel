<?php

namespace App\Filament\Reseller\Resources;

use App\Filament\Reseller\Resources\CompanyResource\Pages;
use App\Filament\Reseller\Resources\CompanyResource\RelationManagers;
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
    protected static ?string $pluralModelLabel = 'Meus Clientes';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $navigationGroup = 'Gestão de Clientes';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('licenses', function (Builder $query) {
                $query->where('cnpj_revenda', auth()->user()->cnpj);
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Identificação')
                            ->description('Dados principais da empresa')
                            ->schema([
                                Forms\Components\TextInput::make('razao')
                                    ->label('Razão Social / Nome')
                                    ->required()
                                    ->prefixIcon('heroicon-m-building-office')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('cnpj')
                                    ->label('CNPJ / CPF')
                                    ->required()
                                    ->maxLength(14)
                                    ->unique(table: 'empresa', column: 'cnpj', ignoreRecord: true)
                                    ->helperText('Apenas números')
                                    ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[^0-9]/g, '')"])
                                    ->prefixIcon('heroicon-m-identification')
                                    ->rule(new \App\Rules\CpfCnpj())
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('buscar_cnpj')
                                            // ... existing action content ...
                                            ->icon('heroicon-m-magnifying-glass')
                                            ->tooltip('Consultar CNPJ na Receita')
                                            ->action(function ($state, Forms\Set $set) {
                                                $cnpj = preg_replace('/[^0-9]/', '', $state);
                                                if (strlen($cnpj) !== 14) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Atenção')
                                                        ->body('A consulta automática é válida apenas para CNPJ (14 dígitos).')
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }
                                                try {
                                                    $response = \Illuminate\Support\Facades\Http::get("https://brasilapi.com.br/api/cnpj/v1/{$cnpj}");
                                                    if ($response->successful()) {
                                                        $data = $response->json();
                                                        $set('razao', $data['razao_social'] ?? $data['nome_fantasia'] ?? '');
                                                        $set('email', $data['email'] ?? '');
                                                        $set('fone', $data['ddd_telefone_1'] ?? $data['ddd_telefone_2'] ?? '');
                                                        $set('cidade', $data['municipio'] ?? '');
                                                        $set('uf', $data['uf'] ?? '');
                                                        \Filament\Notifications\Notification::make()->title('Sucesso')->body('Dados da empresa carregados com sucesso!')->success()->send();
                                                    } else {
                                                        throw new \Exception('CNPJ não encontrado ou serviço indisponível.');
                                                    }
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()->title('Erro na Consulta')->body('Não foi possível buscar os dados: ' . $e->getMessage())->danger()->send();
                                                }
                                            })
                                    ),

                                Forms\Components\TextInput::make('fone')
                                    ->label('Telefone/WhatsApp')
                                    ->tel()
                                    ->prefixIcon('heroicon-m-phone'),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email de Contato')
                                    ->email()
                                    ->unique(table: 'empresa', column: 'email', ignoreRecord: true)
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status e Localização')
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Ativo' => 'Ativo',
                                        'Inativo' => 'Inativo',
                                        'Bloqueado' => 'Bloqueado',
                                    ])
                                    ->default('Ativo')
                                    ->required()
                                    ->native(false)
                                    ->selectablePlaceholder(false),

                                Forms\Components\TextInput::make('cidade')
                                    ->label('Cidade')
                                    ->prefixIcon('heroicon-m-map-pin'),

                                Forms\Components\TextInput::make('uf')
                                    ->label('Estado (UF)')
                                    ->prefixIcon('heroicon-m-map'),
                            ])
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('razao')
                    ->label('Razão Social / Nome')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cnpj')
                    ->label('CNPJ / CPF')
                    ->searchable()
                    ->copyable()
                    ->formatStateUsing(function ($state) {
                        $doc = preg_replace('/\D/', '', $state);
                        if (strlen($doc) <= 11) {
                            return substr($doc, 0, 3) . '.***.***-' . substr($doc, -2);
                        }
                        return $state; // CNPJ display raw or formatted
                    }),

                Tables\Columns\TextColumn::make('fone')
                    ->label('Contato')
                    ->getStateUsing(fn(Company $record) => $record->fone ?: ($record->email ?: '-'))
                    ->description(fn(Company $record) => $record->fone && $record->email ? $record->email : null)
                    ->searchable(['fone', 'email']),

                Tables\Columns\TextColumn::make('localizacao')
                    ->label('Cidade/UF')
                    ->getStateUsing(fn(Company $record) => "{$record->cidade}/{$record->uf}"),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Ativo' => 'success',
                        'Inativo' => 'gray',
                        'Bloqueado' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Ativo' => 'Ativo',
                        'Inativo' => 'Inativo',
                        'Bloqueado' => 'Bloqueado',
                    ]),
                Tables\Filters\Filter::make('cidade')
                    ->form([
                        Forms\Components\TextInput::make('cidade_busca')
                            ->label('Cidade')
                            ->placeholder('Buscar cidade...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['cidade_busca'],
                                fn(Builder $query, $cidade) => $query->where('cidade', 'like', "%{$cidade}%"),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-m-pencil-square')
                    ->button()
                    ->color('primary')
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->tooltip('Editar Cliente'),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
