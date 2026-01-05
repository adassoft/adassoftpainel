<?php

namespace App\Filament\Reseller\Pages;

use App\Models\Company;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;

class CompanyProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Configurações';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Minha Empresa';
    protected static ?string $title = 'Dados da Empresa';
    protected static ?string $slug = 'configurar-empresa';

    protected static string $view = 'filament.reseller.pages.company-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        // Estratégia de Carregamento: ID (Vínculo Seguro) > CNPJ (Legado)
        $empresa = null;
        if ($user->empresa_id) {
            $empresa = Company::find($user->empresa_id);
        }

        if (!$empresa && $user->cnpj) {
            $cleanCnpj = preg_replace('/\D/', '', $user->cnpj);
            $empresa = Company::where('cnpj', $cleanCnpj)->first();

            // Auto-fix vínculo
            if ($empresa) {
                $user->empresa_id = $empresa->codigo;
                $user->saveQuietly();
            }
        }

        if ($empresa) {
            $this->form->fill($empresa->toArray());
        } else {
            $this->form->fill([
                'email' => $user->email,
                'razao' => $user->nome,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('razao')
                    ->label('Razão Social')
                    ->required()
                    ->maxLength(255),

                TextInput::make('nome_fantasia') // Campo "nome_fantasia" não existe no Model Company original, mapear para razao se necessário ou manter se coluna existir
                    ->label('Nome Fantasia')
                    ->maxLength(255),

                TextInput::make('cnpj')
                    ->label('CNPJ')
                    ->required()
                    ->mask('99.999.999/9999-99')
                    ->dehydrateStateUsing(fn($state) => preg_replace('/\D/', '', $state ?? ''))
                    ->live(onBlur: true),

                TextInput::make('email')
                    ->label('Email Corporativo')
                    ->email()
                    ->required(),

                TextInput::make('fone')
                    ->label('Telefone/WhatsApp')
                    ->mask('(99) 99999-9999')
                    ->dehydrateStateUsing(fn($state) => preg_replace('/\D/', '', $state ?? ''))
                    ->required(),

                TextInput::make('cep')
                    ->label('CEP')
                    ->mask('99999-999')
                    ->dehydrateStateUsing(fn($state) => preg_replace('/\D/', '', $state ?? ''))
                    ->required(),

                TextInput::make('endereco')
                    ->label('Endereço')
                    ->required(),

                TextInput::make('numero')
                    ->label('Número')
                    ->required(),

                TextInput::make('complemento')
                    ->label('Complemento')
                    ->maxLength(255),

                TextInput::make('bairro')
                    ->label('Bairro')
                    ->required(),

                TextInput::make('cidade')
                    ->label('Cidade')
                    ->required(),

                TextInput::make('uf')
                    ->label('UF')
                    ->length(2)
                    ->required(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function save(): void
    {
        try {
            $data = $this->form->getState();
            $user = Auth::user();

            // Limpeza de dados
            $data['cnpj'] = preg_replace('/\D/', '', $data['cnpj']);
            $data['fone'] = preg_replace('/\D/', '', $data['fone']);
            $data['cep'] = preg_replace('/\D/', '', $data['cep']);

            // Recupera Empresa existente
            $empresa = null;
            if ($user->empresa_id) {
                $empresa = Company::find($user->empresa_id);
            }

            if ($empresa) {
                // UPDATE: Atualiza empresa existente
                $empresa->update($data);
            } else {
                // CREATE: Nova empresa (Cuidado com duplicação de CNPJ)
                // Verifica se já existe empresa solta com esse CNPJ
                $existingCompany = Company::where('cnpj', $data['cnpj'])->first();

                if ($existingCompany) {
                    // Se existe, apropria-se dela
                    $existingCompany->update($data);
                    $empresa = $existingCompany;
                } else {
                    // Cria nova
                    $empresa = new Company();
                    $empresa->fill($data);
                    $empresa->status = 'Ativo';
                    $empresa->data = now();
                    $empresa->save();
                }

                // Vincula
                $user->empresa_id = $empresa->codigo;
                $user->save();
            }

            // Sincroniza CNPJ no User para manter compatibilidade legado
            if ($user->cnpj !== $data['cnpj']) {
                $user->cnpj = $data['cnpj'];
                $user->save();
            }

            Notification::make()
                ->success()
                ->title('Empresa salva com sucesso!')
                ->send();

            // Recarrega os dados no formulário visível
            $this->form->fill($empresa->fresh()->toArray());

        } catch (Halt $exception) {
            return;
        }
    }
}
