<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\MercadoLibreConfig;
use Illuminate\Support\Facades\Http;
use Filament\Notifications\Notification;

class MercadoLibreProducts extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Anúncios ML';
    protected static ?string $title = 'Anúncios do Mercado Livre';
    protected static ?string $slug = 'mercado-libre-products';
    protected static ?string $navigationGroup = 'Mercado Livre';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.mercado-libre-products';

    public $products = [];
    public $isLoading = false;

    public function mount()
    {
        $this->loadProducts();
    }

    public function loadProducts()
    {
        $this->isLoading = true;

        $config = MercadoLibreConfig::where('is_active', true)->first();

        if (!$config) {
            Notification::make()->title('Integração não configurada ou desconectada.')->warning()->send();
            $this->isLoading = false;
            return;
        }

        try {
            // 1. Busca IDs dos produtos do usuário
            // status=active para pegar apenas ativos. Tire o parametro se quiser todos.
            $searchResponse = Http::withToken($config->access_token)
                ->get("https://api.mercadolibre.com/users/{$config->ml_user_id}/items/search", [
                    'status' => 'active',
                    'limit' => 50, // Paginação simples por enquanto
                ]);

            if ($searchResponse->failed()) {
                throw new \Exception('Falha ao buscar lista de itens: ' . $searchResponse->body());
            }

            $itemIds = $searchResponse->json()['results'] ?? [];

            if (empty($itemIds)) {
                $this->products = [];
                $this->isLoading = false;
                return;
            }

            // 2. Busca detalhes dos produtos (Multiget até 20 itens por vez na API oficial)
            $chunks = array_chunk($itemIds, 20);
            $this->products = [];

            foreach ($chunks as $chunk) {
                $idsString = implode(',', $chunk);
                $detailsResponse = Http::withToken($config->access_token)
                    ->get("https://api.mercadolibre.com/items", [
                        'ids' => $idsString,
                    ]);

                if ($detailsResponse->successful()) {
                    $itemsData = $detailsResponse->json();
                    // O retorno é [{code: 200, body: {...}}, ...]
                    foreach ($itemsData as $itemWrapper) {
                        if (($itemWrapper['code'] ?? 0) === 200) {
                            $this->products[] = $itemWrapper['body'];
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Notification::make()->title('Erro ao carregar produtos: ' . $e->getMessage())->danger()->send();
        }

        $this->isLoading = false;
    }
}
