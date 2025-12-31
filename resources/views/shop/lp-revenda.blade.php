@extends('layouts.app')

@section('title', 'Seja um Parceiro Revendedor - Lucro Recorrente | Adassoft')

@section('extra-css')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', 'Nunito', sans-serif;
        }

        /* Hero Section */
        .hero-lp {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            color: white;
            padding: 100px 0 100px;
            position: relative;
            overflow: hidden;
            margin-top: -72px;
        }

        .hero-lp::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: #f8f9fc;
            transform: skewY(-2deg);
        }

        .hero-title {
            font-weight: 800;
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
        }

        .hero-lead {
            font-size: 1.25rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            max-width: 600px;
        }

        .btn-cta-lp {
            background: white;
            color: #1cc88a !important;
            font-weight: 800;
            padding: 18px 40px;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s;
            text-decoration: none !important;
            display: inline-block;
        }

        .btn-cta-lp:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            background: #f0fff4;
        }

        /* Feature Cards */
        .feature-card-lp {
            background: white;
            border-radius: 20px;
            padding: 40px;
            height: 100%;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            text-align: center;
        }

        .feature-card-lp:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        }

        .icon-square {
            width: 70px;
            height: 70px;
            background: #effaf6;
            color: #1cc88a;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            margin: 0 auto 25px;
        }

        /* Section Headings */
        .section-title {
            font-weight: 800;
            color: #2e384d;
            margin-bottom: 15px;
        }

        .section-subtitle {
            color: #858796;
            font-size: 1.1rem;
            margin-bottom: 50px;
        }

        .whitelabel-box {
            background: #2e384d;
            border-radius: 30px;
            color: white;
            padding: 60px;
            margin-top: 50px;
            position: relative;
            overflow: hidden;
        }

        .cta-bottom-lp {
            background: #4e73df;
            color: white;
            padding: 80px 0;
            text-align: center;
            border-radius: 20px;
            margin-bottom: 80px;
        }

        @media (max-width: 991px) {
            .hero-title {
                font-size: 2.5rem;
            }
        }
    </style>
@endsection

