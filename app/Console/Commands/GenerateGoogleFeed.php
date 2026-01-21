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
            $softwares = Software::where('status', true)->with('plans')->get();

            $content = '<?xml version="1.0"?>' . PHP_EOL;
            $content .= '<rss xmlns:g="http://base.google.com/ns/1.0" version="2.0">' . PHP_EOL;
            $content .= '<channel>' . PHP_EOL;
            $content .= '<title>AdasSoft Store</title>' . PHP_EOL;
            $content .= '<link>' . url('/') . '</link>' . PHP_EOL;
            $content .= '<description>Softwares de Gestão e Automação</description>' . PHP_EOL;

            foreach ($softwares as $soft) {
                // Pega o menor preço de plano ativo
                $minPricePlan = $soft->plans->where('status', true)->sortBy('valor')->first();
                $price = $minPricePlan ? $minPricePlan->valor : 0;
                $priceFormatted = number_format($price, 2, '.', '') . ' BRL';

                $link = route('product.show', $soft->id);
                $imageLink = $soft->imagem_destaque ?: $soft->imagem;
                if ($imageLink && !Str::startsWith($imageLink, 'http')) {
                    $imageLink = asset($imageLink); // Garante URL completa
                }

                // Descrição: Limpa tags HTML
                $cleanDesc = strip_tags($soft->descricao);
                $cleanDesc = preg_replace('/&/', '&amp;', $cleanDesc); // Escape básico

                // ID
                $id = $soft->sku ?? $soft->id;

                // Brand
                $brand = $soft->brand ?: 'AdasSoft';

                $content .= '<item>' . PHP_EOL;
                $content .= '<g:id>' . $id . '</g:id>' . PHP_EOL;
                $content .= '<g:title><![CDATA[' . $soft->nome_software . ']]></g:title>' . PHP_EOL;
                $content .= '<g:description><![CDATA[' . Str::limit($cleanDesc, 4000) . ']]></g:description>' . PHP_EOL;
                $content .= '<g:link>' . $link . '</g:link>' . PHP_EOL;
                if ($imageLink) {
                    $content .= '<g:image_link>' . $imageLink . '</g:image_link>' . PHP_EOL;
                }
                $content .= '<g:condition>new</g:condition>' . PHP_EOL;
                $content .= '<g:availability>in_stock</g:availability>' . PHP_EOL;
                $content .= '<g:price>' . $priceFormatted . '</g:price>' . PHP_EOL;

                // Google Fields
                if ($soft->google_product_category) {
                    $content .= '<g:google_product_category>' . $soft->google_product_category . '</g:google_product_category>' . PHP_EOL;
                } else {
                    $content .= '<g:google_product_category>316</g:google_product_category>' . PHP_EOL;
                }

                // Identifiers
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

            // Salva no diretório PUBLIC para ser acessado diretamente pelo Apache/Nginx
            // Isso evita passar pelo PHP/Laravel no momento do request
            File::put(public_path('google_products.xml'), $content);

            $this->info('Arquivo gerado com sucesso em: ' . public_path('google_products.xml'));
            $this->info('URL Pública: ' . url('google_products.xml'));

        } catch (\Exception $e) {
            $this->error('Erro ao gerar feed: ' . $e->getMessage());
        }
    }
}
