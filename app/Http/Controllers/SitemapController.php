<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Software;
use App\Models\KnowledgeBase;
use App\Models\KbCategory;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index()
    {
        $content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Static Pages
        $this->addUrl($content, route('home'), now());
        $this->addUrl($content, route('downloads'), now());
        // $this->addUrl($content, route('kb.index'), now()); // Helps indexing, usually handled by category links

        // Softwares (Products)
        $softwares = Software::where('status', true)->get();
        foreach ($softwares as $software) {
            $updatedAt = $software->data_cadastro ?? now(); // Using data_cadastro as fallback
            $this->addUrl($content, route('product.show', $software->slug ?? $software->id), $updatedAt);
        }

        // Downloads (Public)
        $downloads = Download::where('publico', true)->get();
        foreach ($downloads as $download) {
            $updatedAt = $download->data_atualizacao ?? $download->created_at ?? now();
            $this->addUrl($content, route('downloads.show', $download->slug ?? $download->id), $updatedAt);
        }

        // Knowledge Base Categories
        $categories = KbCategory::where('is_active', true)->get();
        foreach ($categories as $category) {
            $this->addUrl($content, route('kb.category', $category->slug), $category->updated_at);
        }

        // Knowledge Base Articles
        $articles = KnowledgeBase::where('is_active', true)->where('is_public', true)->get();
        foreach ($articles as $article) {
            $this->addUrl($content, route('kb.show', $article->slug), $article->updated_at);
        }

        // Legal Pages (optional, if model exists or static list)
        // Ignoring for now unless user asks specific ones

        $content .= '</urlset>';

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    private function addUrl(string &$content, string $loc, $lastmod = null)
    {
        $content .= '  <url>' . PHP_EOL;
        $content .= '    <loc>' . $loc . '</loc>' . PHP_EOL;
        if ($lastmod) {
            $date = $lastmod instanceof \DateTimeInterface ? $lastmod->format('Y-m-d') : date('Y-m-d', strtotime($lastmod));
            $content .= '    <lastmod>' . $date . '</lastmod>' . PHP_EOL;
        }
        $content .= '  </url>' . PHP_EOL;
    }
}
