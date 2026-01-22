<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Download;
use Illuminate\Database\Eloquent\Builder;

class DownloadsRankingWidget extends BaseWidget
{
    protected static ?string $heading = 'Ranking de Downloads (Top 10)';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Download::query()
                    ->withCount('logs') // Conta logs (Tracker Novo)
                    ->orderByDesc('logs_count')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\ImageColumn::make('imagem_url')
                    ->label('Ícone')
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=DL&background=0D8ABC&color=fff&rounded=true')
                    ->extraImgAttributes(['class' => 'object-contain'])
                    ->circular(),

                Tables\Columns\TextColumn::make('titulo')
                    ->label('Nome do Software')
                    ->description(fn(Download $record) => $record->slug)
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('versao')
                    ->label('Versão')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('software.id') // Check if relation exists
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => $state ? 'Software Oficial' : 'Pacote Extra')
                    ->badge()
                    ->color(fn($state) => $state === 'Software Oficial' ? 'success' : 'gray'),

                Tables\Columns\TextColumn::make('logs_count')
                    ->label('Downloads (Tracker)')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                Tables\Columns\TextColumn::make('contador')
                    ->label('Total Histórico')
                    ->sortable()
                    ->color('gray'),
            ])
            ->paginated(false)
            ->striped();
    }
}
