<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TicketResource\Pages;
use App\Filament\App\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes do Problema')
                    ->description('Antes de abrir o chamado, preencha abaixo e clique em Consultar IA para tentar uma solução imediata.')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Assunto')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->label('Descrição Detalhada')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->footerActions([
                        Forms\Components\Actions\Action::make('consult_ai')
                            ->label('Consultar IA (Verificar Solução)')
                            ->icon('heroicon-m-sparkles')
                            ->color('primary')
                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                $subject = $get('subject');
                                $desc = strip_tags($get('description') ?? ''); // Remove tags HTML do RichEditor
                    
                                if (!$subject || strlen($desc) < 10) {
                                    \Filament\Notifications\Notification::make()->warning()->title('Preencha assunto e descrição detalhada')->send();
                                    return;
                                }

                                \Filament\Notifications\Notification::make()->info()->title('Consultando Base de Conhecimento e IA...')->send();

                                $service = new \App\Services\GeminiService();
                                $response = $service->analyzeTicketRequest($subject, $desc);

                                if ($response['success'] ?? false) {
                                    $set('ai_response', $response['reply']);
                                    $set('show_ai', true);
                                    \Filament\Notifications\Notification::make()->success()->title('Análise concluída! Veja a sugestão abaixo.')->send();
                                } else {
                                    \Filament\Notifications\Notification::make()->danger()->title('Erro na IA')->body($response['error'] ?? 'Ocorreu um erro')->send();
                                }
                            })
                    ]),

                Forms\Components\Section::make('Sugestão Inteligente')
                    ->visible(fn(Forms\Get $get) => $get('show_ai') == true)
                    ->icon('heroicon-o-light-bulb')
                    ->schema([
                        Forms\Components\MarkdownEditor::make('ai_response')
                            ->label('Análise da IA')
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('help_text')
                            ->content('Se a sugestão acima resolveu seu problema, você não precisa enviar este formulário! Caso contrário, prossiga abaixo.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Classificação e Envio')
                    ->schema([
                        Forms\Components\Select::make('priority')
                            ->label('Prioridade')
                            ->options([
                                'low' => 'Baixa',
                                'medium' => 'Média',
                                'high' => 'Alta',
                                'critical' => 'Crítica'
                            ])
                            ->default('low')
                            ->required(),

                        Forms\Components\Select::make('software_id')
                            ->label('Software Relacionado')
                            ->relationship('software', 'nome_software')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Hidden::make('user_id')->default(auth()->id()),
                        Forms\Components\Hidden::make('show_ai')->default(false),
                        Forms\Components\Hidden::make('ai_checked')->default(false), // Controle de fluxo
                        Forms\Components\Hidden::make('status')->default('open'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn(Builder $query) => $query->where('user_id', auth()->id())->orderBy('created_at', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#ID')->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Assunto')
                    ->searchable()
                    ->limit(50)
                    ->description(fn(Ticket $record) => \Illuminate\Support\Str::limit(strip_tags($record->description), 50)),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'open' => 'Aberto',
                        'in_progress' => 'Em Análise',
                        'answered' => 'Respondido',
                        'closed' => 'Fechado',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'open',
                        'warning' => 'in_progress',
                        'success' => 'answered',
                        'danger' => 'closed',
                    ]),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d/m/Y H:i')->label('Última Atualização'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Aberto',
                        'in_progress' => 'Em Análise',
                        'answered' => 'Respondido',
                        'closed' => 'Fechado',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // Sem delete bulk para cliente por segurança, ou permitir se closed? Melhor proteger.
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}
