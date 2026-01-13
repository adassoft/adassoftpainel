<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailLogResource\Pages;
use App\Filament\Resources\EmailLogResource\RelationManagers;
use App\Models\EmailLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmailLogResource extends Resource
{
    protected static ?string $model = EmailLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationLabel = 'Logs de Email';
    protected static ?string $modelLabel = 'Log de Email';
    protected static ?string $pluralModelLabel = 'Logs de Email';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 2; // After ManageEmail

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('recipient')
                    ->label('Destinatário'),
                Forms\Components\TextInput::make('subject')
                    ->label('Assunto'),
                Forms\Components\TextInput::make('status')
                    ->label('Status'),
                Forms\Components\DateTimePicker::make('sent_at')
                    ->label('Enviado em'),
                Forms\Components\Textarea::make('body')
                    ->label('Conteúdo')
                    ->columnSpanFull()
                    ->rows(10),
                Forms\Components\Textarea::make('error_message')
                    ->label('Erro')
                    ->columnSpanFull()
                    ->visible(fn($record) => $record && $record->status === 'failed'),
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
                Tables\Columns\TextColumn::make('recipient')
                    ->label('Destinatário')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Assunto')
                    ->searchable()
                    ->limit(50),
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
                //
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
            'index' => Pages\ListEmailLogs::route('/'),
            // 'create' => Pages\CreateEmailLog::route('/create'), // Read Only
            'view' => Pages\ViewEmailLog::route('/{record}'),
            // 'edit' => Pages\EditEmailLog::route('/{record}/edit'), // Read Only
        ];
    }
}
