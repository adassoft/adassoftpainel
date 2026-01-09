<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plano;
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

class PlanResource extends Resource
{
    protected static ?string $model = Plano::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $modelLabel = 'Plano de Venda';
    protected static ?string $pluralModelLabel = 'Planos de Venda';
    protected static ?string $navigationGroup = 'Catálogo de Softwares';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Coluna Principal (2/3 ou Full)
                        Forms\Components\Group::make()
                            ->columnSpan(fn($operation) => $operation === 'create' ? 3 : 2)
                            ->schema([
                                Forms\Components\Section::make(fn($operation) => $operation === 'create' ? 'Novo Plano' : 'Dados do Plano')
                                    ->icon(fn($operation) => $operation === 'create' ? 'heroicon-o-plus-circle' : 'heroicon-o-calendar')
                                    ->schema([
                                        Forms\Components\TextInput::make('nome_plano')
                                            ->label('Nome do Plano')
                                            ->required()
                                            ->placeholder(fn($operation) => $operation === 'create' ? 'Ex: Plano Mensal Premium' : 'Ex: Super Carnê = 01 mês')
                                            ->prefixIcon('heroicon-m-tag')
                                            ->prefixIconColor('primary')
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('software_id')
                                            ->label('Software')
                                            ->relationship('software', 'nome_software')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->prefixIcon('heroicon-m-cube')
                                            ->prefixIconColor('primary')
                                            ->placeholder('Selecione um software...')
                                            ->columnSpanFull(),

                                        Forms\Components\Select::make('recorrencia')
                                            ->label('Recorrência (meses)')
                                            ->options([
                                                1 => '1 Mês',
                                                3 => '3 Meses',
                                                6 => '6 Meses',
                                                12 => '12 Meses (Anual)',
                                            ])
                                            ->required()
                                            ->prefixIcon('heroicon-m-arrow-path')
                                            ->prefixIconColor('primary')
                                            ->placeholder('Selecione...')
                                            ->default(null)
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('valor')
                                            ->label('Valor (R$)')
                                            ->prefixIcon('heroicon-m-currency-dollar')
                                            ->prefixIconColor('primary')
                                            ->numeric()
                                            ->required()
                                            ->placeholder('Ex: 50,00')
                                            ->columnSpanFull(),

                                        Forms\Components\Toggle::make('status')
                                            ->label('Plano Ativo')
                                            ->default(true)
                                            ->onIcon('heroicon-m-check')
                                            ->offIcon('heroicon-m-x-mark')
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->hidden(fn($operation) => $operation === 'create'),
                                    ]),
                            ]),

