<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\LicenseResource\Pages;
use App\Models\License;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationLabel = 'Minhas Licenças';
    protected static ?string $modelLabel = 'Licença';
    protected static ?string $pluralModelLabel = 'Minhas Licenças';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if (!$user)
            return parent::getEloquentQuery()->whereRaw('1=0');

        $cnpjLimpo = preg_replace('/\D/', '', $user->cnpj);
        $company = \App\Models\Company::where('cnpj', $cnpjLimpo)->first();

        if (!$company) {
            return parent::getEloquentQuery()->whereRaw('1=0');
        }

        return parent::getEloquentQuery()->where('empresa_codigo', $company->codigo);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('empresa_codigo')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('cnpj_revenda')
                    ->maxLength(20)
                    ->default(null),
                Forms\Components\TextInput::make('software_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('serial_atual')
                    ->required()
                    ->maxLength(200),
                Forms\Components\DateTimePicker::make('data_criacao'),
                Forms\Components\DateTimePicker::make('data_ativacao')
                    ->required(),
                Forms\Components\DatePicker::make('data_expiracao')
                    ->required(),
                Forms\Components\DateTimePicker::make('data_ultima_renovacao'),
                Forms\Components\TextInput::make('terminais_utilizados')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('terminais_permitidos')
                    ->required()
                    ->numeric()
                    ->default(1),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Textarea::make('observacoes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 1,
                'xl' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // SECTION 1: Cabeçalho com Imagem, Nome e Status
                    Tables\Columns\Layout\Split::make([
                        // Esquerda: Imagem
                        Tables\Columns\ImageColumn::make('software_imagem')
                            ->circular()
                            ->defaultImageUrl('/img/placeholder_card.svg')
                            ->grow(false)
                            ->size(50), // Aumentei um pouco para destaque

                        // Centro: Nome e Terminais
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('nome_software')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                                ->color('primary'), // Destaque na cor da marca

                            Tables\Columns\TextColumn::make('resumo_terminais')
                                ->badge()
                                ->icon('heroicon-m-computer-desktop')
                                ->color('info')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                        ])->space(1), // Espaço entre nome e badge

                        // Direita: Status
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'ativo' => 'success',
                                'inativo', 'bloqueado' => 'danger',
                                default => 'warning',
                            })
                            ->grow(false),
                    ])->from('md')->extraAttributes(['class' => 'items-center']), // Alinhamento vertical centralizado

                    // SECTION 2: Datas Importantes (Box Cinza)
                    Tables\Columns\Layout\Split::make([
                        // Data Ativação
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('LabelAtivacao')
                                ->default('Ativação')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                ->color('gray')
                                ->weight(\Filament\Support\Enums\FontWeight::Medium),

                            Tables\Columns\TextColumn::make('data_ativacao')
                                ->date('d/m/Y')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                                ->color('gray'),
                        ])->space(0),

                        // Data Vencimento (Com destaque)
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('LabelVencimento')
                                ->default('Vencimento')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                                ->color('gray')
                                ->weight(\Filament\Support\Enums\FontWeight::Medium)
                                ->alignment('end'),

                            Tables\Columns\TextColumn::make('data_expiracao')
                                ->date('d/m/Y')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                ->color(fn($state) => $state < now()->addDays(7) ? 'danger' : 'success')
                                ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                                ->alignment('end'),
                        ])->space(0),

                    ])->extraAttributes([
                                'class' => 'bg-gray-50 rounded-lg border border-gray-100 p-3 mt-3 mb-1',
                            ]),

                    // SECTION 3: Ações (Renderizadas automaticamente pelo Actions abaixo, mas podemos adicionar infos extras aqui se quiser)
                ])->space(3),
            ])
            ->filters([
                //
            ])
            ->actions([
                // Ação Renovar
                // Ação Renovar (Checkout Otimizado)
                // Ação Principal: Renovar (Destaque)
                Tables\Actions\Action::make('renovar')
                    ->label('Renovar')
                    ->icon('heroicon-o-sparkles') // Ícone mais chamativo
                    ->color('success')
                    ->button() // Estilo botão preenchido para destaque
                    ->requiresConfirmation()
                    ->modalHeading('Renovar Licença')
                    ->modalDescription('Você será redirecionado para a tela de pagamento. Deseja continuar?')
                    ->action(function ($record) {
                        $plano = \App\Models\Plano::where('software_id', $record->software_id)->first();

                        if (!$plano) {
                            \Filament\Notifications\Notification::make()
                                ->title('Erro')
                                ->body('Nenhum plano disponível para renovação deste software.')
                                ->danger()
                                ->send();
                            return;
                        }

                        return redirect()->route('checkout.start', ['planId' => $plano->id, 'license_id' => $record->id]);
                    }),

                // Ação Histórico
                Tables\Actions\Action::make('historico')
                    ->label('Histórico')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->modalContent(function ($record) {
                        $company = $record->company;

                        if (!$company) {
                            $orders = collect([]);
                        } else {
                            $userIds = \App\Models\User::where('cnpj', $company->cnpj)->pluck('id');

                            $orders = \App\Models\Order::query()
                                ->whereIn('user_id', $userIds)
                                ->whereHas('plan', function ($q) use ($record) {
                                    $q->where('software_id', $record->software_id);
                                })
                                ->where(function ($q) {
                                    $q->whereIn(DB::raw('UPPER(status)'), ['PAGO', 'APROVADO', 'PAID', 'APPROVED']);
                                })
                                ->orderBy('created_at', 'desc')
                                ->get();
                        }

                        return view('filament.app.resources.license-resource.pages.history-modal', [
                            'orders' => $orders
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),

                // Ação Terminais
                Tables\Actions\Action::make('terminais')
                    ->label('Terminais')
                    ->icon('heroicon-o-computer-desktop')
                    ->color('info')
                    ->modalContent(function ($record) {
                        $terminais = \App\Models\Terminal::join('terminais_software', 'terminais.CODIGO', '=', 'terminais_software.terminal_codigo')
                            ->where('terminais_software.licenca_id', $record->id)
                            ->select('terminais.*', 'terminais_software.ultima_atividade', 'terminais_software.ativo as status_vinculo')
                            ->get();

                        return view('filament.app.resources.license-resource.pages.terminals-modal', [
                            'terminais' => $terminais
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),

                // Ação Copiar Token
                Tables\Actions\Action::make('copiar')
                    ->label('Token')
                    ->tooltip('Copiar Token de Ativação')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->action(function () {})
                    ->extraAttributes(fn($record) => [
                        'onclick' => 'window.navigator.clipboard.writeText("' . $record->serial_atual . '"); new FilamentNotification().title("Token copiado!").success().send();',
                        'style' => 'cursor: pointer;',
                    ]),

                // Ação Download
                Tables\Actions\Action::make('download')
                    ->label('Baixar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->url(function ($record) {
                        $sw = $record->software;
                        if (!$sw)
                            return null;

                        // 1. Repositório Vinculado
                        if ($sw->id_download_repo) {
                            $dl = \App\Models\Download::find($sw->id_download_repo);
                            if ($dl && $dl->arquivo_path) {
                                return '/storage/' . $dl->arquivo_path;
                            }
                        }

                        // 2. Upload Direto
                        if ($sw->arquivo_software) {
                            return '/storage/' . $sw->arquivo_software;
                        }

                        return $sw->url_download;
                    })
                    ->visible(
                        fn($record) =>
                        $record->software?->id_download_repo
                        || $record->software?->arquivo_software
                        || $record->software?->url_download
                    )
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
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
            'index' => Pages\ListLicenses::route('/'),
        ];
    }
}
