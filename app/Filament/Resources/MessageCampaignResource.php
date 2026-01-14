<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageCampaignResource\Pages;
use App\Filament\Resources\MessageCampaignResource\RelationManagers;
use App\Models\MessageCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MessageCampaignResource extends Resource
{
    protected static ?string $model = MessageCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone'; // Ícone de megafone/campanha

    protected static ?string $navigationLabel = 'Campanhas de Aviso';

    protected static ?string $modelLabel = 'Campanha';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Section::make('Detalhes da Mensagem')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('title')
                            ->label('Identificação da Campanha')
                            ->required()
                            ->maxLength(255),
                        \Filament\Forms\Components\RichEditor::make('message')
                            ->label('Conteúdo da Mensagem')
                            ->helperText('Variáveis disponíveis: {name}, {first_name}, {company}, {software}')
                            ->required()
                            ->columnSpanFull(),
                        \Filament\Forms\Components\CheckboxList::make('channels')
                            ->label('Canais de Envio')
                            ->options([
                                'whatsapp' => 'WhatsApp',
                                'email' => 'E-mail',
                                'sms' => 'SMS'
                            ])
                            ->required()
                            ->columns(3),
                    ]),

                \Filament\Forms\Components\Section::make('Público Alvo')
                    ->schema([
                        \Filament\Forms\Components\Select::make('target_software_id')
                            ->label('Filtrar por Software (Opcional)')
                            ->options(\App\Models\Software::all()->pluck('nome_software', 'id'))
                            ->searchable()
                            ->placeholder('Todos os Softwares'),

                        \Filament\Forms\Components\Select::make('target_license_status')
                            ->label('Status da Licença')
                            ->options([
                                'ativo' => 'Apenas Ativos',
                                'bloqueado' => 'Apenas Bloqueados',
                                'all' => 'Todos (Ativos e Bloqueados)'
                            ])
                            ->default('ativo')
                            ->required(),
                    ])->columns(2),

                \Filament\Forms\Components\Section::make('Agendamento e Status')
                    ->schema([
                        \Filament\Forms\Components\DateTimePicker::make('scheduled_at')
                            ->label('Agendar Envio (Deixe vazio para enviar agora)')
                            ->minDate(now()),

                        \Filament\Forms\Components\TextInput::make('status')
                            ->disabled()
                            ->default('draft'),

                        \Filament\Forms\Components\Placeholder::make('progress')
                            ->label('Progresso')
                            ->content(fn($record) => $record ? "{$record->processed_count} / {$record->total_targets}" : '-'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('title')
                    ->label('Campanha')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                \Filament\Tables\Columns\TextColumn::make('channels')
                    ->label('Canais')
                    ->formatStateUsing(function ($state) {
                        if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $state = is_array($decoded) ? $decoded : [$state];
                        }
                        return implode(', ', array_map('ucfirst', $state ?? []));
                    }),
                \Filament\Tables\Columns\TextColumn::make('processed_count')
                    ->label('Envios')
                    ->formatStateUsing(fn($record) => "{$record->processed_count} / {$record->total_targets}"),
                \Filament\Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Agendado para')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),

                \Filament\Tables\Actions\Action::make('dispatch')
                    ->label('Disparar Agora')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Iniciar Campanha')
                    ->modalDescription('Tem certeza? O envio começará imediatamente em background com intervalos de 1 a 2 minutos entre mensagens.')
                    ->visible(fn($record) => in_array($record->status, ['draft', 'failed']))
                    ->action(function ($record) {
                        // Inicia o processo
                        $record->update([
                            'status' => 'pending',
                            'scheduled_at' => now(), // Marca como agora se não tinha data
                        ]);

                        // Despacha o Job Principal
                        \App\Jobs\ProcessCampaignJob::dispatch($record);

                        \Filament\Notifications\Notification::make()
                            ->title('Campanha Iniciada')
                            ->body('As mensagens serão enviadas gradualmente para evitar bloqueios.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListMessageCampaigns::route('/'),
            'create' => Pages\CreateMessageCampaign::route('/create'),
            'edit' => Pages\EditMessageCampaign::route('/{record}/edit'),
        ];
    }
}
