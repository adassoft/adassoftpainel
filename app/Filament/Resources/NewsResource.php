<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use App\Models\Software;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Notícias e Avisos';
    protected static ?string $modelLabel = 'Notícia';
    protected static ?string $pluralModelLabel = 'Notícias e Avisos';
    protected static ?string $navigationGroup = 'Conteúdo & Suporte';
    protected static ?int $navigationSort = 4;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalhes da Notícia')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('software_id')
                                ->label('Software Relacionado')
                                ->options(Software::pluck('nome_software', 'id'))
                                ->searchable()
                                ->placeholder('Geral (Todos os Softwares)'),

                            Forms\Components\Select::make('publico')
                                ->label('Público Alvo')
                                ->options([
                                    'revenda' => 'Apenas Revendas',
                                    'cliente' => 'Apenas Clientes',
                                    'todos' => 'Todos (Revendas e Clientes)',
                                ])
                                ->required()
                                ->default('todos'),

                            Forms\Components\Select::make('prioridade')
                                ->label('Prioridade')
                                ->options([
                                    'baixa' => 'Baixa',
                                    'normal' => 'Normal',
                                    'alta' => 'Alta (Destaque)',
                                ])
                                ->required()
                                ->default('normal'),
                        ]),

                        Forms\Components\TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('conteudo')
                            ->label('Conteúdo')
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('link_acao')
                                ->label('Link de Ação (Opcional)')
                                ->url()
                                ->suffixIcon('heroicon-m-link'),

                            Forms\Components\Select::make('tipo')
                                ->label('Tipo de Mensagem')
                                ->options([
                                    'manual' => 'Manual',
                                    'automatico' => 'Automática (Sistema)',
                                ])
                                ->default('manual')
                                ->disabled(fn($record) => $record && $record->tipo === 'automatico') // Evita editar tipo de msg auto
                                ->helperText('Mensagens automáticas geralmente são geradas por eventos do sistema.'),
                        ]),

                        Forms\Components\Toggle::make('ativa')
                            ->label('Publicado / Ativo')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('titulo')
                    ->searchable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('software.nome_software')
                    ->label('Software')
                    ->placeholder('Geral')
                    ->badge(),

                Tables\Columns\TextColumn::make('publico')
                    ->label('Público')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'revenda' => 'warning',
                        'cliente' => 'success',
                        'todos' => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'revenda' => 'Revendas',
                        'cliente' => 'Clientes',
                        'todos' => 'Todos',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('prioridade')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'alta' => 'danger',
                        'normal' => 'primary',
                        'baixa' => 'gray',
                    }),

                Tables\Columns\IconColumn::make('ativa')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('publico')
                    ->options([
                        'revenda' => 'Revendas',
                        'cliente' => 'Clientes',
                        'todos' => 'Todos',
                    ]),
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'manual' => 'Manual',
                        'automatico' => 'Automático',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
