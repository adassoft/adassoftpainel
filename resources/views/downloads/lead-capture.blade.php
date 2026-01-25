@extends('layouts.app')

@section('title', 'Acesso Restrito - ' . $download->titulo)

@section('extra-css')
    <style>
        /* Premium Modern Design - Lead Capture */
        .lead-page-container {
            min-height: calc(100vh - 80px);
            /* Adjust for fixed navbar height */
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 40px 20px;
        }

        .lead-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1), 0 5px 15px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
            position: relative;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .lead-header {
            background: linear-gradient(135deg, var(--primary-gradient-start, #4e73df) 0%, var(--primary-gradient-end, #224abe) 100%);
            padding: 40px 30px 30px;
            text-align: center;
            color: white;
            clip-path: ellipse(150% 100% at 50% 0%);
        }

        .lead-icon-circle {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .lead-title {
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .lead-subtitle {
            font-size: 0.9rem;
            opacity: 0.9;
            font-weight: 300;
        }

        .lead-body {
            padding: 30px 40px 40px;
        }

        /* Custom Input Styling */
        .form-group {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .form-control-custom {
            height: 50px;
            padding-left: 45px;
            /* Space for icon */
            border-radius: 12px;
            border: 2px solid #eaecf4;
            background-color: #f8f9fc;
            font-size: 0.95rem;
            color: #5a5c69;
            transition: all 0.3s ease;
        }

        .form-control-custom:focus {
            background-color: #fff;
            border-color: #4e73df;
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
        }

        .input-icon {
            position: absolute;
            top: 50px;
            /* Label height (approx) + input padding top */
            top: 38px;
            /* Adjusted based on label placement */
            left: 15px;
            color: #b7b9cc;
            font-size: 1.1rem;
            transition: color 0.3s;
            pointer-events: none;
        }

        /* Input Icon Positioning Fix with Labels */
        .form-group label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: #858796;
            margin-bottom: 8px;
            margin-left: 5px;
            letter-spacing: 0.5px;
        }

        .form-control-custom:focus+.input-icon,
        .form-group:focus-within .input-icon {
            color: #4e73df;
        }

        .btn-submit {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            height: 50px;
            border-radius: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
            box-shadow: 0 4px 15px rgba(78, 115, 223, 0.35);
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(78, 115, 223, 0.45);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            color: #1cc88a;
            margin-top: 20px;
            background: rgba(28, 200, 138, 0.1);
            padding: 8px;
            border-radius: 8px;
        }
    </style>
@endsection

@section('content')
    <div class="lead-page-container">
        <div class="lead-card">
            <!-- Header -->
            <div class="lead-header">
                <div class="lead-icon-circle">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="lead-title">Download Restrito</h1>
                <p class="lead-subtitle">{{ $download->titulo }}</p>
            </div>

            <!-- Body -->
            <div class="lead-body">
                @if(session('error'))
                    <div class="alert alert-danger shadow-sm border-0 rounded-lg text-center small mb-4">
                        <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                    </div>
                @endif

                <p class="text-center text-muted mb-4 small">
                    Informe seus dados para receber o <strong>link seguro</strong> no seu e-mail.
                </p>

                <form action="{{ route('downloads.lead.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="download_id" value="{{ $download->id }}">
                    <input type="hidden" name="version_id" value="{{ $versionId ?? '' }}">

                    <!-- Empresa -->
                    <div class="form-group">
                        <label>Empresa</label>
                        <input type="text" name="empresa" class="form-control form-control-custom" placeholder="Sua empresa"
                            required>
                        <i class="fas fa-building input-icon"></i>
                    </div>

                    <!-- Nome -->
                    <div class="form-group">
                        <label>Seu Nome</label>
                        <input type="text" name="nome" class="form-control form-control-custom" placeholder="Nome completo"
                            required>
                        <i class="fas fa-user input-icon"></i>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label>E-mail Corporativo</label>
                        <input type="email" name="email" class="form-control form-control-custom"
                            placeholder="seu@email.com" required>
                        <i class="fas fa-envelope input-icon"></i>
                    </div>

                    <!-- WhatsApp -->
                    <div class="form-group">
                        <label>WhatsApp <small class="text-muted ml-1 font-weight-normal">(Opcional)</small></label>
                        <input type="text" name="whatsapp" class="form-control form-control-custom"
                            placeholder="(00) 00000-0000">
                        <i class="fab fa-whatsapp input-icon"></i>
                    </div>

                    <!-- Recaptcha -->
                    @if(!empty($recaptchaSiteKey))
                        <div class="d-flex justify-content-center mb-4">
                            <div class="g-recaptcha" data-sitekey="{{ $recaptchaSiteKey }}"></div>
                            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                        </div>
                    @else
                        <!-- Fallback: Se não configurado, não quebra o layout, apenas não exibe -->
                        <div class="text-center mb-3 text-muted" style="display: none;">
                            <small>Protegido por reCAPTCHA</small>
                        </div>
                    @endif

                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary btn-block btn-submit text-white">
                        Enviar Link por E-mail
                    </button>

                    <div class="secure-badge">
                        <i class="fas fa-shield-alt mr-2"></i> Ambiento Seguro. Seus dados estão protegidos.
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection