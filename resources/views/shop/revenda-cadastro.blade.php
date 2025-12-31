@extends('layouts.app')

@section('title', 'Cadastro de Revenda | Adassoft')

@section('extra-css')
    <style>
        /* Esconder Navbar e Footer nesta página */
        .navbar-landing,
        .footer,
        main {
            padding-top: 0 !important;
        }

        .navbar-landing,
        .footer {
            display: none !important;
        }

        body {
            background-color: #f4f6f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        /* Card Centralizado */
        .auth-container {
            width: 100%;
            max-width: 1000px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
        }

        /* Coluna Esquerda: Banner Verde */
        .side-banner {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            width: 40%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
            padding: 3rem 2rem;
            position: relative;
        }

        /* Detalhes circulares no fundo do banner verde */
        .side-banner::before {
            content: '';
            position: absolute;
            top: -50px;
            left: -50px;
            width: 180px;
            height: 180px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .brand-logo {
            width: 110px;
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 0.5rem 1rem rgba(0, 0, 0, 0.2));
            z-index: 2;
        }

        .brand-name {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
            z-index: 2;
        }

        .brand-sub {
            font-size: 1rem;
            font-weight: 300;
            opacity: 0.9;
            z-index: 2;
        }

        /* Coluna Direita: Formulário */
        .form-side {
            width: 60%;
            padding: 2.5rem 3rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.25rem;
        }

        .form-header p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .section-title {
            font-size: 0.65rem;
            font-weight: 800;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            margin: 1.25rem 0 0.75rem;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #f1f5f9;
            margin-left: 1rem;
        }

        .form-group {
            margin-bottom: 0.75rem;
        }

        .form-control {
            border-radius: 4px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            height: auto;
        }

        .form-control:focus {
            background: #fff;
            border-color: #1cc88a;
            box-shadow: 0 0 0 3px rgba(28, 200, 138, 0.1);
        }

        .btn-submit {
            background: #1cc88a;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 0.6rem;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
        }

        .btn-submit:hover:not(:disabled) {
            background: #13855c;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(28, 200, 138, 0.3);
        }

        .btn-submit:disabled {
            opacity: 0.8;
            cursor: not-allowed;
        }

        .spinner-loading {
            display: none !important;
        }

        .btn-submit.loading .spinner-loading {
            display: inline-block !important;
        }

        .captcha-container {
            background: #f0fdf4;
            border: 1px solid #dcfce7;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.75rem;
        }

        .captcha-label {
            font-weight: 700;
            color: #166534;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bottom-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
        }

        .bottom-link a {
            color: #4e73df;
            font-weight: 700;
            text-decoration: none;
        }

        /* Mobile adjustments */
        @media (max-width: 991.98px) {
            body {
                padding: 1rem;
            }

            .auth-container {
                flex-direction: column;
            }

            .side-banner {
                width: 100%;
                padding: 2.5rem 1rem;
            }

            .form-side {
                width: 100%;
                padding: 2rem 1.5rem;
            }
        }
    </style>
@endsection

@section('content')
    <div class="auth-container">
        <!-- Banner Esquerdo -->
        <div class="side-banner">
            <img src="{{ asset('favicon.svg') }}" alt="Shield Logo" class="brand-logo">
            <h2 class="brand-name">SEJA PARCEIRO</h2>
            <p class="brand-sub">Revenda e Lucre com o {{ config('app.name', 'Adassoft') }}</p>
        </div>

        <!-- Formulário Direito -->
        <div class="form-side">
            <div class="form-header">
                <h1>Cadastro de Revenda</h1>
                <p>Junte-se ao nosso time de sucesso.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm rounded mb-4 py-2 small">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm rounded mb-4 py-2 small">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('reseller.store') }}" method="POST">
                @csrf

                <div class="section-title">Seus Dados</div>

                <div class="row no-gutters">
                    <div class="col-md-6 pr-md-1">
                        <div class="form-group">
                            <input type="text" name="nome" class="form-control" placeholder="Seu Nome Completo"
                                value="{{ old('nome') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6 pl-md-1">
                        <div class="form-group">
                            <input type="text" name="login" class="form-control" placeholder="Usuário (Login)"
                                value="{{ old('login') }}" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <input type="email" name="email" class="form-control" placeholder="E-mail Corporativo"
                                value="{{ old('email') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6 pr-md-1">
                        <div class="form-group">
                            <input type="password" name="senha" class="form-control" placeholder="Crie uma Senha" required>
                        </div>
                    </div>
                    <div class="col-md-6 pl-md-1">
                        <div class="form-group">
                            <input type="password" name="senha_confirmation" class="form-control"
                                placeholder="Confirme a Senha" required>
                        </div>
                    </div>
                </div>

                <div class="section-title">Dados da Revenda</div>

                <div class="row no-gutters">
                    <div class="col-md-6 pr-md-1">
                        <div class="form-group">
                            <input type="text" name="cnpj" class="form-control" placeholder="CNPJ ou CPF" id="cnpj_mask"
                                value="{{ old('cnpj') }}" required>
                        </div>
                    </div>
                    <div class="col-md-6 pl-md-1">
                        <div class="form-group">
                            <input type="text" name="fone" class="form-control" placeholder="WhatsApp Comercial"
                                id="fone_mask" value="{{ old('fone') }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <input type="text" name="razao" class="form-control" placeholder="Razão Social"
                                value="{{ old('razao') }}">
                        </div>
                    </div>
                    <div class="col-md-4 pr-md-1">
                        <div class="form-group">
                            <input type="text" name="cep" class="form-control" placeholder="CEP" id="cep_mask"
                                value="{{ old('cep') }}">
                        </div>
                    </div>
                    <div class="col-md-8 pl-md-1">
                        <div class="form-group">
                            <input type="text" name="endereco" class="form-control" placeholder="Endereço"
                                value="{{ old('endereco') }}">
                        </div>
                    </div>
                    <div class="col-md-5 pr-md-1">
                        <div class="form-group">
                            <input type="text" name="bairro" class="form-control" placeholder="Bairro"
                                value="{{ old('bairro') }}">
                        </div>
                    </div>
                    <div class="col-md-5 px-md-1">
                        <div class="form-group">
                            <input type="text" name="cidade" class="form-control" placeholder="Cidade"
                                value="{{ old('cidade') }}">
                        </div>
                    </div>
                    <div class="col-md-2 pl-md-1">
                        <div class="form-group">
                            <select name="uf" class="form-control" style="padding: 0.5rem 0.25rem;" required>
                                <option value="">UF</option>
                                @foreach(['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'] as $uf)
                                    <option value="{{ $uf }}" {{ old('uf') == $uf ? 'selected' : '' }}>{{ $uf }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="captcha-container">
                    <div class="captcha-label">
                        <i class="fas fa-shield-alt"></i> Check:
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="mr-3 font-weight-bold text-dark">{{ $num1 }} + {{ $num2 }} =</span>
                        <input type="number" name="captcha_challenge" class="form-control text-center"
                            style="width: 60px; height: 30px; padding: 0;" required>
                    </div>
                </div>

                <button type="submit" class="col-12 btn-submit" id="btn-register">
                    <i class="fas fa-spinner fa-spin spinner-loading"></i>
                    <span id="btn-text">Quero ser Parceiro</span>
                </button>

                <div class="bottom-link">
                    <a href="/admin/login">Já sou parceiro? Fazer Login!</a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script>
        $(document).ready(function () {
            var behavior = function (val) {
                return val.replace(/\D/g, '').length === 11 ? '000.000.000-009' : '00.000.000/0000-00';
            },
                options = {
                    onKeyPress: function (val, e, field, options) {
                        field.mask(behavior.apply({}, arguments), options);
                    }
                };
            $('#cnpj_mask').mask(behavior, options);
            $('#fone_mask').mask('(00) 00000-0000');
            $('#cep_mask').mask('00000-000');

            // Lógica de submissão com animação
            $('form').on('submit', function () {
                var $btn = $('#btn-register');
                var $text = $('#btn-text');

                $btn.addClass('loading').attr('disabled', true);
                $text.text('Processando...');
            });
        });
    </script>
@endsection