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

                $repoSlug = null;
                $repoId = null;

                // Se estiver vinculado ao repositório, pegamos os dados reais do arquivo
                if ($soft->id_download_repo) {
                    $repoFile = $extras->firstWhere('id', $soft->id_download_repo);
                    if ($repoFile) {
                        $url = asset('storage/' . $repoFile->arquivo_path);
                        $tamanho = $repoFile->tamanho;
                        $contador = $repoFile->contador;
                        $dataInfo = $repoFile->data_atualizacao ? $repoFile->data_atualizacao->format('d/m/Y') : $dataInfo;

                        $repoSlug = $repoFile->slug;
                        $repoId = $repoFile->id;

                        // Prioritize Repo Version for consistency
                        if (!empty($repoFile->versao)) {
                            $soft->versao = $repoFile->versao;
                        }
                    }
                } elseif ($soft->arquivo_software) {
                    $url = asset('storage/' . $soft->arquivo_software);
                }

                $downloadsCollection->push([
                    'id' => $soft->id,
                    'slug' => $soft->slug,
                    'tipo' => 'software',
                    'repo_id' => $repoId,
                    'repo_slug' => $repoSlug,
                    'nome_software' => $soft->nome_software,
                    'versao' => $soft->versao,
                    'tamanho_arquivo' => $tamanho,
                    'url_download' => $url,
                    'data_info' => $dataInfo,
                    'imagem' => $this->resolveImageUrl($soft->imagem ?: $soft->imagem_destaque),
                    'contador' => $contador
                ]);
            }
        }

        // 4. Processar Downloads Extras que NÃO estão vinculados a nenhum software
        foreach ($extras as $extra) {
            if (!in_array($extra->id, $vinculadosIds)) {
                $downloadsCollection->push([
                    'id' => $extra->id,
                    'slug' => $extra->slug,
                    'tipo' => 'extra',
                    'nome_software' => $extra->titulo,
                    'versao' => $extra->versao,
                    'tamanho_arquivo' => $extra->tamanho,
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
        $download = null;
        $softwareRelacionado = null;

        // 1. Tenta achar o Download (Extra)
        if (is_numeric($id)) {
            $download = Download::find($id);
        } else {
            $download = Download::where('slug', $id)->first();
        }

        // 2. Se achou o download, busca se tem software relacionado
        if ($download) {
            $softwareRelacionado = Software::where('id_download_repo', $download->id)->first();
        }

        // 3. Se NÃO achou download, tenta achar um Software com esse slug/id (Fallback para Legacy)
        else {
            if (is_numeric($id)) {
                $soft = Software::find($id);
            } else {
                $soft = Software::where('slug', $id)->first();
            }

            if ($soft) {
                // Cria um objeto Download "Virtual" para a view não quebrar
                $download = new Download();
                $download->id = $soft->id; // Apenas para referência
                $download->titulo = $soft->nome_software;
                $download->slug = $soft->slug;
                $download->descricao = $soft->descricao;
                $download->versao = $soft->versao;
                $download->tamanho = $soft->tamanho_arquivo;
                $download->data_atualizacao = $soft->data_cadastro;
                $download->contador = 0; // Softwares legacy não têm contador separado ainda
                $download->arquivo_path = $soft->arquivo_software; // Caminho relativo

                // Se tiver URL externa, precisaremos tratar na view ou aqui. 
                // Vamos usar um atributo transiente ou verificar se é url
                $download->is_external_url = !empty($soft->url_download);
                if ($download->is_external_url) {
                    $download->arquivo_path = $soft->url_download;
                }

                $softwareRelacionado = $soft;
            }
        }

        if (!$download) {
            abort(404);
        }

        // Carregar versões anteriores (apenas se for um objeto Download real)
        $versions = $download instanceof Download ? $download->versions : collect();

        return view('shop.download-details', [
            'download' => $download,
            'software' => $softwareRelacionado,
            'versions' => $versions
        ]);
    }

    private function resolveImageUrl($path)
    {
        if (!$path)
            return null;

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Se começar com 'img/', assume que está na pasta public raiz (ex: ícones gerados)
        if (str_starts_with($path, 'img/')) {
            return asset($path);
        }

        // Caso contrário, assume que é um upload do Filament (Storage)
        return asset('storage/' . $path);
    }

    public function downloadFile($id)
    {
        $download = null;

        // 1. Tenta achar Download (Extra)
        if (is_numeric($id)) {
            $download = Download::find($id);
        } else {
            $download = Download::where('slug', $id)->first();
        }

        if ($download) {
            $download->increment('contador');

            // Se for link externo disfarçado (URL completa no arquivo_path)
            if (filter_var($download->arquivo_path, FILTER_VALIDATE_URL)) {
                return redirect()->away($download->arquivo_path);
            }

            $path = storage_path('app/public/' . $download->arquivo_path);
            if (!file_exists($path)) {
                // Tenta sem o public/ se salvou na raiz storage
                $path = storage_path('app/' . $download->arquivo_path);
            }

            return response()->download($path);
        }

        // 2. Fallback: Software (Legacy) - Não tem contador na tabela softwares ainda, mas podemos redirecionar
        $software = null;
        if (is_numeric($id)) {
            $software = Software::find($id);
        } else {
            $software = Software::where('slug', $id)->first();
        }

        if ($software) {
            // Em softwares, se quiser contar, teria que adicionar coluna contador na tabela softwares
            // Por enquanto, apenas entrega

            // 1. Verifica se está vinculado a um repositório de download (Prioridade)
            if ($software->id_download_repo) {
                $linkedDownload = Download::find($software->id_download_repo);
                if ($linkedDownload) {
                    $linkedDownload->increment('contador');

                    if (filter_var($linkedDownload->arquivo_path, FILTER_VALIDATE_URL)) {
                        return redirect()->away($linkedDownload->arquivo_path);
                    }

                    $path = storage_path('app/public/' . $linkedDownload->arquivo_path);
                    if (!file_exists($path)) {
                        $path = storage_path('app/' . $linkedDownload->arquivo_path);
                    }
                    return response()->download($path);
                }
            }

            // 2. Verifica cadastro direto no software
            if ($software->url_download) { // Externo
                return redirect()->away($software->url_download);
            }
            if ($software->arquivo_software) {
                return response()->download(storage_path('app/public/' . $software->arquivo_software));
            }
        }

        abort(404, 'Arquivo não encontrado. (Recurso sem arquivo vinculado)');
    }

    public function downloadVersion($id)
    {
        $version = \App\Models\DownloadVersion::with('download')->find($id);

        if (!$version) {
            abort(404, 'Versão não encontrada.');
        }

        // Incrementa contador da versão e do download principal
        $version->increment('contador');
        if ($version->download) {
            $version->download->increment('contador');
        }

        $path = storage_path('app/public/' . $version->arquivo_path);
        if (!file_exists($path)) {
            $path = storage_path('app/' . $version->arquivo_path);
        }

        return response()->download($path);
    }
}
