<?php

namespace App\Filament\Reseller\Pages;

use App\Models\ResellerConfig;
use App\Models\ResellerConfigHistory;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;
use App\Services\GeminiService;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Storage;

class WhiteLabelPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Personalização (White Label)';
    protected static ?string $title = 'Personalização do Sistema';
    protected static string $view = 'filament.reseller.pages.white-label-page';

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    // Propriedades do Form
    public ?array $data = [];

    // Estado Atual
    public ?ResellerConfig $record = null;

    public function mount(): void
    {
        // Busca config do usuário logado
        $this->record = ResellerConfig::firstOrCreate(
            ['usuario_id' => Auth::id()],
            ['ativo' => false, 'status_aprovacao' => 'pendente']
        );

        // Preenche o formulário
        // Se tiver dados pendentes, usa eles (edição contínua), senão usa os aprovados
        $dadosIniciais = $this->record->dados_pendentes
            ? $this->record->dados_pendentes
            : $this->record->toArray();

        // Garante defaults visualmente
        $dadosIniciais['nome_sistema'] = $dadosIniciais['nome_sistema'] ?? 'Meu Sistema';
        $dadosIniciais['slogan'] = $dadosIniciais['slogan'] ?? '';
        $dadosIniciais['cor_primaria_gradient_start'] = $dadosIniciais['cor_primaria_gradient_start'] ?? '#1a2980';
        $dadosIniciais['cor_primaria_gradient_end'] = $dadosIniciais['cor_primaria_gradient_end'] ?? '#26d0ce';
        $dadosIniciais['logo_path'] = $dadosIniciais['logo_path'] ?? '';
        $dadosIniciais['icone_path'] = $dadosIniciais['icone_path'] ?? ''; // Novo
        $dadosIniciais['dominios'] = $dadosIniciais['dominios'] ?? '';
        $dadosIniciais['cor_acento'] = $dadosIniciais['cor_acento'] ?? '#4e73df';
        $dadosIniciais['cor_secundaria'] = $dadosIniciais['cor_secundaria'] ?? '#858796';

        $this->form->fill($dadosIniciais);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Identidade Visual')
                    ->description('Defina como seus clientes verão o sistema.')
                    ->schema([
                        TextInput::make('nome_sistema')
                            ->label('Nome do Sistema')
                            ->required()
                            ->maxLength(100)
                            ->live(), // Live update for preview
                        TextInput::make('slogan')
                            ->label('Slogan')
                            ->maxLength(255)
                            ->live(),

                        // Logo Principal (Retangular)
                        \Filament\Forms\Components\FileUpload::make('logo_path')
                            ->label('Logotipo Principal (Arrastar e Soltar)')
                            ->disk('public')
                            ->directory('logos-revenda')
                            ->image()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml'])
                            ->helperText('Formatos: PNG, JPG, SVG. Tamanho máx: 2MB.')
                            ->live()
                            ->columnSpanFull(),

                        // Ícone (Quadrado)
                        \Filament\Forms\Components\FileUpload::make('icone_path')
                            ->label('Símbolo/Ícone (Arrastar e Soltar)')
                            ->disk('public')
                            ->directory('icones-revenda')
                            ->image()
                            ->maxSize(1024)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml'])
                            ->helperText('Formatos: PNG, JPG, SVG. Tamanho máx: 1MB. Proporção 1:1.')
                            ->live()
                            ->columnSpanFull(),

                        // Cores
                        // Cores (Usando TextInput type color nativo para garantir compatibilidade)
                        // Cores
                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('suggestColors')
                                ->label('🎨 Sugerir Paleta Completa com IA')
                                ->action('analyzeLogoColor')
                                ->tooltip('A IA analisará sua logo e sugerirá gradientes, cores de destaque e tons secundários.')
                                ->color('violet')
                                ->icon('heroicon-m-sparkles'),
                        ])->fullWidth(),

                        Grid::make(2)->schema([
                            TextInput::make('cor_primaria_gradient_start')
                                ->label('Cor Degradê Início')
                                ->type('color')
                                ->required()
                                ->live(),
                            TextInput::make('cor_primaria_gradient_end')
                                ->label('Cor Degradê Fim')
                                ->type('color')
                                ->required()
                                ->live(),
                            TextInput::make('cor_acento')
                                ->label('Cor de Destaque (Botões)')
                                ->helperText('Usada nos botões de compra e chamadas para ação.')
                                ->type('color')
                                ->required()
                                ->live(),
                            TextInput::make('cor_secundaria')
                                ->label('Cor Secundária (Detalhes)')
                                ->helperText('Usada em ícones e textos de apoio.')
                                ->type('color')
                                ->required()
                                ->live(),
                        ]),
                    ]),

                Section::make('Configuração de Domínio')
                    ->schema([
                        TextInput::make('dominios')
                            ->label('Domínios Permitidos')
                            ->placeholder('ex: app.meuerp.com.br')
                            ->helperText('Separe por vírgula. Ex: meuerp.com.br, app.meuerp.com.br')
                            ->required(),

                        \Filament\Forms\Components\Placeholder::make('dns_warning')
                            ->hiddenLabel()
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="p-4 text-sm text-gray-700 bg-gray-50 rounded-lg border border-gray-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                                    <div class="flex items-start gap-3">
                                        <div class="text-blue-500 mt-0.5"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></div>
                                        <div>
                                            <span class="font-bold block mb-1">Configuração de DNS (Domínio Próprio):</span>
                                            <p class="mb-2">Se você usar um domínio fora de <code class="text-red-500">.adassoft.com</code>, é necessário criar uma entrada <strong>CNAME</strong> no seu provedor de registro apontando para:</p>
                                            <div class="bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded px-2 py-1 font-mono text-center text-red-500 mb-2">
                                                express.adassoft.com
                                            </div>
                                            <p class="text-xs text-gray-500">Exemplo: Se seu domínio for <em>app.meuerp.com.br</em>, crie um CNAME "app" apontando para "express.adassoft.com".</p>
                                        </div>
                                    </div>
                                </div>
                            ')),
                    ]),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        try {
            $dados = $this->form->getState();

            // Lógica de Atualização
            // Salva em "dados_pendentes" e muda status para "pendente"
            $this->record->dados_pendentes = $dados;
            $this->record->status_aprovacao = 'pendente';
            $this->record->mensagem_rejeicao = null;
            $this->record->save();

            // Log Histórico
            ResellerConfigHistory::create([
                'revenda_config_id' => $this->record->id,
                'acao' => 'solicitacao',
                'mensagem' => 'Alterações enviadas para análise.',
                'data_registro' => now(),
            ]);

            Notification::make()
                ->success()
                ->title('Solicitação enviada!')
                ->body('Suas alterações foram enviadas para aprovação.')
                ->send();

        } catch (Halt $exception) {
            return;
        }
    }

    public function getHistory()
    {
        if (!$this->record)
            return [];
        return $this->record->history()->orderBy('data_registro', 'desc')->get();
    }

    public function analyzeLogoColor(): void
    {
        // 1. Obter Logo
        $data = $this->form->getState();
        $logoPath = $data['logo_path'] ?? null;

        // Trata caso seja array (comportamento padrão FileUpload)
        if (is_array($logoPath)) {
            $logoPath = reset($logoPath);
        }

        if (!$logoPath) {
            Notification::make()->warning()->title('Logo não encontrada')->body('Faça o upload da logo primeiro e aguarde carregar.')->send();
            return;
        }

        // 2. Resolver Caminho Real
        // O FileUpload armazena caminho relativo ao disco public
        $fullPath = Storage::disk('public')->path($logoPath);

        if (!file_exists($fullPath)) {
            Notification::make()->danger()->title('Arquivo inacessível')->body('Não foi possível ler o arquivo da imagem. Salve o formulário primeiro se acabou de enviar.')->send();
            return;
        }

        // 3. Preparar Imagem para Gemini
        try {
            $imageData = file_get_contents($fullPath);
            $base64 = base64_encode($imageData);
            $mimeType = mime_content_type($fullPath);

            Notification::make()->info()->title('Analisando cores com IA...')->body('Por favor aguarde alguns segundos.')->send();

            // 4. Chamar IA
            $service = new GeminiService();
            $prompt = "Atue como um Designer de UI Sênior. Analise esta logo. Extraia uma paleta de cores profissional:
            1. 'start' e 'end': Duas cores para um gradiente de fundo moderno.
            2. 'accent': Uma cor de destaque vibrante (contraste alto) para botões de Compra/CTA.
            3. 'secondary': Uma cor sóbria para detalhes secundários.
            Retorne APENAS JSON estrito: {\"start\": \"#hex\", \"end\": \"#hex\", \"accent\": \"#hex\", \"secondary\": \"#hex\"}.";

            $response = $service->generateContent($prompt, $base64, $mimeType);

            if (!$response['success']) {
                throw new \Exception($response['error'] ?? 'Erro desconhecido na IA.');
            }

            // 5. Processar Resposta
            $reply = $response['reply'];

            // Limpeza básica para garantir JSON válido (remove markdown ```json ... ``` se houver)
            $jsonString = preg_replace('/^`{3}json\s*|\s*`{3}$/m', '', $reply);
            $colors = json_decode($jsonString, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($colors['start']) && isset($colors['end'])) {

                // Garante fallbacks se a IA alucinar e não mandar todas
                $colors['accent'] = $colors['accent'] ?? $colors['start'];
                $colors['secondary'] = $colors['secondary'] ?? '#858796';

                // Aplica colors
                $this->form->fill([
                    ...$data,
                    'cor_primaria_gradient_start' => $colors['start'],
                    'cor_primaria_gradient_end' => $colors['end'],
                    'cor_acento' => $colors['accent'],
                    'cor_secundaria' => $colors['secondary'],
                ]);

                Notification::make()->success()->title('Paleta Aplicada!')->body("Cores sugeridas com sucesso.")->send();

            } else {
                throw new \Exception("A IA não retornou um formato válido. Resposta: " . substr($reply, 0, 100));
            }

        } catch (\Exception $e) {
            Notification::make()->danger()->title('Falha na Análise')->body($e->getMessage())->send();
        }
    }
}
