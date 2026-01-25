<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Software;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use App\Mail\DownloadLinkMail;
use App\Models\Lead;

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

        // Se começar com 'img/produtos', é upload novo do Filament -> Storage
        if (str_starts_with($path, 'img/produtos/')) {
            return asset('storage/' . $path);
        }

        // Se começar com 'img/' (legacy), assume que está na pasta public raiz
        if (str_starts_with($path, 'img/')) {
            return asset($path);
        }

        // Caso contrário, assume que é um upload do Filament (Storage) genérico
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

                $detailsUrl = route('downloads.show', $download->slug ?? $download->id);
                return redirect($detailsUrl)->with('error', 'Este é um produto exclusivo. Adquira para liberar o download.');
            }

            // Lead Capture (Se não for link assinado e usuário não estiver logado)
            // Lead Capture
            if (!request()->hasValidSignature() && $download->requires_lead) {
                if (!auth()->check()) {
                    // Visitante: Exibir Formulário
                    $siteKey = null;
                    $config = \App\Models\Configuration::where('chave', 'google_config')->first();
                    if ($config) {
                        $json = json_decode($config->valor, true);
                        $siteKey = $json['recaptcha_site_key'] ?? null;
                    }

                    return view('downloads.lead-capture', [
                        'download' => $download,
                        'versionId' => null,
                        'recaptchaSiteKey' => $siteKey
                    ]);
                } else {
                    // Usuário Logado: Captura Silenciosa do Lead para estatísticas
                    try {
                        $user = auth()->user();
                        $companyName = 'Cliente Cadastrado';
                        $uPhone = null;

                        // Tenta obter dados da empresa vinculada
                        if ($user->empresa) {
                            $companyName = $user->empresa->nome_fantasia ?? $user->empresa->razao ?? $companyName;
                            $uPhone = $user->empresa->fone;
                        }

                        // Tenta obter fone direto do usuário se existir (futuro/legado)
                        $uPhone = $user->whatsapp ?? $user->celular ?? $uPhone;

                        \App\Models\Lead::firstOrCreate([
                            'download_id' => $download->id,
                            'email' => $user->email
                        ], [
                            'nome' => $user->name,
                            'empresa' => $companyName,
                            'whatsapp' => $uPhone,
                            'ip_address' => request()->ip()
                        ]);
                    } catch (\Exception $e) {
                        // Ignora erro duplicado ou campo faltando
                    }
                }
            }

            // Bot Detection
            $userAgent = request()->userAgent();
            $isBot = false;

            if (empty($userAgent)) {
                $isBot = true;
            } else {
                $bots = ['bot', 'crawl', 'slurp', 'spider', 'mediapartners', 'facebook', 'whatsapp', 'preview', 'google', 'bing', 'yahoo'];
                foreach ($bots as $bot) {
                    if (stripos($userAgent, $bot) !== false) {
                        $isBot = true;
                        break;
                    }
                }
            }

            if (!$isBot) {
                // Analytics Log
                \App\Models\DownloadLog::create([
                    'download_id' => $download->id,
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => $userAgent,
                    'referer' => request()->header('referer'),
                ]);

                $download->increment('contador');
            }

            $arquivoPath = $download->arquivo_path;

            // Se não tiver arquivo no Pai, busca na última versão
            if (empty($arquivoPath)) {
                $lastVersion = $download->versions()->orderBy('data_lancamento', 'desc')->first();
                if ($lastVersion) {
                    $arquivoPath = $lastVersion->arquivo_path;
                }
            }

            if (empty($arquivoPath)) {
                \Illuminate\Support\Facades\Log::warning("Download {$id} sem arquivo vinculado (nem pai, nem versão).");
                abort(404, 'Nenhum arquivo vinculado a este download.');
            }

            // Se for link externo disfarçado
            if (filter_var($arquivoPath, FILTER_VALIDATE_URL)) {
                return redirect()->away($arquivoPath);
            }

            // Tenta em múltiplos locais
            // 1. App Products (Novo Padrão)
            $path = storage_path('app/products/' . $arquivoPath);

            // 2. App Public (Legado)
            if (!file_exists($path)) {
                $path = storage_path('app/public/' . $arquivoPath);
            }

            // 3. App Raiz (Legado)
            if (!file_exists($path)) {
                $path = storage_path('app/' . $arquivoPath);
            }

            // 4. Public Raiz (Legacy Assets)
            if (!file_exists($path)) {
                $path = public_path($arquivoPath);
            }

            if (!file_exists($path) || !is_file($path)) {
                \Illuminate\Support\Facades\Log::error("Download falhou: Arquivo não encontrado em nenhum local. Path alvo: {$arquivoPath}");
                abort(404, 'Arquivo físico não encontrado no servidor.');
            }

            return response()->download($path);
        }

        // 2. Fallback: Software (Legacy)
        $software = null;
        if (is_numeric($id)) {
            $software = Software::find($id);
        } else {
            $software = Software::where('slug', $id)->first();
        }

        if ($software) {
            // 1. Verifica se está vinculado a um repositório de download
            if ($software->id_download_repo) {
                // Redireciona para lógica principal recursivamente (seguro) ou repete a logica
                return $this->downloadFile($software->id_download_repo);
            }

            // 2. Verifica cadastro direto no software
            if ($software->url_download) { // Externo
                return redirect()->away($software->url_download);
            }
            if ($software->arquivo_software) {
                $path = storage_path('app/public/' . $software->arquivo_software);
                if (file_exists($path)) {
                    return response()->download($path);
                }
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

            // Lead Capture
            if (!request()->hasValidSignature() && $dl->requires_lead) {
                if (!auth()->check()) {
                    $siteKey = null;
                    $config = \App\Models\Configuration::where('chave', 'google_config')->first();
                    if ($config) {
                        $json = json_decode($config->valor, true);
                        $siteKey = $json['recaptcha_site_key'] ?? null;
                    }

                    return view('downloads.lead-capture', [
                        'download' => $dl,
                        'versionId' => $version->id,
                        'recaptchaSiteKey' => $siteKey
                    ]);
                } else {
                    // Usuário Logado: Captura Silenciosa
                    try {
                        $user = auth()->user();
                        $companyName = 'Cliente Cadastrado';
                        $uPhone = null;

                        if ($user->empresa) {
                            $companyName = $user->empresa->nome_fantasia ?? $user->empresa->razao ?? $companyName;
                            $uPhone = $user->empresa->fone;
                        }

                        $uPhone = $user->whatsapp ?? $user->celular ?? $uPhone;

                        \App\Models\Lead::firstOrCreate([
                            'download_id' => $dl->id,
                            'email' => $user->email
                        ], [
                            'nome' => $user->name,
                            'empresa' => $companyName,
                            'whatsapp' => $uPhone,
                            'ip_address' => request()->ip()
                        ]);
                    } catch (\Exception $e) {
                    }
                }
            }
        }

        // Bot Detection
        $userAgent = request()->userAgent();
        $isBot = false;

        if (empty($userAgent)) {
            $isBot = true;
        } else {
            $bots = ['bot', 'crawl', 'slurp', 'spider', 'mediapartners', 'facebook', 'whatsapp', 'preview', 'google', 'bing', 'yahoo'];
            foreach ($bots as $bot) {
                if (stripos($userAgent, $bot) !== false) {
                    $isBot = true;
                    break;
                }
            }
        }

        if (!$isBot) {
            // Analytics Log
            \App\Models\DownloadLog::create([
                'download_id' => $version->download_id,
                'version_id' => $version->id,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => $userAgent,
                'referer' => request()->header('referer'),
            ]);

            // Incrementa contador da versão e do download principal
            $version->increment('contador');
            if ($version->download) {
                $version->download->increment('contador');
            }
        }

        // 1. Tenta no disco Seguro (Novo Padrão)
        $path = storage_path('app/products/' . $version->arquivo_path);

        // 2. Tenta no disco Público (Legado)
        if (!file_exists($path)) {
            $path = storage_path('app/public/' . $version->arquivo_path);
        }

        // 3. Tenta na Raiz Storage (Legado)
        if (!file_exists($path)) {
            $path = storage_path('app/' . $version->arquivo_path);
        }

        if (!file_exists($path)) {
            abort(404, 'Arquivo físico da versão não encontrado.');
        }

        return response()->download($path);
    }
    public function storeLead(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'nome' => 'required|string',
            'empresa' => 'required|string',
        ]);

        // Recaptcha Validation
        $config = \App\Models\Configuration::where('chave', 'google_config')->first();
        if ($config) {
            $json = json_decode($config->valor, true);
            $siteKey = $json['recaptcha_site_key'] ?? null;
            $secretKey = $json['recaptcha_secret_key'] ?? null;

            if ($siteKey && $secretKey) {
                if (!$request->input('g-recaptcha-response')) {
                    return back()->with('error', 'Por favor, confirme que você não é um robô.')->withInput();
                }

                try {
                    $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                        'secret' => $secretKey,
                        'response' => $request->input('g-recaptcha-response'),
                        'remoteip' => $request->ip(),
                    ]);

                    if (!$verify->successful() || !$verify->json()['success']) {
                        return back()->with('error', 'Falha na verificação do reCAPTCHA.')->withInput();
                    }
                } catch (\Exception $e) {
                }
            }
        }

        // Save Lead
        Lead::create([
            'download_id' => $request->download_id,
            'empresa' => $request->empresa,
            'nome' => $request->nome,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'ip_address' => $request->ip(),
        ]);

        // Generate Signed Link (24 hours valid)
        $link = '';
        $download = Download::find($request->download_id);

        if ($request->version_id) {
            $link = URL::temporarySignedRoute(
                'downloads.version.signed',
                now()->addHours(24),
                ['id' => $request->version_id]
            );
        } else {
            $link = URL::temporarySignedRoute(
                'downloads.file.signed',
                now()->addHours(24),
                ['id' => $request->download_id]
            );
        }

        // Send Email
        try {
            Mail::to($request->email)->send(new DownloadLinkMail($download, $link));
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao enviar e-mail: ' . $e->getMessage())->withInput();
        }

        return view('downloads.lead-success', ['email' => $request->email]);
    }

    public function downloadFileSigned($id)
    {
        if (!request()->hasValidSignature()) {
            abort(403, 'Link expirado ou inválido.');
        }
        return $this->downloadFile($id);
    }

    public function downloadVersionSigned($id)
    {
        if (!request()->hasValidSignature()) {
            abort(403, 'Link expirado ou inválido.');
        }
        return $this->downloadVersion($id);
    }
}
