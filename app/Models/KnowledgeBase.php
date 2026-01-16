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
        'faq',
    ];

    protected $casts = [
        'tags' => 'array',
        'faq' => 'array',
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

        // Construct basic canonical URL (adjust path as needed based on routes)
        $url = url('/ajuda/artigo/' . ($this->slug ?? $this->id));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $this->title,
            'url' => $url,
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => $url
            ],
            'datePublished' => $this->created_at->toIso8601String(),
            'dateModified' => $this->updated_at->toIso8601String(),
            'author' => [
                '@type' => $this->author ? 'Person' : 'Organization',
                'name' => $authorName,
            ],
            'description' => \Illuminate\Support\Str::limit(strip_tags($this->content), 160),
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Adassoft',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => 'https://adassoft.com/images/logo.png'
                ]
            ]
        ];

        if ($this->author && $this->author->job_title) {
            $schema['author']['jobTitle'] = $this->author->job_title;
        }

        if ($this->author && $this->author->linkedin_url) {
            $schema['author']['sameAs'] = [$this->author->linkedin_url];
            $schema['author']['url'] = $this->author->linkedin_url;
        }

        // Extract first image
        if (preg_match('/<img.+?src="([^"]+)"/', $this->content, $matches)) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => $matches[1]
            ];
        }

        // Logic for HowTo: Check title or explicit tag
        $isHowTo = (stripos($this->title, 'Como') === 0) || (is_array($this->tags) && in_array('Tutorial', $this->tags));

        if ($isHowTo) {
            $steps = $this->extractHowToSteps();
            if (count($steps) >= 2) {
                $schema['@type'] = 'HowTo';
                $schema['step'] = $steps;
            }
        }

        // Logic for FAQPage Schema
        $faqSchema = null;
        if (!empty($this->faq) && is_array($this->faq)) {
            $faqQuestions = [];
            foreach ($this->faq as $item) {
                if (!empty($item['question']) && !empty($item['answer'])) {
                    $faqQuestions[] = [
                        '@type' => 'Question',
                        'name' => $item['question'],
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => strip_tags($item['answer'], '<a><p><b><strong><ul><ol><li><br>')
                        ]
                    ];
                }
            }

            if (!empty($faqQuestions)) {
                $faqSchema = [
                    '@type' => 'FAQPage',
                    'mainEntity' => $faqQuestions
                ];
            }
        }

        // If we have FAQ, return a @graph with both schemas
        if ($faqSchema) {
            $graph = [
                '@context' => 'https://schema.org',
                '@graph' => [
                    $schema, // The Article/HowTo
                    $faqSchema // The FAQPage
                ]
            ];
            // Remove context from the child article as it's now in graph root
            unset($graph['@graph'][0]['@context']);

            return json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function getProcessedContentAttribute()
    {
        if (empty($this->content)) {
            return '';
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        // Hack para garantir UTF-8 correto
        $content = mb_convert_encoding($this->content, 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML($content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $index => $img) {
            // Força Lazy Loading em todas as imagens para melhorar LCP
            $img->setAttribute('loading', 'lazy');

            // Garante atributo ALT descritivo se estiver faltando
            if (!$img->hasAttribute('alt') || trim($img->getAttribute('alt')) === '') {
                $altText = ($this->title ?? 'Imagem') . " - Figura " . ($index + 1);
                $img->setAttribute('alt', $altText);
            }
        }

        return $dom->saveHTML();
    }

    protected function extractHowToSteps()
    {
        $content = $this->content;
        if (empty($content))
            return [];

        $steps = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // 1. Try Headers (H2/H3) - Best for SEO Structure
        $headers = $xpath->query('//h2 | //h3');

        if ($headers->length > 0) {
            foreach ($headers as $index => $header) {
                $stepTitle = trim($header->textContent);
                $stepContent = '';
                $node = $header->nextSibling;
                while ($node) {
                    if (in_array(strtolower($node->nodeName), ['h1', 'h2', 'h3']))
                        break;
                    $stepContent .= $dom->saveHTML($node);
                    $node = $node->nextSibling;
                }
                $stepText = trim(strip_tags($stepContent));
                if (empty($stepText))
                    $stepText = "Veja os detalhes no passo acima.";

                $steps[] = [
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'name' => $stepTitle,
                    'text' => \Illuminate\Support\Str::limit($stepText, 300),
                ];
            }
        }
        // 2. Fallback: Ordered Lists (<ol><li>) - For simple tutorials
        else {
            $listItems = $xpath->query('//ol/li');
            if ($listItems->length > 0) {
                foreach ($listItems as $index => $li) {
                    $stepText = trim($li->textContent);
                    $steps[] = [
                        '@type' => 'HowToStep',
                        'position' => $index + 1,
                        'name' => "Passo " . ($index + 1), // "Passo 1", "Passo 2"...
                        'text' => \Illuminate\Support\Str::limit($stepText, 300),
                    ];
                }
            }
        }

        return $steps;
    }
}
