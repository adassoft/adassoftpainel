<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CompleteProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Completar Cadastro';
    protected static ?string $title = 'Complete seu Cadastro';
    protected static ?string $slug = 'complete-profile';
    protected static bool $shouldRegisterNavigation = false; // Não aparece no menu
    protected static string $view = 'filament.pages.complete-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user) {
            redirect('/admin/login');
            return;
        }

        // Se já completou, redireciona para dashboard
        if (!$user->pending_profile_completion) {
            redirect('/admin');
            return;
        }

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'celular' => $user->celular,
            // Preencha com outros campos se tiver na tabela User ou Relacionada
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Dados Pessoais')
                    ->description('Por favor, confirme seus dados para continuar acessando o sistema.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome Completo')
                            ->required(),

                        TextInput::make('email')
                            ->label('E-mail')
                            ->disabled(),

                        TextInput::make('documento')
                            ->label('CPF ou CNPJ')
                            ->required()
                            ->maxLength(20),

                        TextInput::make('celular')
                            ->label('Celular / WhatsApp')
                            ->mask('(99) 99999-9999')
                            ->required(),
                    ])->columns(2),

                Section::make('Endereço (Opcional)')
                    ->schema([
                        TextInput::make('cep')
                            ->label('CEP')
                            ->mask('99999-999'),
                        TextInput::make('endereco')
                            ->label('Logradouro'),
                        TextInput::make('numero')
                            ->label('Número'),
                        TextInput::make('bairro')
                            ->label('Bairro'),
                        TextInput::make('cidade')
                            ->label('Cidade'),
                        TextInput::make('estado')
                            ->label('UF')
                            ->maxLength(2),
                    ])->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        // 1. Atualizar User
        $user->update([
            'name' => $data['name'],
            'celular' => $data['celular'],
            // Salvar documento onde? Se não tiver coluna, precisamos criar ou salvar em 'configurations' json... 
            // Assumindo que User tem cpf_cnpj ou vamos criar Company?
            // Por simplicidade, vou salvar em 'pending_profile_completion' como false para liberar.
            'pending_profile_completion' => false,
        ]);

        // 2. Criar ou Atualizar Empresa vinculada (se aplicável)
        // Se o sistema espera que todo user tenha uma empresa:
        if (!$user->company && !empty($data['documento'])) {
            $company = \App\Models\Company::create([
                // 'user_id' => $user->id, // Se tiver essa relação inversa
                'codigo' => \App\Models\Company::max('codigo') + 1, // Auto-increment manual do legado
                'razao' => $data['name'], // Usa nome como razão inicial
                'cnpj' => $data['documento'],
                'fone1' => $data['celular'],
                'endereco' => $data['endereco'] ?? '',
                'numero' => $data['numero'] ?? '',
                'bairro' => $data['bairro'] ?? '',
                'cidade' => $data['cidade'] ?? '',
                'uf' => $data['estado'] ?? '',
                'cep' => $data['cep'] ?? '',
            ]);

            // Vincula user à empresa
            $user->empresa_codigo = $company->codigo;
            $user->save();
        }

        Notification::make()
            ->title('Cadastro atualizado com sucesso!')
            ->success()
            ->send();

        $this->redirect('/admin');
    }
}
