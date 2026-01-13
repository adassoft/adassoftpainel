<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageLogResource\Pages;
use App\Filament\Resources\MessageLogResource\RelationManagers;
use App\Models\MessageLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MessageLogResource extends Resource
{
    protected static ?string $model = MessageLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Logs de Mensagens';
    protected static ?string $modelLabel = 'Log de Mensagem';
    protected static ?string $pluralModelLabel = 'Logs de Mensagens';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('channel')
                    ->label('Canal')
                    ->disabled(),
                Forms\Components\TextInput::make('recipient')
                    ->label('Destinatário')
                    ->disabled(),
                Forms\Components\TextInput::make('subject')
                    ->label('Assunto')
                    ->disabled(),
                Forms\Components\TextInput::make('status')
                    ->label('Status')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('sent_at')
                    ->label('Enviado em')
                    ->disabled(),
                Forms\Components\Textarea::make('body')
                    ->label('Conteúdo')
                    ->columnSpanFull()
                    ->rows(10)
                    ->disabled(),
                Forms\Components\Textarea::make('error_message')
                    ->label('Erro')
                    ->columnSpanFull()
                    ->visible(fn($record) => $record && $record->status === 'failed')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'email' => 'info',
                        'whatsapp' => 'success',
                        'sms' => 'primary',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('recipient')
                    ->label('Destinatário')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Assunto')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('sent_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Canal')
                    ->options([
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'sms' => 'SMS'
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'sent' => 'Enviado',
                        'failed' => 'Falha',
                        'pending' => 'Pendente'
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListMessageLogs::route('/'),
            'view' => Pages\ViewMessageLog::route('/{record}'),
        ];
    }
}
