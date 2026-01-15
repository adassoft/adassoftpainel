<x-filament::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Delphi Card -->
        <x-filament::card class="h-full flex flex-col justify-between">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <x-filament::icon icon="heroicon-o-cube" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Delphi</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Integração completa para aplicações VCL e FMX. Compatível com Delphi 7 até Alexandria 11.
                    Inclui exemplos de uso e componentes visuais.
                </p>
                <div class="space-y-2">
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 mr-1 text-green-500" />
                        <span>Compatível com componentes nativos</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 mr-1 text-green-500" />
                        <span>Suporte a 32 e 64 bits</span>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <x-filament::button tag="a" href="{{ \App\Filament\Pages\DocsDelphi::getUrl() }}" color="primary"
                    class="w-full">
                    Acessar Documentação
                </x-filament::button>
            </div>
        </x-filament::card>

        <!-- Java Card -->
        <x-filament::card class="h-full flex flex-col justify-between">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                        <x-filament::icon icon="heroicon-o-command-line"
                            class="h-8 w-8 text-orange-600 dark:text-orange-400" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Java</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    SDK robusto para Java 11+. Zero dependências externas no core.
                    Ideal para aplicações Desktop (Swing/JavaFX) e Backend.
                </p>
                <div class="space-y-2">
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 mr-1 text-green-500" />
                        <span>Multiplataforma (Windows/Linux/Mac)</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 mr-1 text-green-500" />
                        <span>Pacote JavaFX incluso</span>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <x-filament::button tag="a" href="{{ \App\Filament\Pages\DocsJava::getUrl() }}" color="warning"
                    class="w-full">
                    Acessar Documentação
                </x-filament::button>
            </div>
        </x-filament::card>

        <!-- Lazarus Card -->
        <x-filament::card class="h-full flex flex-col justify-between">
            <div>
                <div class="flex items-center space-x-2 mb-4">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <x-filament::icon icon="heroicon-o-code-bracket-square"
                            class="h-8 w-8 text-purple-600 dark:text-purple-400" />
                    </div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Lazarus</h2>
                </div>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Biblioteca leve para Free Pascal (FPC).
                    Foco total em portabilidade e compilação nativa sem DLLs externas.
                </p>
                <div class="space-y-2">
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 mr-1 text-green-500" />
                        <span>Uso de bibliotecas nativas (FCL)</span>
                    </div>
                    <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                        <x-filament::icon icon="heroicon-m-check-circle" class="h-4 w-4 mr-1 text-green-500" />
                        <span>Linux / Windows / macOS</span>
                    </div>
                </div>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <x-filament::button tag="a" href="{{ \App\Filament\Pages\DocsLazarus::getUrl() }}" color="info"
                    class="w-full">
                    Acessar Documentação
                </x-filament::button>
            </div>
        </x-filament::card>

    </div>
</x-filament::page>