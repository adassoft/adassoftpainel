<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KnowledgeBase;
use App\Models\KbCategory;

class KbController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        $searchResults = null;

        if ($query) {
            $searchResults = KnowledgeBase::where('is_active', true)
                ->where('is_public', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('content', 'like', "%{$query}%")
                        ->orWhere('tags', 'like', "%{$query}%");
                })
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        // Carrega categorias ativas COM seus artigos ativos e públicos
        $categories = KbCategory::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->with([
                'articles' => function ($query) {
                    $query->where('is_active', true)
                        ->where('is_public', true) // Na home, só mostra os públicos
                        ->orderBy('kb_category_knowledge_base.sort_order', 'asc')
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

        return view('kb.index', compact('categories', 'searchResults', 'query'));
    }

    public function category($slug)
    {
        $category = KbCategory::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $articles = $category->articles()
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('kb_category_knowledge_base.sort_order', 'asc')
            ->paginate(12);

        return view('kb.category', compact('category', 'articles'));
    }

    public function show(Request $request, $slug)
    {
        $article = KnowledgeBase::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Verifica Acesso
        if (!$article->is_public && !auth()->check()) {
            return redirect()->route('login')->with('error', 'Este artigo é exclusivo para usuários logados.');
        }

        // Contexto de Categoria (se veio de uma lista específica)
        $contextSlug = $request->query('c');
        $contextCategory = null;

        if ($contextSlug) {
            $contextCategory = $article->categories()->where('slug', $contextSlug)->first();
        }

        // Fallback: Se não tem contexto ou contexto inválido, pega a primeira categoria
        if (!$contextCategory) {
            $contextCategory = $article->categories()->orderBy('kb_category_knowledge_base.sort_order', 'asc')->first();
        }

        return view('kb.show', compact('article', 'contextCategory'));
    }

    public function vote(Request $request, $id)
    {
        $article = KnowledgeBase::findOrFail($id);

        $type = $request->input('type'); // 'helpful' or 'not_helpful'

        // Bloqueio simples por sessão para evitar spam refrescando a página
        if (session()->has("voted_article_{$id}")) {
            return response()->json(['status' => 'already_voted']);
        }

        if ($type === 'helpful') {
            $article->increment('helpful_count');
            $count = $article->helpful_count + 1; // +1 pq o modelo ainda não recarregou
        } else {
            $article->increment('not_helpful_count');
            $count = $article->not_helpful_count + 1;
        }

        session()->put("voted_article_{$id}", true);

        return response()->json([
            'status' => 'success',
            'helpful_count' => $article->helpful_count, // increment já persiste, mas ideal é reload ou somar manual
            'not_helpful_count' => $article->not_helpful_count
        ]);
    }
}
