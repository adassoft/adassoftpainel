<?php

namespace App\Filament\Pages;

use App\Models\Configuration;
use App\Models\Software;
use App\Services\GeminiService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\HtmlString;

class ManageSeoAnalyzer extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationLabel = 'Analisador SEO (IA)';

    protected static ?string $title = 'Analisador de SEO com Inteligência Artificial';

    protected static ?string $navigationGroup = 'Gestão';

    protected static string $view = 'filament.pages.manage-seo-analyzer';

    public ?array $data = [];
    public ?string $analysisResult = null;
    public bool $isAnalyzing = false;
    public ?array $currentPreview = [];

    public function mount(): void
    {
        $this->form->fill([
            'page_type' => 'home',
        ]);

        $this->updatePreview();
    }

    public function updatedDataPageType()
    {
        $this->updatePreview();
    }

    public function updatedDataProductId()
    {
        $this->updatePreview();
    }

    protected function updatePreview(): void
    {
        $pageType = $this->data['page_type'] ?? 'home';
        $productId = $this->data['product_id'] ?? null;

        // Load Global SEO Config
        $config = Configuration::where('chave', 'seo_config')->first();
        $seoGlobal = $config ? json_decode($config->valor, true) : [];

        $preview = [
            'site' => $seoGlobal['site_name'] ?? 'N/A',
            'title_base' => $seoGlobal['site_title'] ?? 'N/A',
            'target_info' => '',
        ];

        if ($pageType === 'home') {
            $preview['target_info'] = "Alvo: Home (index.php)\nUsa Título Global e Descrição Global.";
        } elseif ($pageType === 'revenda') {
            $preview['target_info'] = "Alvo: Revenda (lp_revenda.php)\nUsa conteúdo estático da Landing Page.";
        } elseif ($pageType === 'produto' && $productId) {
            $prod = Software::find($productId);
            if ($prod) {
                $preview['target_info'] = "Alvo: Produto ID {$prod->id}\nNome: {$prod->nome_software}\nDados virão do Banco de Dados.";
            } else {
                $preview['target_info'] = "Produto não encontrado.";
            }
        } elseif ($pageType === 'produto') {
            $preview['target_info'] = "Selecione um produto.";
        }

        $this->currentPreview = $preview;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(4)
                            ->schema([
                                Section::make('Selecione o Alvo')
                                    ->extraAttributes(['class' => 'seo-analyzer-target'])
                                    ->schema([
                                        Select::make('page_type')
                                            ->label('Tipo de Página')
                                            ->options([
                                                'home' => 'Página Inicial (Loja)',
                                                'revenda' => 'Página de Revenda (LP)',
                                                'produto' => 'Página de Produto',
                                            ])
                                            ->live()
                                            ->required(),

                                        Select::make('product_id')
                                            ->label('Selecione o Produto')
                                            ->options(Software::query()->where('status', 1)->pluck('nome_software', 'id'))
                                            ->visible(fn(\Filament\Forms\Get $get) => $get('page_type') === 'produto')
                                            ->live()
                                            ->required(fn(\Filament\Forms\Get $get) => $get('page_type') === 'produto'),

                                        TextInput::make('keywords')
                                            ->label('Palavras-chave Alvo (Opcional)')
                                            ->placeholder('Ex: automação comercial, pdv delphi')
                                            ->helperText('A IA verificará a otimização para estes termos.'),

                                        ViewField::make('analysis_note')
                                            ->view('filament.forms.components.seo-analysis-note'),

                                        \Filament\Forms\Components\Actions::make([
                                            \Filament\Forms\Components\Actions\Action::make('analyze')
                                                ->label('Analisar Agora')
                                                ->icon('heroicon-o-magnifying-glass')
                                                ->color('primary')
                                                ->action('analyze'),
                                        ])->fullWidth(),
                                    ]),

                                Section::make('Dados Atuais (Preview)')
                                    ->extraAttributes(['class' => 'seo-analyzer-preview'])
                                    ->schema([
                                        ViewField::make('preview_data')
                                            ->view('filament.forms.components.seo-preview-data'),
                                    ]),
                            ]),

                        Grid::make(1)
                            ->columnSpan(8)
                            ->schema([
                                Section::make('Relatório da IA')
                                    ->extraAttributes(['class' => 'seo-analyzer-report'])
                                    ->headerActions([
                                        // Dropdown placeholder if needed, typically actions go here
                                    ])
                                    ->schema([
                                        ViewField::make('report_content')
                                            ->view('filament.forms.components.seo-report-content'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function analyze(): void
    {
        $this->isAnalyzing = true;
        // Force repaint or handle loading state in frontend via Livewire loading states

        $data = $this->form->getState();
        $pageType = $data['page_type'];
        $keywords = $data['keywords'] ?? '';
        $productId = $data['product_id'] ?? 0;

        // Build Context (Replicating api_seo_analyze_prepare.php logic)
        $config = Configuration::where('chave', 'seo_config')->first();
        $seoConfig = $config ? json_decode($config->valor, true) : [];
        $siteName = $seoConfig['site_name'] ?? 'Adassoft Store';
        $siteDesc = $seoConfig['site_description'] ?? '';

        // Check local file existence logic (simulated)
        $sitemapExists = false; // In Filament context, we assume dynamic sitemap generation logic exists elsewhere

        $contexto = "";
        if (!empty($keywords)) {
            $contexto .= "Palavras-chave Alvo (Informadas pelo usuário): " . $keywords . "\n";
        }
        $contexto .= "Sitemap: " . ($sitemapExists ? "Presente" : "Ausente") . "\n";

        if ($pageType === 'home') {
            $contexto .= "Página: Home / Loja Principal (index.php)\n";
            $contexto .= "Título Configurado: " . ($seoConfig['site_title'] ?? 'Loja Oficial') . " | $siteName\n";
            $contexto .= "Meta Description: $siteDesc\n";
            $contexto .= "Objetivo: Vitrine de softwares, atrair revendedores e clientes finais.\n";
            $contexto .= "Estrutura HTML (Resumo): Navbar com links, Hero Header com CTA, Lista de filtros de categoria, Grid de Produtos com cards (Imagem, Título, Descrição curta, Preço, Botão Comprar), Footer com links sociais.\n";
        } elseif ($pageType === 'revenda') {
            $contexto .= "Página: Landing Page de Revenda (lp_revenda.php)\n";
            $contexto .= "Título (Tag Title): Seja Revendedor | Adassoft - Lucre com Recorrência\n";
            $contexto .= "Meta Description: Seja um parceiro revendedor Adassoft. Lucre com recorrência vendendo o melhor software de gestão.\n";
            $contexto .= "Conteúdo Principal (H1): 'Monte sua própria Fábrica de Software'.\n";
            $contexto .= "Pontos de Venda: White Label Total, Modelo Risco Zero, Segurança de Ponta.\n";
            $contexto .= "Produtos Listados: PDV Completo, Gestão de Clínicas, Gestão de Varejo.\n";
        } elseif ($pageType === 'produto') {
            if (!$productId) {
                Notification::make()->title('Erro')->body('Selecione um produto.')->danger()->send();
                $this->isAnalyzing = false;
                return;
            }
            $prod = Software::find($productId);
            if (!$prod) {
                Notification::make()->title('Erro')->body('Produto não encontrado.')->danger()->send();
                $this->isAnalyzing = false;
                return;
            }

            $contexto .= "Página: Detalhes do Produto (Software)\n";
            $contexto .= "Nome do Produto: " . $prod->nome_software . "\n";
            $contexto .= "Categoria: " . $prod->categoria . "\n";
            $contexto .= "Slug/URL (simulada): detalhes_produto.php?id=" . $prod->id . "\n";
            $contexto .= "Título Gerado: " . $prod->nome_software . " | $siteName\n";
            $contexto .= "Descrição (BD): " . $prod->descricao . "\n";
            $contexto .= "Imagem: " . $prod->imagem . "\n";
            $len = mb_strlen($prod->descricao);
            $contexto .= "Tamanho da descrição: $len caracteres.\n";
        }

        // Build Final Prompt
        $prompt = "Atue como um Especialista Sênior em SEO (Search Engine Optimization) e Copywriting. Eu vou te fornecer os dados de uma página web e você fará uma auditoria completa.\n\n";
        $prompt .= "DADOS DA PÁGINA:\n" . $contexto . "\n\n";
        $prompt .= "TAREFA:\n";
        $prompt .= "1. Analise a qualidade do Título e da Meta Description (tamanho, atratividade, palavras-chave).\n";
        if (!empty($keywords)) {
            $prompt .= "2. Verifique se as palavras-chave alvo informadas ('$keywords') estão bem aplicadas ou onde deveriam estar.\n";
        } else {
            $prompt .= "2. Identifique quais palavras-chave a página parece estar atacando atualmente.\n";
        }
        $prompt .= "3. Pontos fortes do conteúdo e metadados.\n";
        $prompt .= "4. Aponte 3 ou mais pontos de melhoria técnica ou de conteúdo.\n";
        $prompt .= "5. Sugira 5 palavras-chave estratégicas " . (empty($keywords) ? "para focar" : "adicionais/relacionadas") . ".\n";
        $prompt .= "6. Reescreva Título e Description focando em alta conversão (CTR).\n";
        $prompt .= "7. Dê uma nota de SEO (0 a 10).\n\n";
        $prompt .= "Responda em formato Markdown, estruturado.";

        // Call Service
        $gemini = new GeminiService();
        $response = $gemini->generateContent($prompt);

        if ($response['success']) {
            $this->analysisResult = $response['reply'];
            Notification::make()->title('Análise concluída!')->success()->send();
        } else {
            Notification::make()->title('Erro na Análise')->body($response['error'])->danger()->send();
            $this->analysisResult = "Erro: " . $response['error'];
        }

        $this->isAnalyzing = false;
    }
}
