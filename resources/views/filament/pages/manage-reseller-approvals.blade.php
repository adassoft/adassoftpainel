<x-filament-panels::page>
    <div class="space-y-6">
        @if(count($requests) === 0)
            <div
                class="flex flex-col items-center justify-center py-12 bg-white dark:bg-gray-900 rounded-xl border border-dashed border-gray-300 dark:border-gray-700 shadow-sm">
                <x-heroicon-o-check-circle class="w-16 h-16 text-gray-300 dark:text-gray-600 mb-4" />
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Tudo certo por aqui!</h3>
                <p class="text-gray-500 dark:text-gray-400 mt-1">Não há solicitações de personalização pendentes no momento.
                </p>
            </div>
        @endif

        @foreach($requests as $item)
            <?php    $novos = $item->dados_pendentes; ?>
            <x-filament::section collapsible class="border-l-4 border-l-warning-500 shadow-lg">
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                Solicitação de: {{ $item->user->nome ?? 'N/A' }} (ID: {{ $item->usuario_id }})
                            </span>
                            @if($item->status_aprovacao === 'com_pendencia')
                                <x-filament::badge color="info" icon="heroicon-m-clock">
                                    Aguardando Retorno
                                </x-filament::badge>
                            @else
                                <x-filament::badge color="warning">
                                    Pendente
                                </x-filament::badge>
                            @endif
                        </div>
                    </div>
                </x-slot>

                <div class="space-y-6 py-4">
                    {{-- Timeline/History if exists --}}
                    @if($item->history->count() > 0)
                        <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg border border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-bold uppercase text-gray-500 mb-3 flex items-center gap-2">
                                <x-heroicon-m-clock class="w-4 h-4" /> Linha do Tempo
                            </h4>
                            <div class="space-y-3 border-l-2 border-gray-200 dark:border-gray-700 ml-2 pl-4">
                                @foreach($item->history as $h)
                                    <div class="relative">
                                        <div class="text-xs text-gray-500 font-medium">
                                            {{ \Carbon\Carbon::parse($h->data_registro)->format('d/m/Y H:i') }}
                                            <span
                                                class="ml-1 uppercase px-1.5 py-0.5 rounded {{ $h->acao === 'aprovacao' ? 'bg-success-100 text-success-700' : ($h->acao === 'rejeicao' ? 'bg-danger-100 text-danger-700' : 'bg-warning-100 text-warning-700') }}">
                                                {{ $h->acao }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-700 dark:text-gray-300 mt-1">{{ $h->mensagem }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Comparison Table --}}
                    <div class="overflow-hidden border border-gray-200 dark:border-gray-700 rounded-lg shadow-sm">
                        <table class="w-full text-left text-sm">
                            <thead
                                class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold border-bottom border-gray-200 dark:border-gray-700">
                                <tr>
                                    <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-700">Campo</th>
                                    <th class="px-4 py-3 border-r border-gray-200 dark:border-gray-700 w-1/3">Atual (No Ar)
                                    </th>
                                    <th class="px-4 py-3 w-1/3">Solicitado (Novo)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                {{-- Row: System Name --}}
                                <tr>
                                    <td
                                        class="px-4 py-4 font-bold bg-gray-50/50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                        Nome do Sistema</td>
                                    <td class="px-4 py-4 border-r border-gray-200 dark:border-gray-700">
                                        {{ $item->nome_sistema ?? 'Adassoft System' }}
                                    </td>
                                    <td
                                        class="px-4 py-4 @if(($item->nome_sistema ?? '') != ($novos['nome_sistema'] ?? '')) text-primary-600 font-bold @endif">
                                        {{ $novos['nome_sistema'] ?? 'N/A' }}
                                    </td>
                                </tr>
                                {{-- Row: Slogan --}}
                                <tr>
                                    <td
                                        class="px-4 py-4 font-bold bg-gray-50/50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                        Slogan</td>
                                    <td class="px-4 py-4 border-r border-gray-200 dark:border-gray-700">
                                        {{ $item->slogan ?? 'Segurança e Gestão' }}
                                    </td>
                                    <td
                                        class="px-4 py-4 @if(($item->slogan ?? '') != ($novos['slogan'] ?? '')) text-primary-600 font-bold @endif">
                                        {{ $novos['slogan'] ?? 'N/A' }}
                                    </td>
                                </tr>
                                {{-- Row: Domains --}}
                                <tr>
                                    <td
                                        class="px-4 py-4 font-bold bg-gray-50/50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                        Domínios</td>
                                    <td class="px-4 py-4 border-r border-gray-200 dark:border-gray-700">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach(explode(',', $item->dominios ?? '') as $dom)
                                                @if(trim($dom))
                                                    <x-filament::badge color="gray" size="sm">{{ trim($dom) }}</x-filament::badge>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach(explode(',', $novos['dominios'] ?? '') as $dom)
                                                @if(trim($dom))
                                                    <x-filament::badge color="info" size="sm">{{ trim($dom) }}</x-filament::badge>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                {{-- Row: Colors --}}
                                <tr>
                                    <td
                                        class="px-4 py-4 font-bold bg-gray-50/50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                        Gradiente (Cores)</td>
                                    <td class="px-4 py-4 border-r border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded border"
                                                style="background-color: {{ $item->cor_primaria_gradient_start ?? '#1a2980' }}">
                                            </div>
                                            <x-heroicon-m-arrow-right class="w-4 h-4 text-gray-400" />
                                            <div class="w-8 h-8 rounded border"
                                                style="background-color: {{ $item->cor_primaria_gradient_end ?? '#26d0ce' }}">
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded border"
                                                style="background-color: {{ $novos['cor_primaria_gradient_start'] ?? '#1a2980' }}">
                                            </div>
                                            <x-heroicon-m-arrow-right class="w-4 h-4 text-gray-400" />
                                            <div class="w-8 h-8 rounded border"
                                                style="background-color: {{ $novos['cor_primaria_gradient_end'] ?? '#26d0ce' }}">
                                            </div>
                                            @if(($item->cor_primaria_gradient_start ?? '') != ($novos['cor_primaria_gradient_start'] ?? ''))
                                                <x-filament::badge color="primary" size="xs">Modificado</x-filament::badge>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                {{-- Row: Logo --}}
                                <tr>
                                    <td
                                        class="px-4 py-4 font-bold bg-gray-50/50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                        Logo</td>
                                    <td class="px-4 py-4 border-r border-gray-200 dark:border-gray-700">
                                        <div class="flex flex-col gap-1">
                                            <span
                                                class="text-xs font-mono text-gray-400">{{ $item->logo_path ?? 'favicon.svg' }}</span>
                                            @if(!empty($item->logo_path))
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($item->logo_path) }}"
                                                    class="h-10 mt-1 border border-gray-200 dark:border-gray-700 rounded p-1 object-contain bg-gray-50 opacity-75">
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-1">
                                            <span
                                                class="text-xs font-mono text-gray-600 dark:text-gray-300">{{ $novos['logo_path'] ?? 'favicon.svg' }}</span>
                                            @if(!empty($novos['logo_path']))
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($novos['logo_path']) }}"
                                                    class="h-14 mt-1 border border-gray-200 dark:border-gray-700 rounded p-1 object-contain bg-white">
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                {{-- Row: Icone --}}
                                <tr>
                                    <td
                                        class="px-4 py-4 font-bold bg-gray-50/50 dark:bg-gray-800/50 border-r border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                                        Ícone (Favicon)
                                    </td>
                                    <td class="px-4 py-4 border-r border-gray-200 dark:border-gray-700">
                                        <span
                                            class="text-xs font-mono text-gray-400">{{ $item->icone_path ?? 'favicon.png' }}</span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-1">
                                            <span
                                                class="text-xs font-mono text-gray-600 dark:text-gray-300">{{ $novos['icone_path'] ?? 'favicon.png' }}</span>
                                            @if(!empty($novos['icone_path']))
                                                <img src="{{ \Illuminate\Support\Facades\Storage::url($novos['icone_path']) }}"
                                                    class="h-8 w-8 mt-1 border border-gray-200 dark:border-gray-700 rounded p-0.5 object-contain bg-white">
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col gap-3">
                        {{-- AI Analyser Button --}}
                        <div x-data="{ analysis: '', loading: false }" class="w-full">
                            <x-filament::button color="info" icon="heroicon-m-sparkles"
                                class="w-full justify-center shadow-md bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600"
                                @click="
                                                            loading = true;
                                                            $wire.analyzeIA({{ $item->id }}).then(res => {
                                                                analysis = res;
                                                                loading = false;
                                                            })
                                                        " x-show="!analysis && !loading">
                                Usar Assistente de IA para Analisar Risco
                            </x-filament::button>

                            <div x-show="loading"
                                class="flex flex-col items-center justify-center p-8 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-info-300">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-info-500 mb-3"></div>
                                <span class="text-gray-600 dark:text-gray-300 font-medium">O Agente IA está analisando a
                                    solicitação...</span>
                            </div>

                            <div x-show="analysis" x-transition
                                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-5 rounded-lg">
                                <header class="flex items-center justify-between mb-3">
                                    <h5 class="font-bold text-blue-800 dark:text-blue-300 flex items-center gap-2">
                                        <x-heroicon-m-swatch class="w-5 h-5" /> Resultado da Análise IA
                                    </h5>
                                    <button @click="analysis = ''"
                                        class="text-blue-500 hover:text-blue-700 text-xs font-bold uppercase">Limpar</button>
                                </header>
                                <div class="prose prose-sm dark:prose-invert max-w-none text-blue-900 dark:text-blue-100"
                                    x-html="analysis"></div>
                            </div>
                        </div>

                        {{-- Main Approval Buttons --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 pt-3" x-data="{ forceApproval: false }">
                            {{-- Approve Button with Confirmation --}}
                            <x-filament::modal width="md">
                                <x-slot name="trigger">
                                    <x-filament::button color="success" icon="heroicon-m-check" size="lg"
                                        class="w-full shadow-md">
                                        Aprovar
                                    </x-filament::button>
                                </x-slot>

                                <x-slot name="heading">
                                    Aprovar Configurações
                                </x-slot>

                                <div class="space-y-3">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Você está prestes a aprovar estas
                                        alterações para o ambiente de produção.</p>
                                    <ul class="text-sm list-disc list-inside text-gray-500 italic">
                                        <li>Visual atualizado imediatamente.</li>
                                        <li>Domínios configurados no servidor.</li>
                                    </ul>

                                    {{-- Force Checkbox --}}
                                    <div
                                        class="flex items-start gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 bg-red-50 dark:bg-red-900/20 p-3 rounded">
                                        <input type="checkbox" id="force_approval_{{ $item->id }}" x-model="forceApproval"
                                            class="mt-1 rounded border-gray-300 text-danger-600 shadow-sm focus:border-danger-300 focus:ring focus:ring-danger-200 focus:ring-opacity-50">
                                        <label for="force_approval_{{ $item->id }}"
                                            class="text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                            <strong>Forçar Aprovação (Cloudflare/Erro DNS)</strong><br>
                                            Marque se os domínios estiverem atrás do Cloudflare ou se quiser ignorar erros
                                            de integração do aaPanel.
                                        </label>
                                    </div>
                                </div>

                                <x-slot name="footer">
                                    <div class="flex justify-end gap-3">
                                        <x-filament::button color="gray" @click="close">Cancelar</x-filament::button>
                                        <x-filament::button color="success"
                                            x-on:click="$wire.approve({{ $item->id }}, forceApproval); close()">
                                            Sim, Aprovar e Publicar
                                        </x-filament::button>
                                    </div>
                                </x-slot>
                            </x-filament::modal>

                            {{-- Correction Button with Modal --}}
                            <x-filament::modal width="lg">
                                <x-slot name="trigger">
                                    <x-filament::button color="warning" icon="heroicon-m-pencil-square" size="lg"
                                        class="w-full shadow-md">
                                        Solicitar Correção
                                    </x-filament::button>
                                </x-slot>

                                <x-slot name="heading">
                                    Solicitar Correção à Revenda
                                </x-slot>

                                <div class="space-y-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Informe o que precisa ser corrigido
                                        pelo revendedor.</p>
                                    <x-filament::input.wrapper>
                                        <x-filament::input type="textarea" x-ref="correction_text"
                                            placeholder="Ex: DNS não aponta para o servidor. Atente-se ao CNAME..."
                                            rows="4" />
                                    </x-filament::input.wrapper>
                                </div>

                                <x-slot name="footer">
                                    <div class="flex justify-end gap-3">
                                        <x-filament::button color="gray" @click="close">Cancelar</x-filament::button>
                                        <x-filament::button color="warning" @click="
                                                                        $wire.requestCorrection({{ $item->id }}, $refs.correction_text.value);
                                                                        close();
                                                                    ">
                                            Enviar Solicitação
                                        </x-filament::button>
                                    </div>
                                </x-slot>
                            </x-filament::modal>

                            {{-- Reject Button with Modal --}}
                            <x-filament::modal width="lg">
                                <x-slot name="trigger">
                                    <x-filament::button color="danger" icon="heroicon-m-x-mark" size="lg"
                                        class="w-full shadow-md">
                                        Rejeitar
                                    </x-filament::button>
                                </x-slot>

                                <x-slot name="heading">
                                    Rejeitar Solicitação
                                </x-slot>

                                <div class="space-y-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Informe o motivo da rejeição
                                        definitiva.</p>
                                    <x-filament::input.wrapper>
                                        <x-filament::input type="textarea" x-ref="rejection_text"
                                            placeholder="Ex: Logotipo em baixa resolução ou domínios não validados."
                                            rows="4" />
                                    </x-filament::input.wrapper>
                                </div>

                                <x-slot name="footer">
                                    <div class="flex justify-end gap-3">
                                        <x-filament::button color="gray" @click="close">Cancelar</x-filament::button>
                                        <x-filament::button color="danger" @click="
                                                                        $wire.reject({{ $item->id }}, $refs.rejection_text.value);
                                                                        close();
                                                                    ">
                                            Confirmar Rejeição
                                        </x-filament::button>
                                    </div>
                                </x-slot>
                            </x-filament::modal>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>