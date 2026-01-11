<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Filament\Support\RawJs;

class MyCompany extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Minha Empresa';
    protected static ?string $title = 'Dados da Empresa';
    protected static ?string $slug = 'minha-empresa';
    protected static string $view = 'filament.app.pages.my-company';
    protected static ?int $sort = 10;
    protected static ?string $navigationGroup = 'Gestão';

    public ?array $data = [];

    public function mount(): void
    {
        $user = Auth::user();
        if (!$user)
            return;

        $cnpjLimpo = preg_replace('/\D/', '', $user->cnpj);
        if (!$cnpjLimpo) {
            $this->form->fill([
                'email' => $user->email
            ]);
            return;
        }

        $company = Company::where('cnpj', $cnpjLimpo)->first();

        if ($company) {
            $this->form->fill($company->toArray());
        } else {
            // Preenche com dados do user se a empresa ainda não existe
            $this->form->fill([
                'cnpj' => $user->cnpj, // Valor raw do user
                'email' => $user->email,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informações Cadastrais')
                    ->description('Mantenha os dados da sua empresa atualizados.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('razao')
                                    ->label('Razão Social')
                                    ->required()
                                    ->maxLength(100)
                                    ->columnSpanFull(),

                                TextInput::make('cnpj')
                                    ->label('CNPJ / CPF')
                                    ->disabled(fn() => !empty(Auth::user()->cnpj) && in_array(strlen(preg_replace('/\D/', '', Auth::user()->cnpj)), [11, 14]))
                                    ->helperText('Para alterar, entre em contato com o suporte.')
                                    ->dehydrated()
                                    ->required()
                                    ->mask(RawJs::make(<<<'JS'
                                        $input.length > 14 ? '99.999.999/9999-99' : '999.999.999-99'
                                    JS)),

                                TextInput::make('fone')
                                    ->label('Telefone / WhatsApp')
                                    ->required()
                                    ->mask('(99) 99999-9999')
                                    ->maxLength(20),

                                TextInput::make('email')
                                    ->label('E-mail Corporativo')
                                    ->email()
                                    ->required()
                                    ->maxLength(100),

                                TextInput::make('cidade')
                                    ->label('Cidade')
                                    ->required()
                                    ->maxLength(50),

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
                                        'TO' => 'TO',
                                    ])
                                    ->searchable()
                                    ->required(),

                                TextInput::make('bairro')
                                    ->label('Bairro')
                                    ->maxLength(50),

                                TextInput::make('cep')
                                    ->label('CEP')
                                    ->mask('99999-999'),
                            ])
                    ])
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = Auth::user();

        $cnpjLimpo = preg_replace('/\D/', '', $data['cnpj']);

        if (empty($cnpjLimpo)) {
            Notification::make()->danger()->title('Erro')->body('CNPJ inválido.')->send();
            return;
        }

        // Garante que salvamos apenas números
        $data['cnpj'] = $cnpjLimpo;

        // Update or Create
        $company = Company::where('cnpj', $cnpjLimpo)->first();

        if ($company) {
            $company->update($data);
        } else {
            // Create
            $data['data'] = now();
            $data['status'] = 'Ativo';

            // Tratamento de campos padrão se necessário
            Company::create($data);
        }

        // Garante vinculo
        if ($user->cnpj !== $cnpjLimpo) {
            $user->cnpj = $cnpjLimpo;
            $user->save();
        }

        Notification::make()->success()->title('Sucesso!')->body('Dados da empresa salvos.')->send();
    }
}
