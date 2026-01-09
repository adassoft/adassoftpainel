<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\MercadoLibreItem;
use App\Models\MercadoLibreConfig;
use App\Models\Download;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use Filament\Support\Enums\FontWeight;
use Filament\Actions\Action as HeaderAction;

class MercadoLibreProducts extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Anúncios ML';
    protected static ?string $title = 'Anúncios do Mercado Livre';
    protected static ?string $slug = 'mercado-libre-products';
    protected static ?string $navigationGroup = 'Mercado Livre';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.mercado-libre-products';

    public function table(Table $table): Table
    {
        return $table
            ->query(MercadoLibreItem::query()->latest('last_synced_at'))
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('thumbnail')
                        ->height('100%')
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-contain h-48 w-full bg-gray-50 rounded-lg']),

                    Tables\Columns\TextColumn::make('title')
                        ->weight(FontWeight::Bold)
                        ->searchable()
                        ->limit(50),

                    Tables\Columns\TextColumn::make('price')
                        ->money('BRL')
                        ->color('success')
                        ->prefix('R$ '),

                    Tables\Columns\TextColumn::make('download.titulo')
                        ->label('Vinculado a')
                        ->icon('heroicon-m-link')
                        ->color('primary')
                        ->getStateUsing(function ($record) {
                            return $record->download ? 'Vinculado: ' . $record->download->titulo : 'Não vinculado';
                        })
                        ->badge()
                        ->color(fn($state) => str_contains($state, 'Não') ? 'gray' : 'primary'),

                    Tables\Columns\TextColumn::make('status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'active' => 'success',
                            'paused' => 'warning',
                            'closed' => 'danger',
                            default => 'gray',
                        }),

                    Tables\Columns\TextColumn::make('ml_id')
                        ->label('ID')
                        ->color('gray')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall),
                ])->space(3),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Ativo',
                        'paused' => 'Pausado',
                        'closed' => 'Fechado',
                    ]),
                Tables\Filters\TernaryFilter::make('linked')
                    ->label('Vinculação')
                    ->placeholder('Todos')
                    ->trueLabel('Vinculados')
                    ->falseLabel('Não Vinculados')
                    ->queries(
                        true: fn($query) => $query->whereNotNull('download_id'),
                        false: fn($query) => $query->whereNull('download_id'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make('link')
                    ->label('Vincular')
                    ->icon('heroicon-m-link')
                    ->modalHeading('Vincular Produto Local')
                    ->modalDescription('Escolha qual produto digital será entregue quando este anúncio for vendido.')
                    ->form([
                        Select::make('download_id')
                            ->label('Produto Digital (Download)')
                            ->options(Download::query()->pluck('titulo', 'id'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Ao vender este anúncio no ML, o sistema entregará uma licença deste produto.'),
                    ]),

                Tables\Actions\Action::make('open_ml')
                    ->label('Ver no ML')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn(MercadoLibreItem $record) => $record->permalink)
                    ->openUrlInNewTab()
                    ->color('gray'),
            ])
            ->headerActions([
                //
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('sync')
                ->label('Sincronizar Anúncios')
                ->icon('heroicon-m-arrow-path')
                ->action(function () {
                    $this->syncProducts();
                }),
        ];
    }

    public function syncProducts()
    {
        $config = MercadoLibreConfig::where('is_active', true)->first();

        if (!$config) {
            Notification::make()->title('Integração não configurada.')->warning()->send();
            return;
        }

        try {
            // 1. Busca IDs (Active e Paused para garantir)
            // Limit 100 para exemplo inicial
            $idsResponse = Http::withToken($config->access_token)
                ->get("https://api.mercadolibre.com/users/{$config->ml_user_id}/items/search", [
                    'limit' => 100,
                    'orders' => 'start_time_desc'
                ]);

            if ($idsResponse->failed()) {
                throw new \Exception('Falha na busca: ' . $idsResponse->body());
            }

            $mlIds = $idsResponse->json()['results'] ?? [];

            if (empty($mlIds)) {
                Notification::make()->title('Nenhum anúncio encontrado.')->info()->send();
                return;
            }

            // 2. Multiget Details
            $chunks = array_chunk($mlIds, 20);
            $count = 0;

            foreach ($chunks as $chunk) {
                $detailsResponse = Http::withToken($config->access_token)
                    ->get("https://api.mercadolibre.com/items", [
                        'ids' => implode(',', $chunk),
                    ]);

                if ($detailsResponse->successful()) {
                    $items = $detailsResponse->json();
                    foreach ($items as $itemWrapper) {
                        if (($itemWrapper['code'] ?? 0) === 200) {
                            $data = $itemWrapper['body'];

                            MercadoLibreItem::updateOrCreate(
                                ['ml_id' => $data['id']],
                                [
                                    'title' => $data['title'],
                                    'price' => $data['price'],
                                    'currency_id' => $data['currency_id'],
                                    'available_quantity' => $data['available_quantity'],
                                    'sold_quantity' => $data['sold_quantity'] ?? 0,
                                    'status' => $data['status'],
                                    'permalink' => $data['permalink'],
                                    'thumbnail' => $data['thumbnail'],
                                    'ml_user_id' => $data['seller_id'],
                                    'last_synced_at' => now(),
                                    'company_id' => $config->company_id, // Link to tenant if applicable
                                ]
                            );
                            $count++;
                        }
                    }
                }
            }

            Notification::make()->title("Sincronização concluída: {$count} anúncios processados.")->success()->send();

        } catch (\Exception $e) {
            Notification::make()->title('Erro ao sincronizar: ' . $e->getMessage())->danger()->send();
        }
    }
}
