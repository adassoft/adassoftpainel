<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\DownloadLog;
use Illuminate\Support\Facades\DB;

class DownloadsReferersWidget extends BaseWidget
{
    protected static ?string $heading = 'Top Origens (Referer)';
    protected static ?int $sort = 3; // Lado a lado com Pizza (sort 3 tambem?)

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DownloadLog::query()
                    ->select('referer', DB::raw('count(*) as total'), DB::raw('MAX(id) as id'))
                    ->whereNotNull('referer')
                    ->where('referer', '!=', '')
                    ->groupBy('referer')
                    ->orderByDesc('total')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('referer')
                    ->label('Origem')
                    ->formatStateUsing(function ($state) {
                        try {
                            $host = parse_url($state, PHP_URL_HOST);
                            return $host ?: 'Link Direto / Desconhecido';
                        } catch (\Exception $e) {
                            return $state;
                        }
                    })
                    ->description(fn($state) => \Illuminate\Support\Str::limit($state, 40))
                    ->tooltip(fn($state) => $state)
                    ->icon('heroicon-o-globe-alt')
                    ->copyable(),

                Tables\Columns\TextColumn::make('total')
                    ->label('Visitas')
                    ->badge()
                    ->alignEnd()
                    ->color('info'),
            ])
            ->paginated(false)
            ->striped();
    }
}
