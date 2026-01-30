@extends('layouts.app')

@section('title', 'Software Sob Medida - AdasSoft')
@section('meta_description', 'Transforme sua ideia em realidade com nosso desenvolvimento de software personalizado. Apps, Sistemas Web e Integrações.')

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="text-center mb-5">
                    <h1 class="display-4 font-weight-bold text-primary">Software Sob Medida</h1>
                    <p class="lead text-secondary">Você idealiza, nós construímos. Soluções exclusivas para o seu negócio.
                    </p>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-check-circle"></i> Recebemos sua ideia!</h4>
                        <p>{{ session('success') }}</p>
                        <hr>
                        <p class="mb-0">Fique de olho no seu e-mail/WhatsApp, entraremos em contato para agendar uma reunião de
                            briefing.</p>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3 class="mb-0 font-weight-bold">Solicitação de Projeto</h3>
                        <p class="mb-0 small text-white-50">Preencha com o máximo de detalhes possível</p>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <form action="{{ route('software-request.store') }}" method="POST">
                            @csrf

                            <h5 class="text-primary border-bottom pb-2 mb-4"><i class="fas fa-user mb-1"></i> Seus Dados
                            </h5>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="name" class="font-weight-bold">Nome Completo <span
                                            class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control form-control-lg @error('name') is-invalid @enderror" id="name"
                                        name="name" value="{{ old('name') }}" required placeholder="Ex: João da Silva">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="email" class="font-weight-bold">E-mail Corporativo <span
                                            class="text-danger">*</span></label>
                                    <input type="email"
                                        class="form-control form-control-lg @error('email') is-invalid @enderror" id="email"
                                        name="email" value="{{ old('email') }}" required placeholder="seu.nome@empresa.com">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label for="phone" class="font-weight-bold">WhatsApp/Telefone</label>
                                    <input type="text"
                                        class="form-control form-control-lg @error('phone') is-invalid @enderror" id="phone"
                                        name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999">
                                    <small class="text-muted">Para um contato mais ágil.</small>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="company" class="font-weight-bold">Nome da Empresa</label>
                                    <input type="text" class="form-control form-control-lg" id="company" name="company"
                                        value="{{ old('company') }}" placeholder="AdasSoft Ltda">
                                </div>
                            </div>

                            <h5 class="text-primary border-bottom pb-2 mb-4 mt-4"><i
                                    class="fas fa-project-diagram mb-1"></i> O Projeto</h5>

                            <div class="form-group">
                                <label for="project_name" class="font-weight-bold">Nome do Projeto (ou apelido)</label>
                                <input type="text" class="form-control form-control-lg" id="project_name"
                                    name="project_name" value="{{ old('project_name') }}"
                                    placeholder="Ex: App de Delivery, ERP Interno...">
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="project_type" class="font-weight-bold">Tipo de Solução <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control form-control-lg custom-select" id="project_type"
                                        name="project_type" required>
                                        <option value="" disabled selected>Selecione...</option>
                                        <option value="Web System" {{ old('project_type') == 'Web System' ? 'selected' : '' }}>Sistema Web / SaaS</option>
                                        <option value="Mobile App" {{ old('project_type') == 'Mobile App' ? 'selected' : '' }}>Aplicativo Mobile (Android)</option>
                                        <option value="Desktop" {{ old('project_type') == 'Desktop' ? 'selected' : '' }}>
                                            Software Desktop (Windows)</option>
                                        <option value="Integration" {{ old('project_type') == 'Integration' ? 'selected' : '' }}>Integração / API</option>
                                        <option value="Other" {{ old('project_type') == 'Other' ? 'selected' : '' }}>Outro
                                        </option>
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="budget_range" class="font-weight-bold">Estimativa de Investimento <span
                                            class="text-danger">*</span></label>
                                    <select class="form-control form-control-lg custom-select" id="budget_range"
                                        name="budget_range" required>
                                        <option value="" disabled selected>Selecione...</option>
                                        <option value="Under 5k" {{ old('budget_range') == 'Under 5k' ? 'selected' : '' }}>Até
                                            R$ 5.000</option>
                                        <option value="5k-15k" {{ old('budget_range') == '5k-15k' ? 'selected' : '' }}>R$
                                            5.000 - R$ 15.000</option>
                                        <option value="15k-30k" {{ old('budget_range') == '15k-30k' ? 'selected' : '' }}>R$
                                            15.000 - R$ 30.000</option>
                                        <option value="30k-50k" {{ old('budget_range') == '30k-50k' ? 'selected' : '' }}>R$
                                            30.000 - R$ 50.000</option>
                                        <option value="50k+" {{ old('budget_range') == '50k+' ? 'selected' : '' }}>Acima de R$
                                            50.000</option>
                                    </select>
                                    <small class="text-muted">Isso nos ajuda a definir a melhor tecnologia.</small>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="deadline" class="font-weight-bold">Prazo Desejado</label>
                                    <input type="text" class="form-control form-control-lg" id="deadline" name="deadline"
                                        value="{{ old('deadline') }}" placeholder="Ex: 3 meses, Urgente...">
                                </div>
                            </div>

                            <div class="form-group mt-3">
                                <label for="description" class="font-weight-bold">Descrição Detalhada do Projeto <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control summernote" id="description" name="description"
                                    required>{{ old('description') }}</textarea>
                                <small class="text-muted">Descreva o problema, usuários e sua visão da solução.</small>
                            </div>

                            <div class="form-group">
                                <label for="features_list" class="font-weight-bold">Lista de Funcionalidades Chave</label>
                                <textarea class="form-control summernote" id="features_list"
                                    name="features_list">{{ old('features_list') }}</textarea>
                                <small class="text-muted">Liste as funções indispensáveis para a primeira versão.</small>
                            </div>

                            <div class="text-center mt-5">
                                <button type="submit"
                                    class="btn btn-primary btn-lg px-5 py-3 shadow-sm rounded-pill font-weight-bold">
                                    <i class="fas fa-paper-plane mr-2"></i> Enviar Solicitação de Orçamento
                                </button>
                            </div>

                        </form>
                    </div>
                </div>

                <div class="text-center mt-5 mb-5">
                    <p class="text-muted">Prefere conversar diretamente?</p>
                    <a href="https://wa.me/5511999999999"
                        class="btn btn-outline-success rounded-pill px-4 font-weight-bold">
                        <i class="fab fa-whatsapp mr-1"></i> Falar no WhatsApp
                    </a>
                </div>

            </div>
        </div>
    </div>


@endsection

@section('extra-js')
<!-- Summernote Lite css/js (Independent of Bootstrap version) -->
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js" defer></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        console.log('Inicializando Summernote...');
        
        if (typeof jQuery !== 'undefined') {
            try {
                $('.summernote').summernote({
                    placeholder: 'Digite aqui os detalhes...',
                    tabsize: 2,
                    height: 200,
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'underline', 'clear']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['insert', ['link']],
                        ['view', ['fullscreen', 'codeview', 'help']] // codeview help debug
                    ],
                    lang: 'pt-BR'
                });
                console.log('Summernote inicializado com sucesso.');
            } catch (e) {
                console.error('Erro ao inicializar Summernote:', e);
            }
        } else {
            console.error('ERRO CRÍTICO: jQuery não foi encontrado. O Summernote precisa do jQuery.');
            // Fallback: tentar carregar jQuery se não existir? Melhor não arriscar conflito agora.
            // Apenas alertar no console.
        }
    });
</script>
@endsection