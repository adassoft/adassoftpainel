<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\News;
use Illuminate\Support\Facades\Auth;

class DashboardNews extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-news';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 5;

    protected function getViewData(): array
    {
        $user = Auth::user();
        $query = News::active();

        // Lógica Rigorosa de Permissões
        $acesso = (int) ($user->acesso ?? 3); // 1=Admin, 2=Revenda, 3=Cliente (Default)

        // Verifica se é Admin (ID 1 ou Acesso 1)
        $isAdmin = ($user->id === 1) || $acesso === 1;

        if ($isAdmin) {
            // Admin vê todas as notícias (sem filtro)
        } elseif ($acesso === 2) {
            // Revenda vê 'revenda' e 'todos'
            $query->whereIn('publico', ['revenda', 'todos']);
        } else {
            // Cliente (Acesso 3 ou outros) vê APENAS 'cliente' e 'todos'
            // Isso garante que notícias 'revenda' NUNCA vazem aqui
            $query->whereIn('publico', ['cliente', 'todos']);
        }

        $noticias = $query->orderByRaw("FIELD(prioridade, 'alta', 'normal', 'baixa')")
            ->latest()
            ->take(5)
            ->get();

        return [
            'noticias' => $noticias,
        ];
    }

    protected function isAdmin($user)
    {
        $acesso = (int) ($user->acesso ?? 0);
        return ($user->id === 1) || ($acesso === 1);
    }
}
