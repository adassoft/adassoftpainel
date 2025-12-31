<div>
    {{-- Steps Indicator --}}
    <div class="mb-8">
        <div class="flex items-center justify-between">
            @foreach(['Requisitos', 'Banco de Dados', 'Instalação', 'Admin'] as $index => $label)
                <div class="flex flex-col items-center flex-1">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                        {{ $step > ($index + 1) ? 'bg-green-500 text-white' : ($step === ($index + 1) ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                        {{ $index + 1 }}
                    </div>
                    <span class="text-xs mt-1 {{ $step === ($index + 1) ? 'font-bold text-blue-600' : 'text-gray-500' }}">
                        {{ $label }}
                    </span>
                </div>
                @if(!$loop->last)
                    <div class="flex-1 h-1 bg-gray-200 mx-2">
                        <div class="h-full bg-green-500 transition-all duration-300" style="width: {{ $step > ($index + 1) ? '100%' : '0%' }}"></div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- Content --}}
    <div>
        {{-- STEP 1: Requisitos --}}
        @if($step === 1)
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Verificação de Servidor</h3>
                
                {{-- PHP Version --}}
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded border">
                    <span class="font-medium">PHP Version >= 8.1</span>
                    @if($requirements['php']['status'])
                        <span class="text-green-600 font-bold flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            {{ $requirements['php']['current'] }}
                        </span>
                    @else
                        <span class="text-red-600 font-bold">Atual: {{ $requirements['php']['current'] }}</span>
                    @endif
                </div>

                {{-- Extensões --}}
                <div class="grid grid-cols-2 gap-2">
                    @foreach($requirements['extensions'] as $ext => $enabled)
                        <div class="flex items-center text-sm">
                            @if($enabled)
                                <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            @else
                                <svg class="w-4 h-4 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            @endif
                            <span class="{{ $enabled ? 'text-gray-700' : 'text-red-600 font-bold' }}">{{ ucfirst($ext) }}</span>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Próximo: Banco de Dados
                    </button>
                </div>
            </div>
        @endif

        {{-- STEP 2: Database --}}
        @if($step === 2)
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Configuração de Ambiente</h3>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome da Aplicação</label>
                    <input type="text" wire:model.blur="app_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">DB Host</label>
                        <input type="text" wire:model.blur="db_host" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                    </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700">DB Port</label>
                        <input type="text" wire:model.blur="db_port" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Database Name</label>
                    <input type="text" wire:model.blur="db_database" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" wire:model.blur="db_username" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                    </div>
                     <div>
                        <label class="block text-sm font-medium text-gray-700">Password</label>
                        <input type="password" wire:model.blur="db_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                    </div>
                </div>

                <div class="flex items-center justify-between mt-4">
                    <button wire:click="testDatabaseConnection" type="button" class="text-sm text-blue-600 hover:text-blue-500 font-medium">
                        Testar Conexão
                    </button>
                    
                    @if($connectionStatus === true)
                        <span class="text-green-600 text-sm font-bold flex items-center">
                             <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                             Conectado!
                        </span>
                    @elseif($connectionStatus === false)
                        <span class="text-red-600 text-xs text-right max-w-xs break-words">
                            {{ $connectionMsg }}
                        </span>
                    @endif
                </div>

                <div class="mt-6 flex justify-end">
                    <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
                        @if($connectionStatus !== true) disabled @endif>
                        Salvar e Continuar
                    </button>
                </div>
            </div>
        @endif

        {{-- STEP 3: Instalação --}}
        @if($step === 3)
            <div class="space-y-6 text-center">
                <h3 class="text-lg font-medium text-gray-900">Instalando Tabelas...</h3>
                
                <div class="bg-gray-900 text-gray-200 p-4 rounded text-left text-sm font-mono h-48 overflow-y-auto mb-4">
                    @foreach($installationOutput as $line)
                        <div>> {{ $line }}</div>
                    @endforeach
                    @error('migration')
                        <div class="text-red-400 font-bold mt-2">ERRO: {{ $message }}</div>
                    @enderror
                </div>

                @if(empty($installationOutput) && !$errors->has('migration'))
                    <p class="text-gray-600">O sistema está pronto para criar as tabelas no banco de dados.</p>
                    <button wire:click="runMigration" wire:loading.attr="disabled" class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 w-full flex justify-center items-center">
                         <svg wire:loading wire:target="runMigration" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        INICIAR INSTALAÇÃO
                    </button>
                @elseif($errors->has('migration'))
                    <button wire:click="runMigration" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        Tentar Novamente
                    </button>
                @else
                    <button wire:click="nextStep" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        Próximo: Criar Admin
                    </button>
                @endif
            </div>
        @endif

        {{-- STEP 4: Admin --}}
        @if($step === 4)
            <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Criar Super Admin</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Nome</label>
                    <input type="text" wire:model="admin_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                    @error('admin_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">E-mail</label>
                    <input type="email" wire:model="admin_email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                    @error('admin_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Senha</label>
                    <input type="password" wire:model="admin_password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                    @error('admin_password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Confirmar Senha</label>
                    <input type="password" wire:model="admin_password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm border p-2">
                </div>

                <div class="mt-6 flex justify-end">
                    <button wire:click="createAdmin" wire:loading.attr="disabled" class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 w-full font-bold">
                        FINALIZAR E ENTRAR
                    </button>
                </div>
                
                @error('admin')
                    <div class="text-red-600 text-center font-bold mt-2">{{ $message }}</div>
                @enderror
            </div>
        @endif
    </div>
</div>
