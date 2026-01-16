<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use Concerns\HasSeo;

    protected $fillable = [
        'title',
        'slug',
        'category_id',
        'content',
        'tags',
        'is_public',
        'is_active',
        'sort_order',
        'video_url',
        'helpful_count',
        'not_helpful_count',
        'author_id',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function categories()
    {
        return $this->belongsToMany(KbCategory::class, 'kb_category_knowledge_base')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    // Helper to keep compatibility for now, returns the first category
    public function getCategoryAttribute()
    {
        return $this->categories->first();
    }

    public function getJsonLdAttribute()
    {
        $authorName = $this->author ? ($this->author->name ?? $this->author->nome) : 'Adassoft';

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->title,
            'datePublished' => $this->created_at->toIso8601String(),
            'dateModified' => $this->updated_at->toIso8601String(),
            'author' => [
                '@type' => $this->author ? 'Person' : 'Organization',
                'name' => $authorName,
            ],
            'description' => \Illuminate\Support\Str::limit(strip_tags($this->content), 160),
        ];

        if ($this->author && $this->author->job_title) {
            $schema['author']['jobTitle'] = $this->author->job_title;
        }

        if ($this->author && $this->author->linkedin_url) {
            $schema['author']['url'] = $this->author->linkedin_url;
        }

        // Se o título começar com "Como", tenta gerar Schema de HowTo
        if (stripos($this->title, 'Como') === 0) {
            $steps = $this->extractHowToSteps();
            if (count($steps) >= 2) {
                $schema['@type'] = 'HowTo';
                $schema['step'] = $steps;
            }
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function extractHowToSteps()
    {
        $content = $this->content;
        if (empty($content))
            return [];

        $steps = [];

        // Utiliza DOMDocument para parsear HTML sem regex frágil
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        // Hack para UTF-8 charset
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        // Busca h2 ou h3, assumindo que são os passos
        $headers = $xpath->query('//h2 | //h3');

        foreach ($headers as $index => $header) {
            $stepTitle = trim($header->textContent);

            // Pega o conteúdo até o próximo header
            $stepContent = '';
            $node = $header->nextSibling;

            while ($node) {
                // Se encontrar outro header do mesmo nível ou superior, para
                if (in_array(strtolower($node->nodeName), ['h1', 'h2', 'h3'])) {
                    break;
                }
                $stepContent .= $dom->saveHTML($node);
                $node = $node->nextSibling;
            }

            $stepText = trim(strip_tags($stepContent));
            if (empty($stepText)) {
                $stepText = "Veja os detalhes no passo acima.";
            }

            $steps[] = [
                '@type' => 'HowToStep',
                'position' => $index + 1,
                'name' => $stepTitle,
                'text' => \Illuminate\Support\Str::limit($stepText, 300), // Limita texto do passo
            ];
        }

        return $steps;
    }
}
