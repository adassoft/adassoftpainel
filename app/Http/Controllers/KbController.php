<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KnowledgeBase;
use App\Models\KbCategory;

class KbController extends Controller
{
    public function index()
    {
        // Carrega categorias ativas COM seus artigos ativos e públicos
        $categories = KbCategory::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->with([
                'articles' => function ($query) {
                    $query->where('is_active', true)
                        ->where('is_public', true) // Na home, só mostra os públicos
                        ->orderBy('updated_at', 'desc')
                        ->take(5); // Limita 5 por card na home
                }
            ])
            ->withCount([
                'articles' => function ($query) {
                    $query->where('is_active', true)
                        ->where('is_public', true);
                }
            ])
            ->get();

        return view('kb.index', compact('categories'));
    }

    public function category($slug)
    {
        $category = KbCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $articles = $category->articles()
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('updated_at', 'desc')
            ->paginate(12);

        return view('kb.category', compact('category', 'articles'));
    }

    public function show($slug)
    {
        $article = KnowledgeBase::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Verifica Acesso
        if (!$article->is_public && !auth()->check()) {
            return redirect()->route('login')->with('error', 'Este artigo é exclusivo para usuários logados.');
        }

        return view('kb.show', compact('article'));
    }
}
