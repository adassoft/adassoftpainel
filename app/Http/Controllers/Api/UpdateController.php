<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Software;
use App\Models\Download;
use App\Models\DownloadVersion;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UpdateController extends Controller
{
    public function check(Request $request)
    {
        try {
            $softwareId = $request->input('software_id');
            $currentVersion = $request->input('current_version');
            $channel = $request->input('channel', 'stable');

            if (!$softwareId) {
                return response()->json(['error' => 'Software ID is required'], 400);
            }

            // Busca o Software
            $software = Software::find($softwareId);
            if (!$software) {
                // Tenta buscar por Slug se ID falhar
                $software = Software::where('slug', $softwareId)->first();
                if (!$software) {
                    return response()->json(['error' => 'Software not found'], 404);
                }
            }

            // 1. Verifica Repositório de Updates (Estrito)
            // Apenas usa o repositório de updates configurado. Não faz fallback para instaladores.
            $updateRepoId = $software->id_update_repo;

            if (!$updateRepoId) {
                return response()->json(['update_available' => false, 'message' => 'Update repository not configured']);
            }

            $download = Download::find($updateRepoId);
            if (!$download) {
                return response()->json(['error' => 'Update repository not found'], 404);
            }

            // 2. Busca a última versão
            $latestVersion = $download->versions()
                ->where('data_lancamento', '<=', now())
                ->orderBy('data_lancamento', 'desc')
                ->first();

            if (!$latestVersion) {
                // Se não tem versões cadastradas, mas tem arquivo principal no Download Pai
                // Implementar fallback opcional? O user pediu 'seção de updates', deve ter versions.
                return response()->json(['update_available' => false, 'message' => 'No versions found']);
            }

            // 3. Compara Versões
            // PHP version_compare: retorna -1 se v1 < v2
            $needsUpdate = empty($currentVersion) || version_compare($currentVersion, $latestVersion->versao, '<');

            if ($needsUpdate) {

                // Gera URL de Download (Assinada - Válida por 2 horas)
                $downloadUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'api.updates.download',
                    now()->addMinutes(120),
                    ['versionId' => $latestVersion->id]
                );

                return response()->json([
                    'update_available' => true,
                    'version' => $latestVersion->versao,
                    'url' => $downloadUrl,
                    'size' => $latestVersion->tamanho,
                    'changelog' => $latestVersion->changelog,
                    'mandatory' => false, // Futuro: campo 'obrigatorio' no DB
                    'hash' => null // Futuro: MD5 do arquivo
                ]);
            }

            return response()->json([
                'update_available' => false,
                'latest_version' => $latestVersion->versao
            ]);

        } catch (Exception $e) {
            Log::error("Update check error: " . $e->getMessage());
            return response()->json(['error' => 'Internal error', 'details' => $e->getMessage()], 500);
        }
    }

    public function download(Request $request, $versionId)
    {
        try {
            $version = DownloadVersion::find($versionId);
            if (!$version) {
                abort(404, 'Version not found');
            }

            // Log de download (Analytics) opcional aqui, mas cuidado com spam de bots.
            // Para updates, incrementar contador do Download Pai
            if ($version->download) {
                $version->download->increment('contador');
            }

            $path = $version->arquivo_path;

            // Verifica disco 'products' (onde o filament salva por padrão conforme DownloadResource)
            if (Storage::disk('products')->exists($path)) {
                return Storage::disk('products')->download($path);
            }

            // Fallback para 'public' se foi upload manual antigo
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->download($path);
            }

            abort(404, 'File physical not found');

        } catch (Exception $e) {
            Log::error("Update download error: " . $e->getMessage());
            abort(500, 'Server Error');
        }
    }
}
