<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DownloadResource\Pages;
use App\Filament\Resources\DownloadResource\RelationManagers;
use App\Models\Download;
use App\Models\MercadoLibreConfig;
use App\Models\MercadoLibreItem;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                Forms\Components\Tabs::make('TabsDownload')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informações')
                            ->icon('heroicon-o-information-circle')
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

                                        Forms\Components\Toggle::make('publico')
                                            ->label('Público (Visível na lista)')
                                            ->default(true)
                                            ->onColor('success')
                                            ->offColor('danger'),

                                        Forms\Components\TextInput::make('contador')
                                            ->label('Contador')
                                            ->numeric()
                                            ->default(0)
                                            ->disabled(),
                                    ]),

                                Forms\Components\Textarea::make('descricao')
                                    ->label('Descrição')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Arquivos & Versões')
                            ->icon('heroicon-o-folder-open')
                            ->schema([
                                Forms\Components\Repeater::make('versions')
                                    ->label('Gerenciar Versões')
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
                                            ->visibility('private')
                                            ->preserveFilenames()
                                            ->live()
                                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                                if (empty($state))
                                                    return;
                                                try {
                                                    $file = is_array($state) ? reset($state) : $state;
                                                    if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile && $file->exists()) {
                                                        $bytes = $file->getSize();
                                                        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                                                        for ($i = 0; $bytes > 1024; $i++)
                                                            $bytes /= 1024;
                                                        $set('tamanho', round($bytes, 2) . ' ' . ($units[$i] ?? 'B'));
                                                    }
                                                } catch (\Exception $e) {
                                                }
                                            }),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('tamanho')->readOnly(),
                                            Forms\Components\DateTimePicker::make('data_lancamento')->default(now()),
                                        ]),
                                        Forms\Components\Textarea::make('changelog')->label('Notas da Versão')->rows(2)->columnSpanFull(),
                                    ])
                                    ->reorderable(false)
                                    ->defaultItems(1)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Tabs\Tab::make('Venda & Acesso')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\Grid::make(2)->schema([
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

                                    Forms\Components\Toggle::make('disponivel_revenda')
                                        ->label('Disponível para Revenda')
                                        ->helperText('Permite que revendedores exibam este produto.')
                                        ->default(false),
                                ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('SEO')
                            ->icon('heroicon-o-magnifying-glass')
                            ->schema([
                                \App\Filament\Components\SeoForm::make(),
                            ]),
                    ]),
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
                Tables\Actions\Action::make('publish_ml')
                    ->label('Publicar no ML')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('success')
                    ->steps([
                        \Filament\Forms\Components\Wizard\Step::make('Dados Básicos')
                            ->schema([
                                TextInput::make('title')->label('Título')->default(fn(Download $record) => substr($record->titulo, 0, 60))->required()->maxLength(60),
                                TextInput::make('price')->label('Preço (R$)')->default(fn(Download $record) => $record->preco)->numeric()->required(),
                                TextInput::make('quantity')->label('Estoque')->default(999)->numeric(),
                                Select::make('listing_type_id')->label('Tipo')->options(['gold_special' => 'Clássico', 'gold_pro' => 'Premium', 'free' => 'Grátis'])->default('gold_special')->required(),
                                Select::make('category_id')
                                    ->label('Categoria do ML')
                                    ->placeholder('Digite para buscar (ex: Software)...')
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        if (strlen($search) < 2)
                                            return [];
                                        try {
                                            $response = Http::get("https://api.mercadolibre.com/sites/MLB/domain_discovery/search?q=" . urlencode($search));
                                            return collect($response->json())
                                                ->take(10)
                                                ->mapWithKeys(fn($item) => [$item['category_id'] => ($item['category_name'] ?? $item['domain_name']) . " ({$item['category_id']})"])
                                                ->toArray();
                                        } catch (\Exception $e) {
                                            return [];
                                        }
                                    })
                                    ->allowHtml()
                                    ->required()
                                    ->live()
                                    ->helperText('Selecione a categoria correta para carregar os atributos.'),
                            ]),
                        \Filament\Forms\Components\Wizard\Step::make('Atributos Dinâmicos')
                            ->description('Campos obrigatórios pelo Mercado Livre.')
                            ->schema(function (\Filament\Forms\Get $get) {
                                $categoryId = $get('category_id');
                                if (!$categoryId)
                                    return [Forms\Components\Placeholder::make('info')->content('Informe a categoria no passo anterior.')];

                                $config = MercadoLibreConfig::where('is_active', true)->first();
                                if (!$config)
                                    return [Forms\Components\Placeholder::make('error')->content('ML desconectado.')];

                                try {
                                    $response = Http::get("https://api.mercadolibre.com/categories/{$categoryId}/attributes");
                                    if ($response->failed())
                                        return [Forms\Components\Placeholder::make('error')->content('Categoria inválida ou erro na API.')];

                                    $attributes = $response->json();
                                    $schema = [];

                                    foreach ($attributes as $attr) {
                                        $isRequired = isset($attr['tags']['required']) && $attr['tags']['required'];
                                        $forceInclude = in_array($attr['id'], ['BRAND', 'MODEL', 'FAMILY_NAME', 'SOFTWARE_NAME', 'FORMAT']);

                                        if ($isRequired || $forceInclude) {
                                            $field = TextInput::make("attributes.{$attr['id']}")
                                                ->label($attr['name'])
                                                ->required($isRequired);

                                            if (in_array($attr['id'], ['BRAND', 'brand']))
                                                $field->default('AdasSoft');
                                            if (in_array($attr['id'], ['MODEL', 'model']))
                                                $field->default('Digital');
                                            if ($attr['id'] === 'FAMILY_NAME')
                                                $field->default('Software');
                                            if ($attr['id'] === 'FORMAT')
                                                $field->default('Digital');

                                            if (!empty($attr['values'])) {
                                                $options = collect($attr['values'])->pluck('name', 'name')->toArray();
                                                $field = Select::make("attributes.{$attr['id']}")
                                                    ->label($attr['name'])
                                                    ->options($options)
                                                    ->searchable()
                                                    ->required($isRequired)
                                                    ->default(count($options) == 1 ? array_key_first($options) : null);
                                            }

                                            $schema[] = $field;
                                        }
                                    }

                                    if (empty($schema)) {
                                        return [Forms\Components\Placeholder::make('info')->content('Nenhum atributo obrigatório extra encontrado.')];
                                    }
                                    return $schema;
                                } catch (\Exception $e) {
                                    return [Forms\Components\Placeholder::make('error')->content('Erro: ' . $e->getMessage())];
                                }
                            }),
                        \Filament\Forms\Components\Wizard\Step::make('Finalizar')
                            ->schema([
                                TextInput::make('image_url')->label('URL Imagem')->default('')->required()->helperText('Cole a URL da imagem de capa (Obrigatório no ML)'),
                                Textarea::make('description')->label('Descrição')->default(fn(Download $record) => strip_tags($record->descricao ?? ''))->rows(3)
                            ]),
                    ])
                    ->action(function (Download $record, array $data) {
                        $config = MercadoLibreConfig::where('is_active', true)->first();
                        if (!$config) {
                            Notification::make()->title('ML Desconectado')->danger()->send();
                            return;
                        }

                        $finalAttributes = [];
                        if (isset($data['attributes'])) {
                            foreach ($data['attributes'] as $id => $val) {
                                if ($val)
                                    $finalAttributes[] = ['id' => $id, 'value_name' => $val];
                            }
                        }

                        // Fallback logic manual e explícito
                        $hasFamily = false;
                        foreach ($finalAttributes as $attr) {
                            if ($attr['id'] === 'FAMILY_NAME') {
                                $hasFamily = true;
                                break;
                            }
                        }

                        if (!$hasFamily) {
                            // Força bruta para garantir que o campo vá
                            $finalAttributes[] = ['id' => 'FAMILY_NAME', 'value_name' => 'Software'];
                        }

                        $body = [
                            'title' => $data['title'],
                            'category_id' => $data['category_id'],
                            'price' => (float) $data['price'],
                            'currency_id' => 'BRL',
                            'available_quantity' => (int) $data['quantity'],
                            'buying_mode' => 'buy_it_now',
                            'listing_type_id' => $data['listing_type_id'],
                            'condition' => 'new',
                            'description' => ['plain_text' => $data['description']],
                            'pictures' => [['source' => $data['image_url']]],
                            'attributes' => $finalAttributes
                        ];

                        try {
                            $res = Http::withToken($config->access_token)->post('https://api.mercadolibre.com/items', $body);
                            if ($res->failed())
                                throw new \Exception($res->body());

                            $ml = $res->json();

                            MercadoLibreItem::create([
                                'ml_id' => $ml['id'],
                                'title' => $ml['title'],
                                'price' => $ml['price'],
                                'status' => $ml['status'],
                                'permalink' => $ml['permalink'],
                                'thumbnail' => $ml['thumbnail'] ?? '',
                                'plano_id' => null,
                                'download_id' => $record->id,
                                'ml_user_id' => $config->ml_user_id,
                                'company_id' => $config->company_id,
                                'last_synced_at' => now()
                            ]);

                            Notification::make()->title('Anúncio Publicado!')->body("Link: {$ml['permalink']}")->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Erro ao publicar: ' . $e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\EditAction::make(), // Slideover removido para suportar tabs complexas
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
            'create' => Pages\CreateDownload::route('/create'),
            'edit' => Pages\EditDownload::route('/{record}/edit'),
        ];
    }
}
