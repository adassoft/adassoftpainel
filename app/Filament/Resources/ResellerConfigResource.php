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

    protected static ?string $navigationGroup = 'Revenda';
    protected static ?int $navigationSort = 4;

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

                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('suggest_colors_ia')
                                ->label('Sugerir Cores via IA')
                                ->icon('heroicon-m-sparkles')
                                ->color('violet')
                                ->size('sm')
                                ->tooltip('Analisa a Logo e sugere gradiente harmônico')
                                ->action(function (Forms\Get $get, Forms\Set $set) {
                                    $logoPath = $get('logo_path');

                                    // Trata array do FileUpload
                                    if (is_array($logoPath)) {
                                        $logoPath = reset($logoPath);
                                    }

                                    if (!$logoPath) {
                                        \Filament\Notifications\Notification::make()->warning()->title('Logo não encontrada')->body('Faça o upload da logo e salve (ou aguarde o upload terminar) antes de pedir sugestão.')->send();
                                        return;
                                    }

                                    $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath);

                                    if (!file_exists($fullPath)) {
                                        \Filament\Notifications\Notification::make()->danger()->title('Arquivo inacessível')->body('Não foi possível ler o arquivo da imagem. Salve o formulário primeiro.')->send();
                                        return;
                                    }

                                    try {
                                        $imageData = file_get_contents($fullPath);
                                        $base64 = base64_encode($imageData);
                                        $mimeType = mime_content_type($fullPath);

                                        \Filament\Notifications\Notification::make()->info()->title('Analisando cores...')->body('Consultando IA...')->send();

                                        $service = new \App\Services\GeminiService();
                                        $prompt = "Atue como um Designer de UI Sênior. Analise esta logo. Identifique a cor dominante principal e uma cor secundária harmônica para criar um degradê (gradiente) moderno e profissional. Retorne APENAS um JSON estrito (sem markdown) no formato: {\"start\": \"#colorHex\", \"end\": \"#colorHex\"}. Exemplo: {\"start\": \"#1a2980\", \"end\": \"#26d0ce\"}.";

                                        $response = $service->generateContent($prompt, $base64, $mimeType);

                                        if (!$response['success']) {
                                            throw new \Exception($response['error'] ?? 'Erro desconhecido na IA.');
                                        }

                                        $reply = $response['reply'];
                                        $jsonString = preg_replace('/^`{3}json\s*|\s*`{3}$/m', '', $reply);
                                        $colors = json_decode($jsonString, true);

                                        if (json_last_error() === JSON_ERROR_NONE && isset($colors['start']) && isset($colors['end'])) {
                                            $colors['accent'] = $colors['accent'] ?? $colors['start'];
                                            $colors['secondary'] = $colors['secondary'] ?? '#858796';

                                            $set('cor_primaria_gradient_start', $colors['start']);
                                            $set('cor_primaria_gradient_end', $colors['end']);
                                            $set('cor_acento', $colors['accent']);
                                            $set('cor_secundaria', $colors['secondary']);

                                            \Filament\Notifications\Notification::make()->success()->title('Paleta Aplicada!')->body("Sugerido: {$colors['start']} -> {$colors['end']}")->send();
                                        } else {
                                            throw new \Exception("Formato inválido da IA.");
                                        }

                                    } catch (\Exception $e) {
                                        \Filament\Notifications\Notification::make()->danger()->title('Erro')->body($e->getMessage())->send();
                                    }
                                }),

                            Forms\Components\Actions\Action::make('reset_colors')
                                ->label('Restaurar Padrão')
                                ->icon('heroicon-m-arrow-path')
                                ->color('gray')
                                ->size('sm')
                                ->action(function (Forms\Set $set) {
                                    $set('cor_primaria_gradient_start', '#4e73df');
                                    $set('cor_primaria_gradient_end', '#224abe');
                                }),
                        ])->fullWidth(),

                        Forms\Components\ColorPicker::make('cor_primaria_gradient_start')
                            ->label('Cor Início (Gradiente)')
                            ->default('#1a2980')
                            ->regex('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'),

                        Forms\Components\ColorPicker::make('cor_primaria_gradient_end')
                            ->label('Cor Fim (Gradiente)')
                            ->default('#26d0ce')
                            ->regex('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'),

                        Forms\Components\ColorPicker::make('cor_acento')
                            ->label('Cor de Acento (Botões)')
                            ->default('#4e73df')
                            ->regex('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'),

                        Forms\Components\ColorPicker::make('cor_secundaria')
                            ->label('Cor Secundária (Detalhes)')
                            ->default('#858796')
                            ->regex('/^#([a-f0-9]{6}|[a-f0-9]{3})$/i'),

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
                    ])->columns(2), // Fim schema Visual

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

                Forms\Components\Section::make('Marketing e Rastreamento')
                    ->schema([
                        Forms\Components\TextInput::make('google_analytics_id')
                            ->label('Google Analytics 4 (GA4)')
                            ->placeholder('G-XXXXXXXXXX')
                            ->helperText(new \Illuminate\Support\HtmlString('ID da Métrica (G-...) do <a href="https://analytics.google.com/" target="_blank">Google Analytics</a>.')),
                        Forms\Components\TextInput::make('facebook_pixel_id')
                            ->label('Facebook Pixel ID')
                            ->placeholder('123456789012345')
                            ->numeric()
                            ->helperText(new \Illuminate\Support\HtmlString('ID do Dataset no <a href="https://business.facebook.com/events_manager2" target="_blank">Events Manager</a>.')),
                        Forms\Components\TextInput::make('microsoft_clarity_id')
                            ->label('Microsoft Clarity ID')
                            ->placeholder('Ex: j4l920ls9')
                            ->helperText(new \Illuminate\Support\HtmlString('Project ID nas <a href="https://clarity.microsoft.com/projects" target="_blank">Configurações</a> do Clarity.')),
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
