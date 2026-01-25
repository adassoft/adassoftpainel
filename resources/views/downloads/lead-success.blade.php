@extends('layouts.app')

@section('title', 'Link Enviado')

@section('extra-css')
    <style>
        /* Premium Modern Design - Lead Success */
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
            max-width: 500px;
            overflow: hidden;
            position: relative;
            animation: fadeInUp 0.6s ease-out;
            text-align: center;
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

        .success-header {
            background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
            padding: 40px 30px 30px;
            color: white;
            clip-path: ellipse(150% 100% at 50% 0%);
        }

        .success-icon-circle {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 36px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .success-title {
            font-weight: 800;
            font-size: 1.8rem;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        .lead-body {
            padding: 40px 40px 50px;
        }

        .email-highlight {
            font-weight: 700;
            color: #4e73df;
            font-size: 1.1rem;
            word-break: break-all;
            background: #f8f9fc;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px dashed #d1d3e2;
            margin: 15px 0;
            display: inline-block;
            width: 100%;
        }

        .info-box {
            background-color: #f1f8ff;
            border-left: 4px solid #36b9cc;
            padding: 15px;
            border-radius: 4px;
            font-size: 0.9rem;
            color: #5a5c69;
            margin-top: 25px;
            text-align: left;
        }

        .btn-back {
            margin-top: 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.85rem;
            color: #858796;
            transition: color 0.3s;
        }

        .btn-back:hover {
            color: #4e73df;
            text-decoration: none;
        }
    </style>
@endsection

@section('content')
    <div class="lead-page-container">
        <div class="lead-card">
            <!-- Header -->
            <div class="success-header">
                <div class="success-icon-circle">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="success-title">Link Enviado!</h1>
            </div>

            <!-- Body -->
            <div class="lead-body">
                <p class="text-gray-600 mb-2 lead" style="font-size: 1.1rem;">
                    O link de download seguro foi enviado para:
                </p>

                <div class="email-highlight">
                    {{ $email }}
                </div>

                <div class="info-box">
                    <i class="fas fa-info-circle mr-2 text-info"></i>
                    Verifique sua <strong>Caixa de Entrada</strong> e também a pasta de <strong>Spam/Lixo
                        Eletrônico</strong>. O link é válido por 24 horas.
                </div>

                <a href="{{ route('home') }}" class="d-block btn-back">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar para o Início
                </a>
            </div>
        </div>
    </div>
@endsection