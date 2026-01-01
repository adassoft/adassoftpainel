@extends('layouts.app')

@section('title', 'Central de Parcerias - Adassoft')

@section('extra-css')
    <style>
        .partner-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            height: 100%;
            background: #fff;
            position: relative;
            z-index: 1;
        }

        .partner-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: #4e73df;
            transition: height 0.3s ease;
            z-index: -1;
        }

        .partner-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .partner-card:hover::before {
            height: 100%;
            opacity: 0.03;
        }

        .icon-wrapper {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #f8f9fc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            color: #4e73df;
            transition: all 0.3s;
        }

        .partner-card:hover .icon-wrapper {
            transform: scale(1.1) rotate(5deg);
            background: #4e73df;
            color: #fff;
        }

        .hero-partners {
            padding: 100px 0 60px;
            background: linear-gradient(180deg, #fff 0%, #f8f9fc 100%);
        }
    </style>
@endsection

@section('content')
    <section class="hero-partners text-center">
        <div class="container">
            <h1 class="font-weight-bold display-4 text-gray-900 mb-3" data-aos="fade-down">Escolha sua Parceria</h1>
            <p class="lead text-muted mb-5" data-aos="fade-up" data-aos-delay="100">
                Junte-se ao ecossistema Adassoft. Selecione como você deseja crescer conosco.
            </p>

            <div class="row justify-content-center">
                <!-- Card Revendedor -->
                <div class="col-lg-5 mb-4" data-aos="fade-right" data-aos-delay="200">
                    <div class="card partner-card p-5 text-center shadow-lg">
                        <div class="card-body">
                            <div class="icon-wrapper">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <h3 class="font-weight-bold text-gray-900 mb-3">Quero Revender</h3>
                            <p class="text-muted mb-4">
                                Ideal para empresas de TI, consultores e agências. Venda nossos softwares consolidados (PDV,
                                ERP, WhatsApp) e ganhe comissões recorrentes.
                            </p>
                            <ul class="text-left text-muted mb-4 list-unstyled" style="padding-left: 20px;">
                                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Alta margem de lucro</li>
                                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Material de marketing pronto
                                </li>
                                <li class="mb-2"><i class="fas fa-check text-success mr-2"></i> Suporte prioritário</li>
                            </ul>
                            <a href="{{ route('reseller.lp') }}"
                                class="btn btn-primary btn-lg rounded-pill px-5 font-weight-bold btn-block">
                                Seja um Revendedor
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Card Desenvolvedor -->
                <div class="col-lg-5 mb-4" data-aos="fade-left" data-aos-delay="300">
                    <div class="card partner-card p-5 text-center shadow-lg">
                        <div class="card-body">
                            <div class="icon-wrapper">
                                <i class="fas fa-code"></i>
                            </div>
                            <h3 class="font-weight-bold text-gray-900 mb-3">Sou Desenvolvedor</h3>
                            <p class="text-muted mb-4">
                                Transforme seu software em um negócio escalável. Utilize nossa infraestrutura de
                                licenciamento, ativação e pagamentos para o seu próprio produto.
                            </p>
                            <ul class="text-left text-muted mb-4 list-unstyled" style="padding-left: 20px;">
                                <li class="mb-2"><i class="fas fa-check text-info mr-2"></i> API de Licenciamento Robusta
                                </li>
                                <li class="mb-2"><i class="fas fa-check text-info mr-2"></i> Painel White-label</li>
                                <li class="mb-2"><i class="fas fa-check text-info mr-2"></i> Gestão de Assinaturas (SaaS)
                                </li>
                            </ul>
                            <!-- Link para uma nova rota 'developers' ou direto para contato por enquanto -->
                            <a href="{{ route('reseller.register') }}?type=developer"
                                class="btn btn-outline-primary btn-lg rounded-pill px-5 font-weight-bold btn-block">
                                Integrar meu Software
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 text-muted" data-aos="fade-up">
                <p>Dúvidas sobre qual modelo escolher? <a href="#" class="font-weight-bold">Fale com nosso consultor.</a>
                </p>
            </div>
        </div>
    </section>
@endsection