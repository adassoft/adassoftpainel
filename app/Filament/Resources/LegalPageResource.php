<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalPageResource\Pages;
use App\Filament\Resources\LegalPageResource\RelationManagers;
use App\Models\LegalPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LegalPageResource extends Resource
{
    protected static ?string $model = LegalPage::class;

    protected static ?string $navigationGroup = 'Conteúdo & Suporte';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo')
                            ->default(true),

                        Forms\Components\RichEditor::make('content')
                            ->label('Conteúdo (HTML)')
                            ->required()
                            ->columnSpanFull()
                            ->hintAction(
                                Forms\Components\Actions\Action::make('generate_ai')
                                    ->label('Gerar Texto com IA')
                                    ->icon('heroicon-m-sparkles')
                                    ->color('primary')
                                    ->form([
                                        Forms\Components\Textarea::make('prompt_context')
                                            ->label('O que deve conter neste documento?')
                                            ->placeholder('Ex: Política de privacidade para software SaaS seguindo a LGPD. Coletamos nome, email e CNPJ...')
                                            ->required()
                                            ->rows(4)
                                    ])
                                    ->action(function ($data, Forms\Set $set, \App\Services\GeminiService $gemini) {
                                        $context = $data['prompt_context'];
                                        $prompt = "Atue como um advogado especialista em direito digital brasileiro. Escreva um documento HTML estruturado (apenas o conteúdo do body, sem html/head/body tags) para a seguinte solicitação:\n\n{$context}\n\nUse formatação HTML adequada (h2, p, ul, li).";

                                        \Filament\Notifications\Notification::make()->title('Gerando conteúdo... aguarde.')->info()->send();

                                        $result = $gemini->generateContent($prompt);

                                        if ($result['success']) {
                                            $set('content', $result['reply']);
                                            \Filament\Notifications\Notification::make()->title('Conteúdo Gerado!')->success()->send();
                                        } else {
                                            \Filament\Notifications\Notification::make()->title('Erro na IA')->body($result['error'])->danger()->send();
                                        }
                                    })
                            ),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Atualização')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListLegalPages::route('/'),
            'create' => Pages\CreateLegalPage::route('/create'),
            'edit' => Pages\EditLegalPage::route('/{record}/edit'),
        ];
    }
}
