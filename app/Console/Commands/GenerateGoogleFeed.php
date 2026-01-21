<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Software;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class GenerateGoogleFeed extends Command
{
    protected $signature = 'feeds:generate';
    protected $description = 'Gera o arquivo XML estático para o Google Shopping';

    public function handle()
    {
        $this->info('Gerando feed de produtos...');

        try {
            // Filtra apenas ativos E marcados para Google Shopping
            $softwares = Software::where('status', true)
                ->where('enviar_google', true)
                ->with('plans')
                ->get();

            // Força o domínio de produção, ignorando configuração local errada
            $baseUrl = 'https://adassoft.com';

            $content = '<?xml version="1.0"?>' . PHP_EOL;
            $content .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . PHP_EOL;
            $content .= '<channel>' . PHP_EOL;
            $content .= '<title>AdasSoft Store</title>' . PHP_EOL;
            $content .= '<link>' . $baseUrl . '</link>' . PHP_EOL;
            $content .= '<description>Softwares de Gestão e Automação</description>' . PHP_EOL;

            foreach ($softwares as $soft) {
                // Preço: Se tiver 'preco_google' definido, usa ele.
                // Se não, usa o menor preço de plano ativo (regra antiga)
                if ($soft->preco_google && $soft->preco_google > 0) {
                    $price = $soft->preco_google;
                } else {
                    $minPricePlan = $soft->plans->where('status', true)->sortBy('valor')->first();
                    $price = $minPricePlan ? $minPricePlan->valor : 0;
                }

                $priceFormatted = number_format($price, 2, '.', '') . ' BRL';

                // Gera o link e substitui o domínio base pelo correto
                $link = route('product.show', $soft->id);
                $link = str_replace(url('/'), $baseUrl, $link);

                $imageLink = $soft->imagem_destaque ?: $soft->imagem;
                if ($imageLink) {
                    if (!Str::startsWith($imageLink, 'http')) {
                        $cleanPath = ltrim($imageLink, '/');
                        // Se não começar com storage/, adiciona
                        if (!Str::startsWith($cleanPath, 'storage/')) {
                            $cleanPath = 'storage/' . $cleanPath;
                        }
                        // Monta com base URL correta
                        $imageLink = $baseUrl . '/' . $cleanPath;
                    } else {
                        // Se for absoluto mas com domínio errado, corrige
                        $imageLink = str_replace(url('/'), $baseUrl, $imageLink);
                    }
                }

                // Descrição: Limpa tags HTML
                $cleanDesc = strip_tags($soft->descricao);
                $cleanDesc = preg_replace('/&/', '&amp;', $cleanDesc);

                $id = $soft->sku ?? $soft->id;
                $brand = $soft->brand ?: 'AdasSoft';

                $content .= '<item>' . PHP_EOL;
                $content .= '<g:id>' . $id . '</g:id>' . PHP_EOL;
                $content .= '<g:title><![CDATA[' . $soft->nome_software . ']]></g:title>' . PHP_EOL;
                $content .= '<g:description><![CDATA[' . Str::limit($cleanDesc, 4000) . ']]></g:description>' . PHP_EOL;
                $content .= '<g:link>' . $link . '</g:link>' . PHP_EOL;
                if ($imageLink) {
                    $content .= '<g:image_link>' . $imageLink . '</g:image_link>' . PHP_EOL;
                }

                // Galeria de Imagens Adicionais
                if ($soft->galeria && is_array($soft->galeria)) {
                    foreach ($soft->galeria as $imgUrl) {
                        if (!empty($imgUrl)) {
                            $addImgLink = $imgUrl;
                            if (!Str::startsWith($addImgLink, 'http')) {
                                $cleanPath = ltrim($addImgLink, '/');
                                if (!Str::startsWith($cleanPath, 'storage/')) {
                                    $cleanPath = 'storage/' . $cleanPath;
                                }
                                $addImgLink = $baseUrl . '/' . $cleanPath;
                            } else {
                                $addImgLink = str_replace(url('/'), $baseUrl, $addImgLink);
                            }

                            $content .= '<g:additional_image_link>' . $addImgLink . '</g:additional_image_link>' . PHP_EOL;
                        }
                    }
                }
                $content .= '<g:condition>new</g:condition>' . PHP_EOL;
                $content .= '<g:availability>in_stock</g:availability>' . PHP_EOL;
                $content .= '<g:price>' . $priceFormatted . '</g:price>' . PHP_EOL;

                if ($soft->google_product_category) {
                    $content .= '<g:google_product_category>' . $soft->google_product_category . '</g:google_product_category>' . PHP_EOL;
                } else {
                    $content .= '<g:google_product_category>316</g:google_product_category>' . PHP_EOL;
                }

                if ($soft->gtin) {
                    $content .= '<g:gtin>' . $soft->gtin . '</g:gtin>' . PHP_EOL;
                    $content .= '<g:brand>' . $brand . '</g:brand>' . PHP_EOL;
                    $content .= '<g:identifier_exists>yes</g:identifier_exists>' . PHP_EOL;
                } else {
                    $content .= '<g:brand>' . $brand . '</g:brand>' . PHP_EOL;
                    $content .= '<g:identifier_exists>no</g:identifier_exists>' . PHP_EOL;
                }

                $content .= '</item>' . PHP_EOL;
            }

            $content .= '</channel>' . PHP_EOL;
            $content .= '</rss>';

            // Salva como .txt para evitar bloqueios de firewall em arquivos .xml
            File::put(public_path('google_products.txt'), $content);

            $this->info('Arquivo gerado com sucesso em: ' . public_path('google_products.txt'));
            $this->info('URL Pública: ' . $baseUrl . '/google_products.txt');

        } catch (\Exception $e) {
            $this->error('Erro ao gerar feed: ' . $e->getMessage());
        }
    }
}
