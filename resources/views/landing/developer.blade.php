<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdasSoft for Developers | Monetize seu Software</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Fira+Code:wght@400&display=swap"
        rel="stylesheet">

    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <!-- Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --primary: #3b82f6;
            --accent: #8b5cf6;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-main);
            overflow-x: hidden;
            line-height: 1.6;
        }

        header {
            padding: 2rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            position: fixed;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .logo {
            font-weight: 700;
            font-size: 1.5rem;
            background: linear-gradient(to right, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            text-align: center;
            padding: 8rem 2rem 4rem;
            background:
                radial-gradient(circle at 20% 30%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(139, 92, 246, 0.15) 0%, transparent 50%);
        }

        h1 {
            font-size: 3.5rem;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            max-width: 900px;
        }

        h1 span {
            background: linear-gradient(135deg, #60a5fa 0%, #c084fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .lead {
            font-size: 1.25rem;
            color: var(--text-muted);
            max-width: 700px;
            margin-bottom: 2.5rem;
        }

        .cta-btn {
            background: linear-gradient(90deg, var(--primary), var(--accent));
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            font-size: 1.1rem;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }

        .code-block {
            background: #000;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
            font-family: 'Fira Code', monospace;
            font-size: 0.9rem;
            color: #a5b4fc;
            text-align: left;
            margin-top: 3rem;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.5);
            max-width: 600px;
            width: 100%;
        }

        .features {
            padding: 6rem 5%;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .card {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            border-color: var(--primary);
        }

        .card i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: block;
        }

        .card h3 {
            margin-bottom: 0.5rem;
            color: white;
        }

        .card p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .waitlist-section {
            background: linear-gradient(to bottom, var(--bg-dark), #111827);
            padding: 6rem 5%;
            text-align: center;
        }

        .form-box {
            max-width: 500px;
            margin: 0 auto;
            background: var(--bg-card);
            padding: 2.5rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: left;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #cbd5e1;
        }

        .input-group input,
        .input-group textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            color: white;
            font-family: inherit;
        }

        .input-group input:focus,
        .input-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>

<body>

    <header data-aos="fade-down">
        <div class="logo">AdasSoft <span style="font-weight:300; color:white; font-size:0.8em;">Developers</span></div>
        <a href="#join" class="cta-btn" style="padding: 0.5rem 1.5rem; font-size: 0.9rem;">Lista de Espera</a>
    </header>

    <section class="hero">
        <div data-aos="fade-up">
            <span
                style="background: rgba(59, 130, 246, 0.1); color: #60a5fa; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1rem; display: inline-block;">Early
                Access</span>
            <h1>Transforme Código em <br> <span>Receita Recorrente</span></h1>
            <p class="lead">A infraestrutura completa de licenciamento, pagamentos e rede de revendas para o seu
                software. Foque no produto, nós cuidamos da escala.</p>
            <a href="#join" class="cta-btn">Solicitar Convite</a>
        </div>

        <div class="code-block" data-aos="flip-up" data-aos-delay="200">
            <div style="display:flex; gap:5px; margin-bottom:10px;">
                <div style="width:10px; height:10px; border-radius:50%; background:#ef4444;"></div>
                <div style="width:10px; height:10px; border-radius:50%; background:#eab308;"></div>
                <div style="width:10px; height:10px; border-radius:50%; background:#22c55e;"></div>
            </div>
            <span style="color: #c084fc;">class</span> <span style="color: #60a5fa;">MySoftware</span> <span
                style="color: #c084fc;">extends</span> AdasMarketplace {<br>
            &nbsp;&nbsp;<span style="color: #c084fc;">public function</span> <span
                style="color: #facc15;">launch</span>() {<br>
            &nbsp;&nbsp;&nbsp;&nbsp;<span style="color: #94a3b8;">// Distribuição instantânea para 500+
                revendas</span><br>
            &nbsp;&nbsp;&nbsp;&nbsp;$this->distribute(<span style="color: #22c55e;">'GLOBAL_NETWORK'</span>);<br>
            &nbsp;&nbsp;&nbsp;&nbsp;$this->enableLicensing();<br>
            &nbsp;&nbsp;&nbsp;&nbsp;$this->collectRoyaties();<br>
            &nbsp;&nbsp;}<br>
            }
        </div>
    </section>

    <section class="features">
        <div class="card" data-aos="fade-up" data-aos-delay="100">
            <i class="ph ph-shield-check"></i>
            <h3>Licenciamento Seguro</h3>
            <p>API robusta com validação online e offline, criptografia de ponta a ponta e gestão de terminais.</p>
        </div>
        <div class="card" data-aos="fade-up" data-aos-delay="200">
            <i class="ph ph-storefront"></i>
            <h3>Rede de Revendas</h3>
            <p>Seu produto disponível para milhares de revendedores cadastrados em nossa plataforma instantaneamente.
            </p>
        </div>
        <div class="card" data-aos="fade-up" data-aos-delay="300">
            <i class="ph ph-currency-dollar"></i>
            <h3>Pagamentos Automatizados</h3>
            <p>Checkout integrado com Split de Pagamentos. Receba seus royalties direto na conta a cada venda.</p>
        </div>
    </section>

    <section id="join" class="waitlist-section">
        <div class="form-box" data-aos="zoom-in">
            <h2 style="margin-bottom: 2rem; color: white;">Pré-Cadastro Dev</h2>
            <p style="margin-bottom: 2rem; color: var(--text-muted);">Estamos selecionando parceiros para a fase beta.
                Garanta sua vaga.</p>

            @if(session('success'))
                <div
                    style="background: rgba(34, 197, 94, 0.2); color: #4ade80; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; text-align: center;">
                    {{ session('success') }}
                </div>
            @else
                <form action="{{ route('developer.store') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <label>Nome Completo</label>
                        <input type="text" name="name" required placeholder="Seu nome">
                    </div>
                    <div class="input-group">
                        <label>E-mail Corporativo</label>
                        <input type="email" name="email" required placeholder="dev@tech.com">
                    </div>
                    <div class="input-group">
                        <label>WhatsApp / Telegram</label>
                        <input type="text" name="whatsapp" placeholder="(11) 99999-9999">
                    </div>
                    <div class="input-group">
                        <label>Sobre sua Solução (Breve descrição)</label>
                        <textarea name="software_description" rows="3"
                            placeholder="Ex: ERP para Clínicas com foco em..."></textarea>
                    </div>
                    <button type="submit" class="cta-btn" style="width: 100%;">Entrar na Lista</button>
                </form>
            @endif
        </div>
    </section>

    <footer style="text-align: center; padding: 2rem; color: #475569; font-size: 0.9rem;">
        &copy; {{ date('Y') }} AdasSoft. All rights reserved. Code is Poetry.
    </footer>

    <script>
        AOS.init({
            duration: 800,
            once: true,
        });
    </script>

</body>

</html>