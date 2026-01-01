<?php

namespace App\Filament\App\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Histórico de Mensagens';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\RichEditor::make('content')
                    ->label('Nova Resposta')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('user_id')->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Autor')
                    ->badge()
                    ->color(fn($record) => $record->user_id === auth()->id() ? 'info' : 'success'),

                Tables\Columns\TextColumn::make('content')
                    ->label('Mensagem')
                    ->html()
                    ->wrap(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Responder')
                    ->modalHeading('Nova Resposta')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->after(function ($livewire) {
                        $ticket = $livewire->getOwnerRecord();
                        if ($ticket->status === 'answered') {
                            $ticket->update(['status' => 'open']);
                        }
                    }),
            ])
            ->defaultSort('created_at', 'asc')
            ->paginated(false);
    }
}
