<?php

namespace App\Filament\Reseller\Pages;

use App\Models\ResellerConfig;
use App\Models\ResellerConfigHistory;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;

class WhiteLabelPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Configurações';
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
        $dadosIniciais['dominios'] = $dadosIniciais['dominios'] ?? '';

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

                        \Filament\Forms\Components\FileUpload::make('logo_path')
                            ->label('Logotipo do Sistema')
                            ->image()
                            ->imageEditor()
                            ->maxSize(512) // 512 KB
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('150')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml'])
                            ->directory('logos-revenda')
                            ->visibility('public')
                            ->helperText('Máx: 512KB. Otimizado automaticamente (400x150px). Fundo transp. recomendado.')
                            ->columnSpanFull()
                            ->live(), // Importante para o preview

                        // Cores
                        ColorPicker::make('cor_primaria_gradient_start')
                            ->label('Cor Degradê Início')
                            ->required()
                            ->live(),
                        ColorPicker::make('cor_primaria_gradient_end')
                            ->label('Cor Degradê Fim')
                            ->required()
                            ->live(),
                    ])->columns(2),

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
}
