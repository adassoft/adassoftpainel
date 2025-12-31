<?php

namespace App\Filament\App\Pages\Auth;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Empresa;
use App\Services\ResellerBranding;
use Illuminate\Database\Eloquent\Model;

use Livewire\Attributes\Layout;

#[Layout('layouts.login-split')]
class Register extends BaseRegister
{
    protected static string $view = 'filament.app.pages.auth.register';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nome')
                    ->label('Nome Completo')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),

                TextInput::make('login')
                    ->label('Usuário (Login)')
                    ->required()
                    ->unique('usuario', 'login')
                    ->maxLength(50),

                TextInput::make('email')
                    ->label('E-mail')
                    ->email()
                    ->required()
                    ->unique('usuario', 'email')
                    ->maxLength(255),

                TextInput::make('password')
                    ->label('Senha')
                    ->password()
                    ->required()
                    ->minLength(8)
                    ->same('passwordConfirmation'),

                TextInput::make('passwordConfirmation')
                    ->label('Confirmar Senha')
                    ->password()
                    ->required(),

                Section::make('Dados da Empresa / Pessoal')
                    ->description('Informe seus dados para emissão de nota e cadastro.')
                    ->schema([
                        TextInput::make('cnpj')
                            ->label('CPF ou CNPJ')
                            ->required()
                            ->maxLength(18)
                            ->mask('999.999.999-99'), // Padrão CPF inicio 

                        TextInput::make('fone')
                            ->label('WhatsApp')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('razao')
                            ->label('Razão Social (Opcional)'),

                        TextInput::make('cidade')
                            ->label('Cidade')
                            ->required(),

                        Select::make('uf')
                            ->label('UF')
                            ->options([
                                'AC' => 'AC',
                                'AL' => 'AL',
                                'AP' => 'AP',
                                'AM' => 'AM',
                                'BA' => 'BA',
                                'CE' => 'CE',
                                'DF' => 'DF',
                                'ES' => 'ES',
                                'GO' => 'GO',
                                'MA' => 'MA',
                                'MT' => 'MT',
                                'MS' => 'MS',
                                'MG' => 'MG',
                                'PA' => 'PA',
                                'PB' => 'PB',
                                'PR' => 'PR',
                                'PE' => 'PE',
                                'PI' => 'PI',
                                'RJ' => 'RJ',
                                'RN' => 'RN',
                                'RS' => 'RS',
                                'RO' => 'RO',
                                'RR' => 'RR',
                                'SC' => 'SC',
                                'SP' => 'SP',
                                'SE' => 'SE',
                                'TO' => 'TO'
                            ])
                            ->required(),
                    ])
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        // 1. Limpeza
        $cnpjLimpo = preg_replace('/[^0-9]/', '', $data['cnpj']);

        // 2. Revenda
        $config = ResellerBranding::getConfig();
        $cnpjRep = $config && $config->user ? $config->user->cnpj : null;

        // 3. Empresa
        Empresa::firstOrCreate(
            ['cnpj' => $cnpjLimpo],
            [
                'razao' => $data['razao'] ?? $data['nome'],
                'fone' => $data['fone'],
                'cidade' => $data['cidade'],
                'uf' => $data['uf'],
                'email' => $data['email'],
                'data' => now(),
                'status' => 'Ativo',
                'cnpj_representante' => $cnpjRep,
                'endereco' => 'Cadastro Online',
                'bairro' => 'Centro',
                'cep' => '00000000'
            ]
        );

        // 4. Usuário
        return User::create([
            'nome' => $data['nome'],
            'email' => $data['email'],
            'login' => $data['login'],
            'senha' => Hash::make($data['password']),
            'cnpj' => $cnpjLimpo,
            'acesso' => 3, // Cliente
            'uf' => $data['uf'],
            'data' => now(),
            'status' => 'Ativo'
        ]);
    }
}
