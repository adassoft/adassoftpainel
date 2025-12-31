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
                // Dados de Acesso
                Section::make()
                    ->schema([
                        TextInput::make('nome')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255)
                            ->autofocus()
                            ->prefixIcon('heroicon-o-user')
                            ->placeholder('Seu nome completo'),

                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('login')
                                    ->label('Usuário')
                                    ->required()
                                    ->unique('usuario', 'login')
                                    ->maxLength(50)
                                    ->prefixIcon('heroicon-o-key')
                                    ->placeholder('usuario.sistema'),

                                TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->unique('usuario', 'email')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->placeholder('seu@email.com'),
                            ]),

                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->label('Senha')
                                    ->password()
                                    ->required()
                                    ->minLength(8)
                                    ->same('passwordConfirmation')
                                    ->prefixIcon('heroicon-o-lock-closed'),

                                TextInput::make('passwordConfirmation')
                                    ->label('Confirmar Senha')
                                    ->password()
                                    ->required()
                                    ->prefixIcon('heroicon-o-check-circle'),
                            ]),
                    ])->compact(),

                Section::make('Dados da Empresa / Pessoal')
                    ->description('Necessário para validação da conta.')
                    ->schema([
                        \Filament\Forms\Components\Grid::make(2)
                            ->schema([
                                TextInput::make('cnpj')
                                    ->label('CPF ou CNPJ')
                                    ->required()
                                    ->maxLength(18)
                                    ->mask('999.999.999-99')
                                    ->prefixIcon('heroicon-o-identification')
                                    ->placeholder('000.000.000-00'),

                                TextInput::make('fone')
                                    ->label('WhatsApp / Celular')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-phone')
                                    ->mask('(99) 99999-9999')
                                    ->placeholder('(00) 00000-0000'),
                            ]),

                        TextInput::make('razao')
                            ->label('Razão Social (Opcional)')
                            ->placeholder('Nome da sua empresa (se houver)')
                            ->prefixIcon('heroicon-o-building-office'),

                        \Filament\Forms\Components\Grid::make(4)
                            ->schema([
                                TextInput::make('cidade')
                                    ->label('Cidade')
                                    ->required()
                                    ->prefixIcon('heroicon-o-map-pin')
                                    ->columnSpan(3),

                                Select::make('uf')
                                    ->label('UF')
                                    ->searchable()
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
                                    ->required()
                                    ->prefixIcon('heroicon-o-map')
                                    ->columnSpan(1),
                            ]),
                    ])->collapsible(),
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
