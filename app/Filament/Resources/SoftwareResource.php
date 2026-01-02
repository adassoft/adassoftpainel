<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SoftwareResource\Pages;
use App\Filament\Resources\SoftwareResource\RelationManagers;
use App\Models\Software;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Support\HtmlString;

class SoftwareResource extends Resource
{
    protected static ?string $model = Software::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $modelLabel = 'Software';
    protected static ?string $pluralModelLabel = 'Softwares';
    protected static ?string $navigationGroup = 'Catálogo de Softwares';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        // Coluna Principal (Esquerda - 2/3)
                        Group::make()
                            ->columnSpan(2)
                            ->schema([
                                Section::make('Dados do Software')
                                    ->schema([
                                        TextInput::make('codigo')
                                            ->label('Código')
                                            ->default(fn() => 'SW-' . strtoupper(\Illuminate\Support\Str::random(8)))
                                            ->unique(ignoreRecord: true)
                                            ->required()
                                            ->maxLength(50)
                                            ->helperText('Código único para identificação do software (máximo 50 caracteres).'),

                                        TextInput::make('nome_software')
                                            ->label('Nome do Software')
                                            ->required()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(Forms\Set $set, ?string $state) => $set('slug', \Illuminate\Support\Str::slug($state)))
                                            ->placeholder('Ex: Dev PDV Delphi')
                                            ->maxLength(255)
                                            ->helperText('Nome completo do software (máximo 255 caracteres).'),

                                        TextInput::make('slug')
                                            ->label('URL Amigável (Slug)')
                                            ->unique(ignoreRecord: true)
                                            ->required()
                                            ->placeholder('ex: dev-pdv-delphi')
                                            ->maxLength(255)
                                            ->helperText('Identificador na URL: adassoft.com/produto/SEU-SLUG'),

                                        TextInput::make('versao')
                                            ->label('Versão')
                                            ->required()
                                            ->placeholder('Ex: 2.0.1, 1.5.0')
                                            ->maxLength(50)
                                            ->helperText('Versão atual do software (máximo 50 caracteres).'),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('linguagem')
                                                    ->label('Linguagem de Programação')
                                                    ->placeholder('Ex: Delphi, PHP')
                                                    ->maxLength(50),
                                                Select::make('plataforma')
                                                    ->label('Plataforma')
                                                    ->options([
                                                        'desktop' => 'Desktop (Windows)',
                                                        'web' => 'Web App (Nuvem)',
                                                        'mobile' => 'Mobile (Android/iOS)',
                                                        'api' => 'API / Backend'
                                                    ]),
                                            ]),

                                        Tabs::make('Origem do Arquivo')
                                            ->tabs([
                                                Tabs\Tab::make('Upload Direto')
                                                    ->schema([
                                                        FileUpload::make('arquivo_software')
                                                            ->label('Selecione o Instalador (.exe, .zip, .rar)')
                                                            ->disk('public')
                                                            ->directory('softwares')
                                                            ->helperText("O arquivo será salvo em 'uploads/softwares/'. O tamanho é calculado automaticamente."),
                                                    ]),
                                                Tabs\Tab::make('Repositório Interno')
                                                    ->schema([
                                                        Select::make('id_download_repo')
                                                            ->label('Selecione um Arquivo do Gerenciador de Downloads')
                                                            ->options(\App\Models\Download::all()->pluck('titulo', 'id'))
                                                            ->searchable()
                                                            ->helperText('Vincule um arquivo já cadastrado em "Gerenciador de Downloads".'),
                                                    ]),
                                                Tabs\Tab::make('Link Externo')
                                                    ->schema([
                                                        TextInput::make('url_download')
                                                            ->label('URL Direta')
                                                            ->placeholder('Ex: http://meusite.com/setup.exe'),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),

                                        TextInput::make('tamanho_arquivo')
                                            ->label('Tamanho (Opcional/Calculado)')
                                            ->placeholder('Ex: 50MB')
                                            ->helperText('Será preenchido automaticamente no Upload ou Repositório.'),
                                    ]),

                                Section::make('Informações da Loja')
                                    ->icon('heroicon-o-building-storefront')
                                    ->schema([
                                        TextInput::make('categoria')
                                            ->label('Categoria')
                                            ->default('Software')
                                            ->placeholder('Ex: ERP, PDV'),

                                        Grid::make(1)
                                            ->schema([
                                                // Icon Row
                                                Group::make()
                                                    ->schema([
                                                        TextInput::make('imagem')
                                                            ->label('Ícone / Logo (Quadrado)')
                                                            ->placeholder('https://... ou Caminho gerado')
                                                            ->suffixAction(
                                                                Action::make('gerar_icon_ia')
                                                                    ->icon('heroicon-o-sparkles')
                                                                    ->label('Ícone IA')
                                                                    ->color('primary')
                                                                    ->form([
                                                                        Textarea::make('prompt_imagem')
                                                                            ->label('Descreva o ícone')
                                                                            ->placeholder('Ex: Ícone moderno flat de um carrinho de compras azul...')
                                                                            ->required(),
                                                                    ])
                                                                    ->action(function (array $data, Forms\Set $set, \App\Services\GeminiService $gemini) {
                                                                        try {
                                                                            $basePrompt = "Você é um designer de UI/UX especialista em Ícones e Logotipos (SVG). Crie um ÍCONE DE APP quadrado.";
                                                                            $techSpecs = "\nREGRAS TÉCNICAS (OBRIGATÓRIO):\n1. Retorne APENAS o código SVG bruto.\n2. SVG deve ter viewBox='0 0 512 512' (Quadrado perfeito).\n3. Estilo: Flat, Minimalista, Identidade Visual Clara. Fundo transparente ou shape circular/arredondado.";

                                                                            $userInstruction = "Instrução do Usuário: " . $data['prompt_imagem'];
                                                                            $finalPrompt = $basePrompt . "\n" . $userInstruction . $techSpecs;

                                                                            $response = $gemini->generateContent($finalPrompt);

                                                                            if (!$response['success']) {
                                                                                throw new \Exception($response['error']);
                                                                            }

                                                                            $textoCandidato = $response['reply'];
                                                                            $textoCandidato = str_replace(['```svg', '```xml', '```'], '', $textoCandidato);

                                                                            if (preg_match('/<svg[\s\S]*?<\/svg>/i', $textoCandidato, $matches)) {
                                                                                $svgCode = $matches[0];
                                                                            } else {
                                                                                throw new \Exception("A IA não retornou um SVG válido.");
                                                                            }

                                                                            $fileName = 'ia_icon_' . uniqid() . '.svg';
                                                                            $path = public_path('img/produtos');
                                                                            if (!file_exists($path)) {
                                                                                mkdir($path, 0755, true);
                                                                            }
                                                                            file_put_contents($path . '/' . $fileName, $svgCode);

                                                                            $publicUrl = 'img/produtos/' . $fileName;
                                                                            $set('imagem', $publicUrl);

                                                                            \Filament\Notifications\Notification::make()
                                                                                ->title('Ícone Gerado!')
                                                                                ->success()
                                                                                ->send();

                                                                        } catch (\Exception $e) {
                                                                            \Filament\Notifications\Notification::make()
                                                                                ->title('Erro ao Gerar Ícone')
                                                                                ->body($e->getMessage())
                                                                                ->danger()
                                                                                ->send();
                                                                        }
                                                                    })
                                                            ),
                                                    ]),

                                                // Banner Row
                                                Group::make()
                                                    ->schema([
                                                        TextInput::make('imagem_destaque')
                                                            ->label('Imagem Destaque (Banner)')
                                                            ->placeholder('https://... ou Caminho gerado')
                                                            ->suffixAction(
                                                                Action::make('gerar_banner_ia')
                                                                    ->icon('heroicon-o-photo')
                                                                    ->label('Banner IA')
                                                                    ->color('info')
                                                                    ->form([
                                                                        Textarea::make('prompt_banner')
                                                                            ->label('Descreva o banner')
                                                                            ->placeholder('Ex: Banner tecnológico com gráficos e nuvens, tons de azul...')
                                                                            ->required(),
                                                                    ])
                                                                    ->action(function (array $data, Forms\Set $set, \App\Services\GeminiService $gemini) {
                                                                        try {
                                                                            $basePrompt = "Você é um ilustrador digital especialista em Cenários Vetoriais (SVG) para Web. Crie um BANNER HERO IMAGE moderno e sofisticado para software SaaS.";
                                                                            $techSpecs = "\nREGRAS TÉCNICAS (OBRIGATÓRIO):\n1. Retorne APENAS o código SVG bruto.\n2. SVG deve ter viewBox='0 0 1200 630' (Proporção aprox. 2:1, padrão social).\n3. Fundo: DEVE ter um fundo preenchido (retângulo com gradiente suave ou cor sólida moderna).\n4. Estilo: Isometric, Tech, Startup, Clean. Evite textos complexos.\n5. Elementos: Use representações abstratas de dashboards, gráficos, nuvens, conectividade.";

                                                                            $userInstruction = "Instrução do Usuário: " . $data['prompt_banner'];
                                                                            $finalPrompt = $basePrompt . "\n" . $userInstruction . $techSpecs;

                                                                            $response = $gemini->generateContent($finalPrompt);

                                                                            if (!$response['success']) {
                                                                                throw new \Exception($response['error']);
                                                                            }

                                                                            $textoCandidato = $response['reply'];
                                                                            $textoCandidato = str_replace(['```svg', '```xml', '```'], '', $textoCandidato);

                                                                            if (preg_match('/<svg[\s\S]*?<\/svg>/i', $textoCandidato, $matches)) {
                                                                                $svgCode = $matches[0];
                                                                            } else {
                                                                                throw new \Exception("A IA não retornou um SVG válido.");
                                                                            }

                                                                            $fileName = 'ia_banner_' . uniqid() . '.svg';
                                                                            $path = public_path('img/produtos');
                                                                            if (!file_exists($path)) {
                                                                                mkdir($path, 0755, true);
                                                                            }
                                                                            file_put_contents($path . '/' . $fileName, $svgCode);

                                                                            $publicUrl = 'img/produtos/' . $fileName;
                                                                            $set('imagem_destaque', $publicUrl);

                                                                            \Filament\Notifications\Notification::make()
                                                                                ->title('Banner Gerado!')
                                                                                ->success()
                                                                                ->send();

                                                                        } catch (\Exception $e) {
                                                                            \Filament\Notifications\Notification::make()
                                                                                ->title('Erro ao Gerar Banner')
                                                                                ->body($e->getMessage())
                                                                                ->danger()
                                                                                ->send();
                                                                        }
                                                                    })
                                                            ),
                                                    ]),
                                            ]),

                                        Textarea::make('descricao')
                                            ->label('Descrição Curta (Vitrine)')
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Textarea::make('pagina_vendas_html')
                                            ->label('Landing Page Personalizada (HTML Puro)')
                                            ->helperText('Cole o HTML bruto aqui. O sistema renderizará exatamente como estiver.')
                                            ->rows(15)
                                            ->columnSpanFull()
                                            ->hintAction(
                                                Action::make('gerar_html_ia')
                                                    ->label('Gerar com IA')
                                                    ->icon('heroicon-o-bolt')
                                                    ->color('info')
                                                    ->form(function (Forms\Get $get) {
                                                        // 1. Obter Cores da Marca (Branding)
                                                        $branding = \App\Services\ResellerBranding::getCurrent();
                                                        $corStart = $branding['cor_start'] ?? '#3b82f6'; // Fallback Blue
                                                        $corEnd = $branding['cor_end'] ?? '#1d4ed8';

                                                        // 2. Obter Dados do Formulário Atual
                                                        $nomeSoftware = $get('nome_software') ?? 'NOME DO SOFTWARE';
                                                        $descricao = $get('descricao') ?? 'Software inovador de gestão.';

                                                        // 3. Montar Prompt Rico
                                                        $prePrompt = "Crie uma Landing Page moderna para o software: '{$nomeSoftware}'.\n";
                                                        $prePrompt .= "Contexto: {$descricao}\n\n";
                                                        $prePrompt .= "IDENTIDADE VISUAL (Siga estritamente):\n";
                                                        $prePrompt .= "- Cor Primária/Gradiente: De {$corStart} para {$corEnd}.\n";
                                                        $prePrompt .= "- Estilo: Clean, Profissional, use sombras suaves e bordas arredondadas (Border Radius 12px).\n";
                                                        $prePrompt .= "- CTA (Botões): Use gradiente 'background: linear-gradient(to right, {$corStart}, {$corEnd})'.\n\n";
                                                        $prePrompt .= "DETALHES DO CONTEÚDO:\n";
                                                        $prePrompt .= "Inclua uma seção 'Hero' impactante, uma seção de 'Funcionalidades' (Grid 3 colunas) e um 'Rodapé' simples.";

                                                        return [
                                                            Textarea::make('prompt_html')
                                                                ->label('Prompt da IA (Personalize se necessário)')
                                                                ->default($prePrompt)
                                                                ->rows(10)
                                                                ->required(),
                                                        ];
                                                    })
                                                    ->action(function (array $data, Forms\Set $set, \App\Services\GeminiService $gemini) {
                                                        try {
                                                            $userPrompt = $data['prompt_html'];

                                                            $baseSystemPrompt = "Atue como um Especialista em Front-end Sênior (Bootstrap 5 + CSS Moderno). " .
                                                                "Sua tarefa é escrever o CÓDIGO HTML PURO para o CORPO de uma Landing Page. " .
                                                                "NÃO use tags <html>, <head> ou <body>. Comece direto nas <section> ou <header>.\n" .
                                                                "Use classes do Bootstrap 5 para layout (container, row, col, d-flex, etc). " .
                                                                "Para estilização específica (cores, gradientes, sombras), use tags <style> no início ou style='' inline, garantindo que o design fique incrível e fiel às cores solicitadas.\n" .
                                                                "IMPORTANTE: Retorne APENAS o código HTML bruto. Não use blocos de código markdown (```).";

                                                            $finalPrompt = $baseSystemPrompt . "\n\nINSTRUÇÃO DO USUÁRIO:\n" . $userPrompt;

                                                            $response = $gemini->generateContent($finalPrompt);

                                                            if (!$response['success']) {
                                                                throw new \Exception($response['error']);
                                                            }

                                                            $html = $response['reply'];
                                                            $cleanHtml = str_replace(['```html', '```'], '', $html);

                                                            $set('pagina_vendas_html', $cleanHtml);

                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Landing Page Gerada!')
                                                                ->body('O HTML foi inserido no campo. Verifique e ajuste se necessário.')
                                                                ->success()
                                                                ->send();
                                                        } catch (\Exception $e) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Erro na IA')
                                                                ->body($e->getMessage())
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    })
                                            ),
                                    ]),

                                Section::make('Integrações (Google Shopping / Meta)')
                                    ->collapsible()
                                    ->collapsed()
                                    ->icon('heroicon-o-globe-alt')
                                    ->schema([
                                        TextInput::make('gtin')
                                            ->label('GTIN / EAN')
                                            ->placeholder('EAN-13 ou deixe vazio')
                                            ->helperText('Se vazio, enviará "identifier_exists=no".'),
                                        Select::make('google_product_category')
                                            ->label('Categoria Google')
                                            ->options(\App\Services\GoogleTaxonomy::getSoftwareCategories())
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Busque por: Software, ERP, CRM...'),
                                        TextInput::make('brand')
                                            ->label('Marca')
                                            ->default('AdasSoft'),
                                    ])->columns(3),
                            ]),

                        // Coluna Lateral (Direita - 1/3)
                        Group::make()
                            ->columnSpan(1)
                            ->hidden(fn($operation) => $operation === 'create')
                            ->schema([
                                Section::make('Informações do Software')
                                    ->schema([
                                        Placeholder::make('created_at')
                                            ->label('Data de Cadastro')
                                            ->content(fn($record) => $record?->data_cadastro ? \Carbon\Carbon::parse($record->data_cadastro)->format('d/m/Y H:i:s') : '-'),

                                        Placeholder::make('id')
                                            ->label('ID do Software')
                                            ->content(fn($record) => '#' . $record?->id),

                                        Placeholder::make('dicas')
                                            ->label('Dicas para edição')
                                            ->content(new HtmlString('
                                                <ul class="list-disc pl-4 text-sm text-gray-500">
                                                    <li>Mantenha códigos únicos</li>
                                                    <li>Use nomes descritivos</li>
                                                    <li>Atualize a versão quando necessário</li>
                                                </ul>
                                            ')),
                                    ]),

                                Section::make('API Key do Software')
                                    ->schema([
                                        Placeholder::make('api_key_hint_view')
                                            ->label('Últimos caracteres visíveis')
                                            ->content(fn($record) => '****' . $record?->api_key_hint),

                                        Placeholder::make('api_key_date')
                                            ->label('Gerada em')
                                            ->content(fn($record) => $record?->api_key_gerada_em?->format('d/m/Y H:i:s') ?? '-'),

                                        Actions::make([
                                            Action::make('regenerar_api_key')
                                                ->label('Gerar nova API key')
                                                ->color('warning')
                                                ->icon('heroicon-o-key')
                                                ->requiresConfirmation()
                                                ->modalHeading('Gerar nova API Key')
                                                ->modalDescription('A chave atual será revogada imediatamente após gerar uma nova. Essa ação não pode ser desfeita.')
                                                ->modalSubmitActionLabel('Sim, gerar nova chave')
                                                ->action(function ($record) {
                                                    $novaApiKey = 'sk_' . bin2hex(random_bytes(16));
                                                    $hashKey = hash('sha256', $novaApiKey);
                                                    $apiKeyHint = substr($novaApiKey, -6);

                                                    $record->update([
                                                        'api_key_hash' => $hashKey,
                                                        'api_key_hint' => $apiKeyHint,
                                                        'api_key_gerada_em' => now(),
                                                    ]);

                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Nova API Key Gerada')
                                                        ->body("**Nova Chave:** `{$novaApiKey}`\n\nCopie esta chave agora.")
                                                        ->success()
                                                        ->persistent()
                                                        ->send();
                                                }),
                                        ]),

                                        Placeholder::make('aviso_key')
                                            ->content('A chave atual será revogada imediatamente após gerar uma nova.')
                                            ->extraAttributes(['class' => 'text-xs text-gray-400 italic']),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Lista de Softwares Cadastrados')
            ->columns([

                Tables\Columns\TextColumn::make('codigo')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('nome_software')
                    ->label('Nome do Software')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('versao')
                    ->label('Versão')
                    ->sortable()
                    ->searchable(),
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
                    }),
                Tables\Columns\TextColumn::make('api_key_hint')
                    ->label('API Key')
                    ->formatStateUsing(function ($state, \App\Models\Software $record) {
                        $date = $record->api_key_gerada_em ? $record->api_key_gerada_em->format('d/m/Y H:i') : '';
                        return "<div class='text-xs'>
                                    <span class='font-mono'>****{$state}</span><br>
                                    <span class='text-gray-500'>{$date}</span>
                                </div>";
                    })
                    ->html(),
                Tables\Columns\TextColumn::make('data_cadastro')
                    ->label('Data Cadastro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-m-pencil-square')
                    ->button()
                    ->color('primary')
                    ->tooltip('Editar'),
                Tables\Actions\Action::make('toggle_status')
                    ->label('')
                    ->icon(fn(Software $record) => $record->status ? 'heroicon-m-pause' : 'heroicon-m-play')
                    ->button()
                    ->color('warning')
                    ->action(fn(Software $record) => $record->update(['status' => !$record->status]))
                    ->requiresConfirmation()
                    ->tooltip(fn(Software $record) => $record->status ? 'Inativar' : 'Ativar'),
                Tables\Actions\DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-m-trash')
                    ->button()
                    ->color('danger')
                    ->tooltip('Excluir'),
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
            'index' => Pages\ListSoftware::route('/'),
            'create' => Pages\CreateSoftware::route('/create'),
            'edit' => Pages\EditSoftware::route('/{record}/edit'),
        ];
    }
}
