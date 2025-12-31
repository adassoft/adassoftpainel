<?php

namespace App\Filament\Reseller\Pages;

use App\Models\Empresa;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Auth;

class CompanyProfile extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Minha Empresa';
    protected static ?string $title = 'Dados da Empresa';
    protected static ?string $slug = 'configurar-empresa';

    // Escondido do menu se já tiver empresa, ou mostra sempre? 
    // Vamos deixar visível para ele poder editar depois.
    protected static ?string $navigationGroup = 'Configurações';

    protected static string $view = 'filament.reseller.pages.company-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();

        // Tenta achar a empresa pelo CNPJ do usuário
        $empresa = Empresa::where('cnpj', $user->cnpj)->first();

        if ($empresa) {
            $this->form->fill($empresa->toArray());
        } else {
            $this->form->fill([
                'email' => $user->email, // Preenche email por conveniência
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('razao_social')
                    ->label('Razão Social')
                    ->required()
                    ->maxLength(255),

                TextInput::make('nome_fantasia')
                    ->label('Nome Fantasia')
                    ->required()
                    ->maxLength(255),

                TextInput::make('cnpj')
                    ->label('CNPJ')
                    ->required()
                    ->mask('99.999.999/9999-99')
                    ->unique('empresa', 'cnpj', ignoreRecord: true)
                    ->live(onBlur: true), // Validação ao sair do campo

                TextInput::make('email')
                    ->label('Email Corporativo')
                    ->email()
                    ->required(),

                TextInput::make('fone')
                    ->label('Telefone/WhatsApp')
                    ->mask('(99) 99999-9999')
                    ->required(),

                TextInput::make('cep')
                    ->label('CEP')
                    ->mask('99999-999')
                    ->required(),

                TextInput::make('endereco')
                    ->label('Endereço')
                    ->required(),

                TextInput::make('numero')
                    ->label('Número')
                    ->required(),

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

            // Lógica de Create or Update baseada no CNPJ
            // Problema: se ele mudar o CNPJ, criamos uma nova ou atualizamos?
            // Como CNPJ é a chave de ligação, se mudar o CNPJ tem que atualizar no User também.

            $empresa = Empresa::updateOrCreate(
                ['cnpj' => $data['cnpj']], // Busca por CNPJ
                $data // Atualiza tudo
            );

            // Atualiza o usuário para ter esse CNPJ
            if ($user->cnpj !== $data['cnpj']) {
                $user->cnpj = $data['cnpj'];
                $user->save();
            }

            Notification::make()
                ->success()
                ->title('Empresa salva com sucesso!')
                ->send();

            // Se veio redirecionado, talvez recarregar a página para limpar o aviso
            // Mas o middleware vai deixar passar agora.

        } catch (Halt $exception) {
            return;
        }
    }
}