                        // Coluna Lateral (1/3)
                        Forms\Components\Group::make()
                            ->columnSpan(1)
                            ->hidden(fn($operation) => $operation === 'create')
                            ->schema([
                                Forms\Components\Section::make('Informações do Plano')
                                    ->schema([
                                        Forms\Components\Placeholder::make('id')
                                            ->label('ID do Plano:')
                                            ->content(fn($record) => '#' . $record?->id),

                                        Forms\Components\Placeholder::make('data_cadastro')
                                            ->label('Data de Cadastro:')
                                            ->content(fn($record) => $record?->data_cadastro?->format('d/m/Y H:i:s') ?? '-'),

                                        Forms\Components\Placeholder::make('dicas')
                                            ->label('Dicas para edição:')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <ul class="list-disc pl-4 text-sm text-gray-500 space-y-1">
                                                    <li>Nome deve ser descritivo e único</li>
                                                    <li>Software deve estar ativo</li>
                                                    <li>Recorrência em meses</li>
                                                    <li>Valor deve usar vírgula ou ponto</li>
                                                </ul>
                                            ')),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Lista de Planos')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->grow(false),
                Tables\Columns\TextColumn::make('nome_plano')
                    ->label('Nome do Plano')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->sortable()
                    ->description(
                        fn(Plano $record): string =>
                        ($record->software?->codigo ?? '-') . ' v' . ($record->software?->versao ?? '-')
                    )
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('recorrencia')
                    ->label('Recorrência')
                    ->suffix(' meses')
                    ->sortable()
                    ->alignCenter()
                    ->grow(false),
                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor (R$)')
                    ->money('BRL')
                    ->sortable()
                    ->grow(false),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        '1', 'true', 'active' => 'success',
                        '0', 'false', 'inactive' => 'gray',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        '1', 'true', 'active' => 'Ativo',
                        '0', 'false', 'inactive' => 'Inativo',
                        default => $state,
                    })
                    ->grow(false),
                Tables\Columns\TextColumn::make('data_cadastro')
                    ->label('Data Cadastro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->defaultSort('id', 'asc')
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
                                TextInput::make('title')->label('Título')
                                    ->default(function (Plano $record) {
                                        $nome = $record->nome_plano;
                                        $soft = $record->software->nome_software ?? '';
                                        // Evita "Super Carnê - Super Carnê"
                                        $final = ($soft && stripos($nome, $soft) !== false) ? $nome : "$nome - $soft";
                                        return substr(str_replace(['=', '*', '+'], ' ', $final), 0, 60);
                                    })
                                    ->required()->maxLength(60),
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
                                TextInput::make('image_url')->label('URL Imagem')->default(fn(Plano $record) => $record->software->imagem ? url($record->software->imagem) : '')->required(),
                                Textarea::make('description')->label('Descrição')->default(fn(Plano $record) => strip_tags($record->software->descricao ?? ''))->rows(3)
                            ]),
                    ])
                    ->action(function (Plano $record, array $data) {
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

                        // Fallback logic manual explícito
                        $hasFamilyUpper = false;
                        $hasFamilyLower = false;

                        foreach ($finalAttributes as $attr) {
                            if ($attr['id'] === 'FAMILY_NAME')
                                $hasFamilyUpper = true;
                            if ($attr['id'] === 'family_name')
                                $hasFamilyLower = true;
                        }

                        if (!$hasFamilyUpper) {
                            $finalAttributes[] = ['id' => 'FAMILY_NAME', 'value_name' => 'Software'];
                        }
                        // OBRIGATÓRIO: Se não tem o minúsculo, adiciona (mesmo se tiver o maiúsculo)
                        if (!$hasFamilyLower) {
                            $finalAttributes[] = ['id' => 'family_name', 'value_name' => 'Software'];
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

                        // HACK DE COMPATIBILIDADE: family_name na raiz (apenas minúsculo)
                        $body['family_name'] = 'Software';

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
                                'plano_id' => $record->id,
                                'ml_user_id' => $config->ml_user_id,
                                'company_id' => $config->company_id,
                                'last_synced_at' => now()
                            ]);

                            Notification::make()->title('Anúncio Publicado!')->body("Link: {$ml['permalink']}")->success()->send();
                        } catch (\Exception $e) {
                            $errorMsg = $e->getMessage();
                            $decodedError = json_decode($errorMsg, true);

                            // Debug: Monta mensagem com o que foi enviado + erro
                            $debugInfo = [
                                'ENVIADO (Body)' => $body,
                                'ERRO (API)' => $decodedError ?? $errorMsg
                            ];

                            $finalMsg = json_encode($debugInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

                            Notification::make()
                                ->title('Erro ao publicar')
                                ->body($finalMsg)
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-m-pencil-square')
                    ->button()
                    ->color('primary')
                    ->tooltip('Editar'),
                Tables\Actions\Action::make('toggle_status')
                    ->label('')
                    ->icon('heroicon-m-pause')
                    ->button()
                    ->color('warning')
                    ->action(fn(Plano $record) => $record->update(['status' => !$record->status]))
                    ->requiresConfirmation()
                    ->tooltip(fn(Plano $record) => $record->status ? 'Inativar' : 'Ativar'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-m-trash')
                    ->button()
                    ->color('danger')
                    ->tooltip('Excluir'),
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
