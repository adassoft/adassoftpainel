<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResellerConfigResource\Pages;
use App\Filament\Resources\ResellerConfigResource\RelationManagers;
use App\Models\ResellerConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResellerConfigResource extends Resource
{
    protected static ?string $model = ResellerConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Gestão de Usuários e Clientes';
    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Configuração White Label';

    protected static ?string $pluralModelLabel = 'Configurações White Label';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuração Visual & Branding')
                    ->description('Personalize a interface do seu revendedor e dos clientes dele.')
                    ->schema([
                        Forms\Components\Select::make('usuario_id')
                            ->label('Revendedor (Usuário)')
                            ->relationship('user', 'nome')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nome_sistema')
                            ->label('Nome do Sistema')
                            ->placeholder('Ex: Meu Sistema')
                            ->required(),

                        Forms\Components\TextInput::make('slogan')
                            ->label('Slogan')
                            ->placeholder('Ex: Gestão Completa'),

                        Forms\Components\TextInput::make('dominios')
                            ->label('Domínios (Separar por vírgula)')
                            ->placeholder('localhost, revenda.com.br')
                            ->helperText('Para testar agora, use "localhost"')
                            ->required(),

                        Forms\Components\ColorPicker::make('cor_primaria_gradient_start')
                            ->label('Cor Início')
                            ->default('#1a2980'),

                        Forms\Components\ColorPicker::make('cor_primaria_gradient_end')
                            ->label('Cor Fim')
                            ->default('#26d0ce'),

                        Forms\Components\TextInput::make('logo_path')
                            ->label('Caminho da Logo (URL ou Arquivo)')
                            ->default('favicon.svg'),

                        Forms\Components\Toggle::make('ativo')
                            ->label('Ativo')
                            ->default(true)
                            ->inline(false),

                        Forms\Components\Toggle::make('is_default')
                            ->label('Revenda Padrão')
                            ->helperText('Se ativo, será usada para acessos sem revenda definida.')
                            ->default(false)
                            ->inline(false)
                            ->onColor('success'),
                    ])->columns(2),

                Forms\Components\Section::make('Contato e Redes Sociais')
                    ->description('Informações públicas exibidas no rodapé do site.')
                    ->schema([
                        Forms\Components\TextInput::make('email_suporte')->label('E-mail de Suporte')->email(),
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp Comercial (Números)')
                            ->helperText('Apenas números, com DDD. Ex: 11999999999'),
                        Forms\Components\TextInput::make('endereco')->label('Endereço Completo')->columnSpanFull(),
                        Forms\Components\TextInput::make('horario_atendimento')->label('Horário de Atendimento')->placeholder('Seg à Sex, 09h às 18h'),

                        Forms\Components\Toggle::make('exibir_documento')
                            ->label('Exibir CPF/CNPJ no Rodapé')
                            ->default(true),

                        Forms\Components\Group::make()->schema([
                            Forms\Components\TextInput::make('instagram_url')->label('Instagram (URL)')->url()->prefix('https://'),
                            Forms\Components\TextInput::make('facebook_url')->label('Facebook (URL)')->url()->prefix('https://'),
                            Forms\Components\TextInput::make('linkedin_url')->label('LinkedIn (URL)')->url()->prefix('https://'),
                            Forms\Components\TextInput::make('youtube_url')->label('YouTube (URL)')->url()->prefix('https://'),
                        ])->columnSpanFull()->columns(2),
                    ])->columns(2)->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('dominios')
                    ->label('Domínios')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\ViewColumn::make('sistema')
                    ->label('Sistema')
                    ->view('filament.tables.columns.reseller-info'),

                Tables\Columns\ViewColumn::make('cores')
                    ->label('Cores')
                    ->view('filament.tables.columns.reseller-colors'),

                Tables\Columns\IconColumn::make('ativo')
                    ->label('Status')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_default')
                    ->label('Padrão')
                    ->onColor('success')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->tooltip('Editar')
                    ->slideOver()
                    ->color('primary')
                    ->size(\Filament\Support\Enums\ActionSize::Medium)
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->icon('heroicon-m-pencil-square'),
                Tables\Actions\Action::make('toggle_status')
                    ->label('')
                    ->tooltip(fn($record) => $record->ativo ? 'Desativar' : 'Ativar')
                    ->icon(fn($record) => $record->ativo ? 'heroicon-m-pause' : 'heroicon-m-play')
                    ->color('warning')
                    ->size(\Filament\Support\Enums\ActionSize::Medium)
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->action(fn($record) => $record->update(['ativo' => !$record->ativo])),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir')
                    ->color('danger')
                    ->size(\Filament\Support\Enums\ActionSize::Medium)
                    ->extraAttributes(['class' => 'force-btn-height'])
                    ->icon('heroicon-m-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageResellerConfigs::route('/'),
        ];
    }
}