@section('content')
    <!-- Hero Section -->
    <header class="hero-lp">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6" data-aos="fade-right">
                    <h1 class="hero-title">Sua própria Fábrica de Software.</h1>
                    <p class="hero-lead">Tenha uma marca de softwares de gestão sem escrever uma linha de código. Use nossa
                        estrutura White Label e lucre com recorrência.</p>
                    <a href="{{ route('reseller.register') }}" class="btn-cta-lp">
                        Ser Um Parceiro <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                    <div class="mt-4 opacity-8 d-flex align-items-center">
                        <span class="mr-4"><i class="fas fa-check-circle mr-1"></i> Setup em 24h</span>
                        <span><i class="fas fa-check-circle mr-1"></i> Lucro de 300% até 500%</span>
                    </div>
                </div>
                <div class="col-lg-6 d-none d-lg-block" data-aos="fade-left">
                    <img src="https://images.unsplash.com/photo-1460925895917-afdab827c52f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                        alt="Dashboard" class="img-fluid rounded shadow-2xl"
                        style="border-radius: 25px; transform: perspective(1000px) rotateY(-10deg) rotateX(5deg);">
                </div>
            </div>
        </div>
    </header>

    <!-- Vantagens -->
    <section class="py-5 mt-5">
        <div class="container text-center mb-5">
            <h2 class="section-title" data-aos="fade-up">Acelere seu faturamento recorrente</h2>
            <p class="section-subtitle" data-aos="fade-up" data-aos-delay="100">Não vendemos apenas software, entregamos um
                negócio pronto para você operar.</p>

            <div class="row mt-5">
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card-lp">
                        <div class="icon-square"><i class="fas fa-palette"></i></div>
                        <h4 class="font-weight-bold">White Label Real</h4>
                        <p class="text-muted">Sua logomarca, suas cores e seu domínio. O cliente final nunca saberá da
                            existência da Adassoft.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-card-lp">
                        <div class="icon-square"><i class="fas fa-hand-holding-usd"></i></div>
                        <h4 class="font-weight-bold">Custo de Licença Fixo</h4>
                        <p class="text-muted">Pague um valor fixo e acessível por cliente ativo. Você decide quanto cobrar
                            na ponta final.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-4" data-aos="fade-up" data-aos-delay="400">
                    <div class="feature-card-lp">
                        <div class="icon-square"><i class="fas fa-rocket"></i></div>
                        <h4 class="font-weight-bold">Automação Total</h4>
                        <p class="text-muted">Geração automática de instaladores, cobrança e liberação de licenças sem
                            intervenção manual.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- White Label Showcase -->
    <section class="container py-5">
        <div class="whitelabel-box" data-aos="zoom-in">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <h2 class="font-weight-bold mb-4">Painel Administrativo da Revenda</h2>
                    <p class="lead mb-4">Gerencie todos os seus clientes, licenças e suporte em um dashboard unificado e
                        poderoso.</p>
                    <ul class="list-unstyled mb-5">
                        <li class="mb-3 d-flex align-items-center"><i class="fas fa-check-circle text-success mr-2"></i>
                            Monitoramento em tempo real</li>
                        <li class="mb-3 d-flex align-items-center"><i class="fas fa-check-circle text-success mr-2"></i>
                            Criação de licenças em 2 cliques</li>
                        <li class="mb-3 d-flex align-items-center"><i class="fas fa-check-circle text-success mr-2"></i>
                            Relatórios financeiros detalhados</li>
                    </ul>
                    <a href="{{ route('reseller.register') }}"
                        class="btn btn-light btn-lg rounded-pill px-5 font-weight-bold text-primary">
                        Quero Conhecer o Painel
                    </a>
                </div>
                <div class="col-lg-6">
                    <img src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                        alt="Analytics" class="img-fluid rounded"
                        style="border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
                </div>
            </div>
        </div>
    </section>

    <!-- Softwares Inclusos -->
    <section class="py-5 bg-white">
        <div class="container py-5 text-center">
            <h2 class="section-title">Nossa "Prateleira" é a Sua</h2>
            <p class="section-subtitle">Venda soluções validadas para diversos nichos de mercado.</p>

            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="p-4 rounded-xl shadow-sm border h-100">
                        <i class="fas fa-cash-register fa-3x text-primary mb-3"></i>
                        <h5 class="font-weight-bold text-dark">PDV Varejo</h5>
                        <p class="small text-muted">Controle de estoque, vendas e relatórios.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="p-4 rounded-xl shadow-sm border h-100">
                        <i class="fas fa-notes-medical fa-3x text-primary mb-3"></i>
                        <h5 class="font-weight-bold text-dark">Clin/Pet</h5>
                        <p class="small text-muted">Gestão para clínicas e consultórios.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="p-4 rounded-xl shadow-sm border h-100">
                        <i class="fas fa-shopping-cart fa-3x text-primary mb-3"></i>
                        <h5 class="font-weight-bold text-dark">E-commerce</h5>
                        <p class="small text-muted">Integração com marketplaces e vendas online.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="p-4 rounded-xl shadow-sm border h-100">
                        <i class="fas fa-file-invoice-dollar fa-3x text-primary mb-3"></i>
                        <h5 class="font-weight-bold text-dark">ERP Completo</h5>
                        <p class="small text-muted">A solução definitiva para médias empresas.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="text-center font-weight-bold mb-5">Dúvidas Frequentes</h2>
                <div class="accordion shadow-sm" id="faqAccordion">
                    <div class="card border-0">
                        <div class="card-header bg-white p-4" id="headingOne">
                            <h5 class="mb-0">
                                <button class="btn btn-link text-decoration-none text-dark font-weight-bold w-100 text-left"
                                    type="button" data-toggle="collapse" data-target="#collapseOne">
                                    Paga algum valor para começar?
                                </button>
                            </h5>
                        </div>
                        <div id="collapseOne" class="collapse show" data-parent="#faqAccordion">
                            <div class="card-body p-4 text-muted">
                                Não! O cadastro é gratuito e você tem acesso ao painel de revendedor imediatamente. Você só
                                paga quando for ativar a primeira licença paga de um cliente.
                            </div>
                        </div>
                    </div>
                    <!-- Mais itens podem ser adicionados aqui -->
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="container mt-5">
        <div class="cta-bottom-lp" data-aos="flip-up">
            <h2 class="font-weight-bold mb-3">Pronto para montar sua softhouse?</h2>
            <p class="mb-4 opacity-8 lead">Junte-se a centenas de parceiros que lucram com a tecnologia Adassoft.</p>
            <a href="{{ route('reseller.register') }}"
                class="btn btn-light btn-lg px-5 rounded-pill font-weight-bold text-primary shadow-lg py-3">
                COMEÇAR AGORA - É GRÁTIS <i class="fas fa-check-circle ml-2"></i>
            </a>
        </div>
    </section>
@endsection