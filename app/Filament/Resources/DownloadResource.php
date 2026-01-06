<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DownloadResource\Pages;
use App\Filament\Resources\DownloadResource\RelationManagers;
use App\Models\Download;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DownloadResource extends Resource
{
    protected static ?string $model = Download::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-down';
    protected static ?string $navigationLabel = 'Gerenciar Downloads';
    protected static ?string $navigationGroup = 'Catálogo de Softwares';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Download';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('titulo')
                    ->label('Título')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                    ->placeholder('Ex: Manual de Instalação')
                    ->columnSpanFull()
                    ->prefixIcon('heroicon-m-document-text'),

                Forms\Components\TextInput::make('slug')
                    ->label('URL Amigável')
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->columnSpanFull()
                    ->prefixIcon('heroicon-m-link'),

                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('categoria')
                            ->label('Categoria')
                            ->datalist([
                                'Drivers',
                                'Manuais',
                                'Utilitários',
                                'Instaladores',
                            ])
                            ->placeholder('Ex: Drivers')
                            ->prefixIcon('heroicon-m-tag'),
                    ]),

                Forms\Components\Section::make('Gerenciador de Versões e Arquivos')
                    ->description('Adicione aqui os arquivos. O sistema identificará automaticamente a versão mais recente para download principal.')
                    ->schema([
                        Forms\Components\Repeater::make('versions')
                            ->relationship()
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
                                    Forms\Components\TextInput::make('versao')
                                        ->label('Versão (ex: v1.0)')
                                        ->required(),
                                    Forms\Components\Select::make('sistema_operacional')
                                        ->label('Sistema Operacional')
                                        ->options([
                                            'windows' => 'Windows',
                                            'linux' => 'Linux',
                                            'mac' => 'macOS',
                                            'android' => 'Android',
                                            'ios' => 'iOS',
                                            'any' => 'Qualquer',
                                        ])
                                        ->default('windows')
                                        ->required(),
                                ]),
                                Forms\Components\FileUpload::make('arquivo_path')
                                    ->label('Arquivo')
                                    ->disk('products')
                                    ->directory('versions')
                                    ->required()
                                    // ->maxSize(102400) // Removido para evitar crash se o arquivo falhar no upload
                                    ->visibility('private')
                                    ->preserveFilenames()
                                    ->live()
                                    ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                        if (empty($state))
                                            return;

                                        try {
                                            $file = is_array($state) ? reset($state) : $state;
                                            // Verifica se é temporário e se existe fisicamente
                                            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile && $file->exists()) {
                                                $bytes = $file->getSize();
                                                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                                                for ($i = 0; $bytes > 1024; $i++) {
                                                    $bytes /= 1024;
                                                }
                                                $set('tamanho', round($bytes, 2) . ' ' . ($units[$i] ?? 'B'));
                                            }
                                        } catch (\Exception $e) {
                                            // Silencia erro de metadados ausentes para não quebrar o fluxo
                                            \Illuminate\Support\Facades\Log::warning('Erro ao calcular tamanho do arquivo DL: ' . $e->getMessage());
                                        }
                                    }),
                                Forms\Components\Grid::make(3)->schema([
                                    Forms\Components\TextInput::make('tamanho')->readOnly(),
                                    Forms\Components\DateTimePicker::make('data_lancamento')->default(now()),
                                    Forms\Components\TextInput::make('contador')->disabled()->default(0),
                                ]),
                                Forms\Components\Textarea::make('changelog')->label('Notas da Versão')->rows(2)->columnSpanFull(),
                            ])
                            ->reorderable(false) // Desativa reordenação para não sobrescrever data_lancamento com índices
                            ->defaultItems(1)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Toggle::make('publico')
                            ->label('Público (Visível na lista)')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),

                        Forms\Components\TextInput::make('contador')
                            ->label('Contador de Downloads')
                            ->numeric()
                            ->default(0)
                            ->prefixIcon('heroicon-m-arrow-down-tray'),
                    ]),

                Forms\Components\Section::make('Configurações de Venda e Acesso')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Toggle::make('requires_login')
                                ->label('Exigir Login')
                                ->helperText('Usuário deve estar logado para baixar.')
                                ->default(false),

                            Forms\Components\Toggle::make('is_paid')
                                ->label('Produto Pago')
                                ->live()
                                ->helperText('Exige pagamento para liberar download.')
                                ->default(false),

                            Forms\Components\TextInput::make('preco')
                                ->label('Preço (R$)')
                                ->prefix('R$')
                                ->numeric()
                                ->default(0.00)
                                ->visible(fn(Forms\Get $get) => $get('is_paid'))
                                ->required(fn(Forms\Get $get) => $get('is_paid')),
                        ]),

                        Forms\Components\Toggle::make('disponivel_revenda')
                            ->label('Disponível para Revenda')
                            ->helperText('Permite que revendedores exibam este produto em suas lojas.')
                            ->default(false)
                            ->onColor('primary')
                            ->offColor('gray'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Documento/Arquivo')
                    ->description(fn(Download $record) => $record->categoria)
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('versao')
                    ->label('Versão')
                    ->sortable(),

                Tables\Columns\TextColumn::make('tamanho')
                    ->label('Tam.'),

                Tables\Columns\TextColumn::make('data_atualizacao')
                    ->label('Data/Info')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn(Download $record) => 'Downloads: ' . $record->contador),

                Tables\Columns\TextColumn::make('preco')
                    ->label('Preço')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Pago')
                    ->boolean()
                    ->trueIcon('heroicon-o-currency-dollar')
                    ->falseIcon('heroicon-o-gift')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('disponivel_revenda')
                    ->label('Revenda')
                    ->boolean()
                    ->trueIcon('heroicon-o-check')
                    ->falseIcon('heroicon-o-x-mark')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('publico')
                    ->label('Público')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-lock-closed')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->defaultSort('data_atualizacao', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->modalWidth('2xl'),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('download')
                    ->label('')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn(Download $record) => '/storage/' . $record->arquivo_path)
                    ->openUrlInNewTab(),
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
            // RelationManagers\VersionsRelationManager::class, // Desativado em favor do Repeater
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDownloads::route('/'),
        ];
    }
}
