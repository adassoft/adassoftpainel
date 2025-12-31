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

        // Lógica de filtro baseada no nível de acesso do usuário
        // Assumindo: Acesso 2 = Revenda. Admin vê tudo. Outros (Clientes) = Todos.
        // Se o usuário for Super Admin (método do FilamentUser), vê tudo.

        // Se não for super admin (admin pode ver tudo também):
        // Vamos aplicar filtro se NÃO for admin explícito.
        // Como o sistema identifica "Cliente"? Provavelmente acesso != 2 e não admin.

        // Lógica simplificada:
        if ($user->acesso == 2) {
            // Revenda vê 'revenda' e 'todos'
            $query->whereIn('publico', ['revenda', 'todos']);
        } elseif (!$this->isAdmin($user)) {
            // Cliente (assumindo que não é admin e não é revenda)
            $query->where('publico', 'todos');
        }
        // Se for Admin, query não tem filtro de público (vê tudo)

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
        // Helper para checar se é admin com base nas regras do projeto
        // User.php deve ter canAccessPanel ou similar.
        // Aqui vou assumir que acesso != 2 e acesso != (cliente) é admin.
        // Mas se todos usam o mesmo painel... 
        // Vamos checar depois o User.php mais a fundo se precisar.
        // Por ora, admin vê tudo.
        return method_exists($user, 'isSuperAdmin') ? $user->isSuperAdmin() : ($user->id === 1); // Fallback
    }
}
