<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Software;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index()
    {
        // 1. Pegar todos os softwares ativos
        $softwares = Software::where('status', 1)->get();

        // 2. Pegar todos os downloads extras públicos
        $extras = Download::where('publico', true)->get();

        $downloadsCollection = collect();

        // Mapear quais IDs de download já estão vinculados a softwares para não duplicar
        $vinculadosIds = $softwares->pluck('id_download_repo')->filter()->toArray();

        // 3. Processar Softwares
        foreach ($softwares as $soft) {
            // Só exibe na lista de downloads se tiver algum meio de baixar
            if ($soft->id_download_repo || $soft->url_download || $soft->arquivo_software) {

                $url = $soft->url_download;
                $tamanho = $soft->tamanho_arquivo;
                $contador = 0;
                $dataInfo = $soft->data_cadastro ? $soft->data_cadastro->format('d/m/Y') : null;

                // Se estiver vinculado ao repositório, pegamos os dados reais do arquivo
                if ($soft->id_download_repo) {
                    $repoFile = $extras->firstWhere('id', $soft->id_download_repo);
                    if ($repoFile) {
                        $url = asset('storage/' . $repoFile->arquivo_path);
                        $tamanho = $repoFile->tamanho_arquivo;
                        $contador = $repoFile->contador;
                        $dataInfo = $repoFile->data_atualizacao ? $repoFile->data_atualizacao->format('d/m/Y') : $dataInfo;
                    }
                } elseif ($soft->arquivo_software) {
                    $url = asset('storage/' . $soft->arquivo_software);
                }

                $downloadsCollection->push([
                    'id' => $soft->id,
                    'tipo' => 'software',
                    'nome_software' => $soft->nome_software,
                    'versao' => $soft->versao,
                    'tamanho_arquivo' => $tamanho,
                    'url_download' => $url,
                    'data_info' => $dataInfo,
                    'imagem' => $soft->imagem ?: $soft->imagem_destaque,
                    'contador' => $contador
                ]);
            }
        }

        // 4. Processar Downloads Extras que NÃO estão vinculados a nenhum software
        foreach ($extras as $extra) {
            if (!in_array($extra->id, $vinculadosIds)) {
                $downloadsCollection->push([
                    'id' => $extra->id,
                    'tipo' => 'extra',
                    'nome_software' => $extra->titulo,
                    'versao' => $extra->versao,
                    'tamanho_arquivo' => $extra->tamanho_arquivo,
                    'url_download' => asset('storage/' . $extra->arquivo_path),
                    'data_info' => $extra->data_atualizacao ? $extra->data_atualizacao->format('d/m/Y') : null,
                    'imagem' => null, // Deixe o blade decidir o ícone padrão
                    'contador' => $extra->contador
                ]);
            }
        }

        $downloads = $downloadsCollection->sortBy('nome_software');

        return view('shop.downloads', compact('downloads'));
    }

    public function show($id)
    {
        $download = Download::findOrFail($id);

        // Se este download for vinculado a um software, redireciona para a página do produto
        $software = Software::where('id_download_repo', $id)->first();
        if ($software) {
            return redirect()->route('product.show', $software->id);
        }

        return view('shop.download-details', compact('download'));
    }
}
