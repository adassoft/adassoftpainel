<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Software;
use Illuminate\Http\Request;

class DownloadController extends Controller
{
    public function index(Request $request)
    {
        // DEBUG DE REVENDA
        if ($request->has('debug_reseller')) {
            $config = \App\Services\ResellerBranding::getConfig();
            return response()->json([
                'host_detectado' => $request->getHost(),
                'config_encontrada_id' => $config ? $config->id : 'NENHUMA',
                'is_default' => $config ? (bool) $config->is_default : 'N/A',
                'is_reseller_logic' => !\App\Services\ResellerBranding::isDefault(),
                'cache_key' => "site_config_obj_" . $request->getHost(),
                'softwares_liberados_count' => Software::where('disponivel_revenda', true)->count(),
                'contact_info' => \App\Services\ResellerBranding::getContactInfo()
            ]);
        }

        $search = $request->get('q');
        // Se NÃO for a config padrão, estamos num domínio de revenda
        $isReseller = !\App\Services\ResellerBranding::isDefault();

        // 1. Pegar todos os softwares ativos com filtro
        $softwares = Software::where('status', 1)
            ->when($isReseller, function ($query) {
                return $query->where('disponivel_revenda', true);
            })
            ->when($search, function ($query, $search) {
                return $query->where('nome_software', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%");
            })
            ->get();

        // 2. Pegar todos os downloads extras públicos com versões e filtro
        $extras = Download::where('publico', true)
            ->when($isReseller, function ($query) {
                return $query->where('disponivel_revenda', true);
            })
            ->with('versions')
            ->when($search, function ($query, $search) {
                return $query->where('titulo', 'like', "%{$search}%")
                    ->orWhere('descricao', 'like', "%{$search}%");
            })
            ->get();

        $downloadsCollection = collect();

        // Mapear quais IDs de download já estão vinculados a softwares para não duplicar
        $vinculadosIds = $softwares->pluck('id_download_repo')->filter()->toArray();

        // 3. Processar Softwares
        foreach ($softwares as $soft) {
            // Safety Check: Se for revenda e não estiver liberado, pula.
            if ($isReseller && !$soft->disponivel_revenda) {
                continue;
            }

            // Só exibe na lista de downloads se tiver algum meio de baixar
            if ($soft->id_download_repo || $soft->url_download || $soft->arquivo_software) {

                $url = $soft->url_download;
                $tamanho = $soft->tamanho_arquivo;
                $contador = 0;
                $dataInfo = $soft->data_cadastro ? $soft->data_cadastro->format('d/m/Y') : null;

                $repoSlug = null;
                $repoId = null;
                $osList = [];

                // Variáveis de Produto Digital
                $is_paid = false;
                $preco = 0.00;

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
                        $osList = $repoFile->versions->pluck('sistema_operacional')->unique()->values()->toArray();

                        $is_paid = $repoFile->is_paid;
                        $preco = $repoFile->preco;

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
                    'contador' => $contador,
                    'os_list' => $osList,
                    'is_paid' => $is_paid,
                    'preco' => $preco,
                    'requires_login' => $repoFile->requires_login ?? false
                ]);
            }
        }

        // 4. Processar Downloads Extras que NÃO estão vinculados a nenhum software
        foreach ($extras as $extra) {
            if (!in_array($extra->id, $vinculadosIds)) {
                $osList = $extra->versions->pluck('sistema_operacional')->unique()->values()->toArray();

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
                    'contador' => $extra->contador,
                    'os_list' => $osList,
                    'is_paid' => $extra->is_paid,
                    'preco' => $extra->preco,
                    'requires_login' => $extra->requires_login
                ]);
            }
        }

        // Ordenar e Paginar
        $downloadsSorted = $downloadsCollection->sortBy('nome_software')->values();

        $perPage = 12;
        $page = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();

        $downloads = new \Illuminate\Pagination\LengthAwarePaginator(
            $downloadsSorted->forPage($page, $perPage),
            $downloadsSorted->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        return view('shop.downloads', compact('downloads', 'search'));
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

        // Agrupar última versão de cada SO
        $latestByOs = collect();
        if ($download instanceof Download) {
            $latestByOs = $versions->groupBy('sistema_operacional')
                ->map(function ($group) {
                    return $group->sortByDesc('data_lancamento')->first();
                })
                ->sortKeys(); // Windows, Linux, Mac order depends on string, usually OK.
        }

        // Verificar Acesso (Pago / Login)
        $hasAccess = true;
        if ($download instanceof Download) {
            if ($download->is_paid) {
                // Se for Pago, usuário deve estar logado E possuir na biblioteca
                $user = auth()->user();
                $hasAccess = $user && $user->library->contains($download->id);
            } elseif ($download->requires_login) {
                // Se requer login, basta estar logado
                $hasAccess = auth()->check();
            }
        }

        // Informações da Revenda (Para botão WhatsApp se não tiver Asaas)
        $isReseller = !\App\Services\ResellerBranding::isDefault();
        $contactInfo = \App\Services\ResellerBranding::getContactInfo();

        return view('shop.download-details', [
            'download' => $download,
            'software' => $softwareRelacionado,
            'versions' => $versions,
            'latestByOs' => $latestByOs,
            'hasAccess' => $hasAccess,
            'isReseller' => $isReseller,
            'contactInfo' => $contactInfo
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
            // Verificar Acesso
            $hasAccess = true;
            if ($download->is_paid) {
                $user = auth()->user();
                $hasAccess = $user && $user->library()->where('download_id', $download->id)->exists();
            } elseif ($download->requires_login) {
                $hasAccess = auth()->check();
            }

            if (!$hasAccess) {
                if (!auth()->check()) {
                    session()->put('url.intended', route('downloads.show', $download->slug ?? $download->id));
                    return redirect()->route('login')->with('error', 'Por favor, faça login para baixar este arquivo.');
                }

                // Se logado mas sem acesso (Ex: Produto Pago)
                $detailsUrl = route('downloads.show', $download->slug ?? $download->id);
                return redirect($detailsUrl)->with('error', 'Este é um produto exclusivo. Adquira para liberar o download.');
            }

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

        // Verificar Acesso
        if ($version->download) {
            $dl = $version->download;
            $hasAccess = true;

            if ($dl->is_paid) {
                $user = auth()->user();
                $hasAccess = $user && $user->library()->where('download_id', $dl->id)->exists();
            } elseif ($dl->requires_login) {
                $hasAccess = auth()->check();
            }

            if (!$hasAccess) {
                if (!auth()->check()) {
                    session()->put('url.intended', route('downloads.show', $dl->slug ?? $dl->id));
                    return redirect()->route('login')->with('error', 'Por favor, faça login para baixar esta versão.');
                }

                $detailsUrl = route('downloads.show', $dl->slug ?? $dl->id);
                return redirect($detailsUrl)->with('error', 'Você precisa adquirir este produto para liberar esta versão.');
            }
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
