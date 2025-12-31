<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManageRegisterUser extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Cadastro Manual';

    protected static ?string $title = 'Cadastro Manual de Usuários';

    protected static ?string $navigationGroup = 'Clientes e Licenças';

    protected static string $view = 'filament.pages.manage-register-user';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'acesso' => 3, // Cliente default
            'n_terminais' => 1,
            'validade_licenca' => now()->addDays(30)->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Grid::make(1)
                            ->columnSpan(6)
                            ->schema([
                                Section::make('Dados do Usuário')
                                    ->icon('heroicon-o-user')
                                    ->extraAttributes(['class' => 'register-section-user'])
                                    ->schema([
                                        TextInput::make('nome')
                                            ->label('Nome completo')
                                            ->required(),

                                        TextInput::make('login')
                                            ->label('Login')
                                            ->required()
                                            ->unique(User::class, 'login'),

                                        TextInput::make('email')
                                            ->label('E-mail')
                                            ->email()
                                            ->required(),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('senha')
                                                    ->label('Senha')
                                                    ->password()
                                                    ->required()
                                                    ->same('senha_repetida'),

                                                TextInput::make('senha_repetida')
                                                    ->label('Confirmar senha')
                                                    ->password()
                                                    ->required(),
                                            ]),

                                        TextInput::make('cnpj')
                                            ->label('CNPJ / CPF')
                                            ->mask(fn(TextInput $component) => '99.999.999/9999-99') // Simple mask, can be improved dynamically but Filament mask is strictly defined usually
                                            // Ideally we use a custom or dynamic mask but for now just text or generic mask
                                            ->required()
                                            ->rule(function () {
                                                return function (string $attribute, $value, \Closure $fail) {
                                                    $clean = preg_replace('/\D/', '', $value);
                                                    if (User::where('cnpj', $clean)->exists()) {
                                                        $fail('Este CNPJ/CPF já possui um usuário vinculado.');
                                                    }
                                                };
                                            }),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('uf_usuario')
                                                    ->label('UF do usuário')
                                                    ->options($this->getUfs())
                                                    ->required(),

                                                Select::make('acesso')
                                                    ->label('Nível de acesso')
                                                    ->options([
                                                        1 => 'Administrador',
                                                        2 => 'Vendedor',
                                                        3 => 'Cliente',
                                                    ])
                                                    ->required(),
                                            ]),
                                    ]),
                            ]),

                        Grid::make(1)
                            ->columnSpan(6)
                            ->schema([
                                Section::make('Dados da Empresa (preencha caso o CNPJ não exista)')
                                    ->icon('heroicon-o-building-office')
                                    ->extraAttributes(['class' => 'register-section-company'])
                                    ->schema([
                                        TextInput::make('razao')
                                            ->label('Razão Social'),

                                        TextInput::make('endereco')
                                            ->label('Endereço'),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('bairro')
                                                    ->label('Bairro'),

                                                TextInput::make('cidade')
                                                    ->label('Cidade'),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('cep')
                                                    ->label('CEP'),

                                                Select::make('uf_empresa')
                                                    ->label('UF')
                                                    ->options($this->getUfs()),

                                                TextInput::make('fone')
                                                    ->label('Telefone'),
                                            ]),

                                        TextInput::make('email_empresa')
                                            ->label('E-mail da empresa')
                                            ->email(),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('n_terminais')
                                                    ->label('Nº de terminais')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1),

                                                DatePicker::make('validade_licenca')
                                                    ->label('Validade da licença')
                                                    ->displayFormat('d/m/Y')
                                                    ->default(now()->addDays(30)),
                                            ]),

                                        TextInput::make('cnpj_representante')
                                            ->label('CNPJ do representante (opcional)'),

                                        ViewField::make('company_note')
                                            ->view('filament.forms.components.register-company-note'),
                                    ]),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $cnpjLimpo = preg_replace('/\D/', '', $data['cnpj']);

        DB::beginTransaction();
        try {
            // Check existing User by Login again (double check)
            if (User::where('login', $data['login'])->exists()) {
                throw new \Exception('Já existe um usuário com este login.');
            }

            // Check/Create Company
            $empresa = Company::where('cnpj', $cnpjLimpo)->first();

            if (!$empresa) {
                // Validate required company fields if creating new
                if (empty($data['razao']) || empty($data['endereco']) || empty($data['cidade']) || empty($data['bairro']) || empty($data['cep']) || empty($data['uf_empresa'])) {
                    throw new \Exception('CNPJ não encontrado. Preencha todos os campos da empresa para criá-la.');
                }

                $empresa = Company::create([
                    'cnpj' => $cnpjLimpo,
                    'razao' => $data['razao'],
                    'endereco' => $data['endereco'],
                    'cidade' => $data['cidade'],
                    'bairro' => $data['bairro'],
                    'cep' => preg_replace('/\D/', '', $data['cep']),
                    'uf' => strtoupper($data['uf_empresa']),
                    'fone' => $data['fone'],
                    'email' => $data['email_empresa'],
                    'data' => now(),
                    'nterminais' => $data['n_terminais'],
                    'validade_licenca' => $data['validade_licenca'],
                    'cnpj_representante' => preg_replace('/\D/', '', $data['cnpj_representante'] ?? ''),
                    'bloqueado' => 'N',
                ]);
            }

            // Create User
            User::create([
                'nome' => $data['nome'],
                'login' => $data['login'],
                'email' => $data['email'],
                'senha' => Hash::make($data['senha']),
                'cnpj' => $cnpjLimpo,
                'uf' => $data['uf_usuario'],
                'acesso' => $data['acesso'],
                'data' => now(),
            ]);

            DB::commit();

            Notification::make()
                ->title('Sucesso!')
                ->body('Usuário cadastrado com sucesso!')
                ->success()
                ->send();

            // Allow creating another one immediately or redirect? 
            // Usually form reset.
            $this->form->fill([
                'acesso' => 3,
                'n_terminais' => 1,
                'validade_licenca' => now()->addDays(30)->format('Y-m-d'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Erro ao salvar')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getUfs(): array
    {
        $ufs = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
        return array_combine($ufs, $ufs);
    }
}
