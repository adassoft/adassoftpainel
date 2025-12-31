<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\News;

class NewsController extends Controller
{
    /**
     * Listar notícias disponíveis para o usuário autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = News::active();

        // Se usuário é revenda (acesso 2)
        if ($user && $user->acesso == 2) {
            $query->forReseller();
        } else {
            // Cliente normal (vê apenas 'todos')
            $query->forClient();
        }

        // Ordenação: Prioridade (Alta > Normal > Baixa) -> Data Desc
        $query->orderByRaw("FIELD(prioridade, 'alta', 'normal', 'baixa')")->latest();

        $news = $query->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $news->map(function ($item) {
                return [
                    'id' => $item->id,
                    'titulo' => $item->titulo,
                    'conteudo' => $item->conteudo, // HTML
                    'data' => $item->created_at->toIso8601String(),
                    'prioridade' => $item->prioridade,
                    'link_acao' => $item->link_acao,
                    'software' => $item->software ? $item->software->nome_software : 'Geral',
                    'tipo' => $item->tipo
                ];
            })
        ]);
    }
}
