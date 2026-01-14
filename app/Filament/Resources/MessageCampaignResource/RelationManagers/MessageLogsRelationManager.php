<?php

namespace App\Filament\Resources\MessageCampaignResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MessageLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'messageLogs';

    protected static ?string $title = 'Logs de Envio';

    protected static ?string $modelLabel = 'Log de Mensagem';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('recipient')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('recipient')
            ->columns([
                Tables\Columns\TextColumn::make('recipient')
                    ->label('Destinatário')
                    ->searchable(),
                Tables\Columns\TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'whatsapp' => 'success',
                        'email' => 'info',
                        'sms' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'sent' => 'success',
                        'failed' => 'danger',
                        'pending' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Erro')
                    ->color('danger')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->error_message),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Enviado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make()->modalHeading('Detalhes do Envio')->form([
                    Forms\Components\TextInput::make('recipient')->label('Destinatário'),
                    Forms\Components\TextInput::make('channel')->label('Canal'),
                    Forms\Components\TextInput::make('status'),
                    Forms\Components\Textarea::make('body')->label('Mensagem Enviada')->rows(10),
                    Forms\Components\Textarea::make('error_message')->label('Erro (se houver)')->rows(3),
                ]),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
