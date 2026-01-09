<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedirectLogResource\Pages;
use App\Models\RedirectLog;
use App\Models\Redirect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

class RedirectLogResource extends Resource
{
    protected static ?string $model = RedirectLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel = 'Monitor 404';
    protected static ?string $modelLabel = 'Erro 404';
    protected static ?string $pluralModelLabel = 'Erros 404 Pendentes';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_resolved', false)
            ->where('is_ignored', false)
            ->orderByDesc('last_accessed_at');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('path')
                    ->label('URL Não Encontrada')
                    ->searchable()
                    ->copyable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hits')
                    ->label('Tentativas')
                    ->sortable()
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('last_accessed_at')
                    ->label('Último Acesso')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn($record) => $record->user_agent ? Str::limit($record->user_agent, 30) : 'N/A'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('create_redirect')
                    ->label('Criar Redirecionamento')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('path')
                            ->label('De (Origem)')
                            ->default(fn($record) => $record->path)
                            ->readOnly()
                            ->required(),
                        Forms\Components\TextInput::make('target_url')
                            ->label('Para (Destino)')
                            ->placeholder('/nova-pagina')
                            ->required()
                            ->helperText('URL completa ou caminho relativo (ex: /sobre)'),
                        Forms\Components\Select::make('status_code')
                            ->label('Tipo')
                            ->options([
                                301 => '301 - Permanente',
                                302 => '302 - Temporário',
                            ])
                            ->default(301)
                            ->required(),
                    ])
                    ->action(function (RedirectLog $record, array $data) {
                        Redirect::create([
                            'path' => $data['path'],
                            'target_url' => $data['target_url'],
                            'status_code' => $data['status_code'],
                            'is_active' => true,
                        ]);
                        $record->delete();
                        Notification::make()->title('Redirecionamento Criado')->success()->send();
                    }),

                Tables\Actions\Action::make('ignore')
                    ->label('Ignorar')
                    ->icon('heroicon-o-eye-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn(RedirectLog $record) => $record->update(['is_ignored' => true])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('ignore_all')
                        ->label('Ignorar Selecionados')
                        ->icon('heroicon-o-eye-slash')
                        ->action(fn(\Illuminate\Database\Eloquent\Collection $records) => $records->each->update(['is_ignored' => true])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedirectLogs::route('/'),
        ];
    }
}
