<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edfinance - Controle Financeiro Inteligente</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        /* --- DESIGN SYSTEM "UNICÓRNIO" --- */
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --accent: #f72585; 
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient-hero: radial-gradient(circle at top right, #eef2ff 0%, #fff 100%);
            --shadow-soft: 0 20px 40px -10px rgba(0,0,0,0.05);
            --shadow-hover: 0 30px 60px -15px rgba(67, 97, 238, 0.15);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark);
            background-color: #fff;
            overflow-x: hidden;
        }

        /* --- NAVBAR --- */
        .navbar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0,0,0,0.03);
            padding: 15px 0;
        }
        .nav-link { font-weight: 600; color: var(--dark); margin: 0 10px; transition: 0.2s; }
        .nav-link:hover { color: var(--primary); }
        
        .btn-nav-login { 
            border: 1px solid #e2e8f0; color: var(--dark); border-radius: 50px; 
            padding: 8px 25px; font-weight: 700; transition: 0.2s; background: transparent;
        }
        .btn-nav-login:hover { border-color: var(--dark); background: transparent; color: #000; }

        /* --- HERO SECTION --- */
        .hero-section {
            padding: 160px 0 100px;
            background: var(--gradient-hero);
            position: relative;
            overflow: hidden;
        }

        .badge-hero {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary);
            font-weight: 700;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 25px;
        }

        .hero-title {
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -1.5px;
            margin-bottom: 25px;
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-img {
            border-radius: 24px;
            box-shadow: 0 40px 80px -20px rgba(67, 97, 238, 0.25);
            border: 8px solid rgba(255,255,255,0.6);
            transform: perspective(1000px) rotateY(-10deg) rotateX(5deg);
            transition: all 0.5s ease;
        }
        .hero-img:hover {
            transform: perspective(1000px) rotateY(0deg) rotateX(0deg) scale(1.02);
            box-shadow: 0 50px 100px -20px rgba(67, 97, 238, 0.35);
        }

        /* --- BENTO GRID (FUNCIONALIDADES) --- */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 24px;
        }
        .bento-card {
            background: var(--light);
            border-radius: 30px;
            padding: 40px;
            border: 1px solid #f1f5f9;
            transition: 0.3s;
            overflow: hidden;
            position: relative;
            height: 100%;
        }
        .bento-card:hover { transform: translateY(-5px); border-color: var(--primary); background: #fff; box-shadow: var(--shadow-hover); }
        
        .span-4 { grid-column: span 4; }
        .span-6 { grid-column: span 6; }
        .span-8 { grid-column: span 8; }
        .span-12 { grid-column: span 12; }
        @media (max-width: 992px) { .span-4, .span-6, .span-8 { grid-column: span 12; } }

        .icon-bento {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        /* --- DETAILED FEATURES (ZIG ZAG) --- */
        .feature-row { padding: 80px 0; }
        .feature-img-box {
            background: white;
            border-radius: 30px;
            padding: 40px;
            box-shadow: var(--shadow-soft);
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        /* Simulação de UI com CSS (Metas) */
        .ui-progress-card {
            background: white; border-radius: 16px; padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); margin-bottom: 15px; border: 1px solid #f1f5f9;
        }
        .ui-progress-bar { height: 8px; background: #f1f5f9; border-radius: 10px; margin-top: 10px; overflow: hidden; }
        .ui-progress-fill { height: 100%; border-radius: 10px; }

        /* Simulação de UI (Gráfico) */
        .ui-chart-circle {
            width: 150px; height: 150px; border-radius: 50%;
            background: conic-gradient(#4361ee 0% 60%, #f72585 60% 85%, #e2e8f0 85% 100%);
            margin: 0 auto; position: relative;
        }
        .ui-chart-inner {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 100px; height: 100px; background: white; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-weight: 800;
        }

        /* --- PLANOS & TESTIMONIALS --- */
        .pricing-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 30px;
            padding: 3rem 2rem; text-align: center; position: relative; transition: 0.3s;
        }
        .pricing-card.featured {
            border: 2px solid var(--primary);
            box-shadow: var(--shadow-hover);
            transform: scale(1.05); z-index: 2;
        }
        .testimonial-card {
            background: white; border: 1px solid #f1f5f9; border-radius: 24px;
            padding: 30px; transition: 0.3s; height: 100%;
        }
        .testimonial-card:hover { border-color: var(--primary); box-shadow: var(--shadow-soft); }

        /* --- BOTÕES --- */
        .btn-cta {
            background: var(--primary); color: white;
            padding: 15px 40px; border-radius: 50px; font-weight: 700; border: none;
            box-shadow: 0 10px 25px -5px rgba(67, 97, 238, 0.4); transition: 0.3s;
        }
        .btn-cta:hover { background: var(--secondary); transform: translateY(-3px); color: white; }
        
        .accordion-button:not(.collapsed) { background: #eef2ff; color: var(--primary); }
        .accordion-button { font-weight: 700; border-radius: 16px !important; }
        .accordion-item { border: none; margin-bottom: 10px; }

    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center" href="#">
                <div class="bg-primary text-white rounded-3 d-flex align-items-center justify-content-center me-2 shadow-sm" style="width: 38px; height: 38px;">
                    <i class="bi bi-wallet2"></i>
                </div>
                Edfinance
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center gap-3">
                    <li class="nav-item"><a class="nav-link" href="#funcionalidades">Recursos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#planos">Planos</a></li>
                    <li class="nav-item"><a class="nav-link" href="#depoimentos">Depoimentos</a></li>
                    <li class="nav-item ms-lg-2">
                        <a href="index.php?pg=login" class="btn-nav-login">Entrar</a>
                    </li>
                    <li class="nav-item">
                        <a href="#planos" class="btn btn-sm btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">Criar Conta</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <span class="badge-hero"><i class="bi bi-lightning-charge-fill me-2"></i> Gestão Financeira 2.0</span>
                    <h1 class="display-4 hero-title">
                        O único que prevê o futuro do seu bolso.
                    </h1>
                    <p class="lead text-muted mb-5 pe-lg-5">
                        Pare de olhar apenas para o saldo de hoje. 
                        Com a nossa <strong>Inteligência de Cartão</strong>, você sabe exatamente quanto vai sobrar no final do ano, considerando parcelas e assinaturas.
                    </p>
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <a href="#planos" class="btn btn-cta">
                            Começar Grátis <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                        <div class="d-flex align-items-center ms-2 text-muted fw-bold small">
                            <i class="bi bi-shield-check fs-4 text-success me-2"></i> 7 dias de garantia
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="dashboard-hero.jpg" class="img-fluid hero-img" alt="Dashboard Edfinance">
                </div>
            </div>
        </div>
    </section>

    <div class="py-4 border-top border-bottom bg-white">
        <div class="container text-center">
            <p class="small text-muted fw-bold text-uppercase mb-3 tracking-wide">Integração manual inteligente com</p>
            <div class="d-flex justify-content-center gap-5 opacity-50 flex-wrap">
                <i class="bi bi-bank fs-2" title="Nubank"></i>
                <i class="bi bi-credit-card-2-front fs-2" title="Inter"></i>
                <i class="bi bi-wallet2 fs-2" title="Itaú"></i>
                <i class="bi bi-phone fs-2" title="XP"></i>
            </div>
        </div>
    </div>

    <section class="py-5" id="funcionalidades">
        <div class="container py-5">
            <div class="text-center mb-5 mx-auto" style="max-width: 700px;">
                <h6 class="text-primary fw-bold text-uppercase">Tudo em um só lugar</h6>
                <h2 class="fw-bold display-6">Controle total, sem complicação.</h2>
                <p class="text-muted">Desenvolvemos o que faltava nos apps de banco.</p>
            </div>

            <div class="bento-grid">
                
                <div class="bento-card span-8">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="icon-bento bg-success bg-opacity-10 text-success"><i class="bi bi-graph-up-arrow"></i></div>
                            <h4 class="fw-bold">Saldo Real vs. Projetado</h4>
                            <p class="text-muted mb-4">
                                Diferenciamos o dinheiro que você <strong>tem hoje</strong> do dinheiro que vai <strong>sobrar</strong> após pagar as faturas já agendadas.
                            </p>
                        </div>
                    </div>
                    <div class="p-3 bg-white rounded-4 border shadow-sm d-flex gap-4 align-items-center">
                        <div>
                            <small class="text-muted d-block text-uppercase" style="font-size:0.7rem">Saldo Caixa</small>
                            <span class="fw-bold text-primary fs-5">R$ 2.500</span>
                        </div>
                        <div class="border-start ps-4">
                            <small class="text-muted d-block text-uppercase" style="font-size:0.7rem">Projeção Final</small>
                            <span class="fw-bold text-dark fs-5">R$ 5.850</span>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-success bg-opacity-10 text-success">No Azul</span>
                        </div>
                    </div>
                </div>

                <div class="bento-card span-4 bg-primary text-white border-0" style="background: linear-gradient(135deg, #4361ee, #3a0ca3);">
                    <div class="icon-bento bg-white bg-opacity-25 text-white"><i class="bi bi-credit-card-fill"></i></div>
                    <h4 class="fw-bold">Limite Global</h4>
                    <p class="opacity-75 small">Soma inteligente de todos os cartões. Saiba seu poder de compra real.</p>
                    <div class="mt-3 bg-white bg-opacity-10 rounded-3 p-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small>Comprometido</small>
                            <small class="fw-bold">70%</small>
                        </div>
                        <div class="progress" style="height: 6px;"><div class="progress-bar bg-white" style="width: 70%"></div></div>
                    </div>
                </div>

                <div class="bento-card span-4">
                    <div class="icon-bento bg-warning bg-opacity-10 text-warning"><i class="bi bi-robot"></i></div>
                    <h4 class="fw-bold">Robô de Assinaturas</h4>
                    <p class="text-muted small">Netflix, Spotify... Cadastre uma vez e nosso robô lança todo mês automaticamente.</p>
                </div>

                <div class="bento-card span-4">
                    <div class="icon-bento bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-check"></i></div>
                    <h4 class="fw-bold">Data de Fechamento</h4>
                    <p class="text-muted small">O sistema sabe se a compra cai na fatura atual ou na próxima automaticamente.</p>
                </div>

                <div class="bento-card span-4">
                    <div class="icon-bento bg-danger bg-opacity-10 text-danger"><i class="bi bi-calendar3"></i></div>
                    <h4 class="fw-bold">Visão de Futuro</h4>
                    <p class="text-muted small">Navegue até Dezembro e veja o impacto dos parcelamentos de hoje.</p>
                </div>

            </div>
        </div>
    </section>

    <section class="feature-row bg-light">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="feature-img-box">
                        <div class="ui-progress-card">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-dark"><i class="bi bi-cart me-2"></i>Mercado</span>
                                <span class="fw-bold text-warning">85%</span>
                            </div>
                            <div class="ui-progress-bar"><div class="ui-progress-fill bg-warning" style="width: 85%"></div></div>
                            <small class="text-muted mt-2 d-block">R$ 850 de R$ 1.000</small>
                        </div>

                        <div class="ui-progress-card">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-dark"><i class="bi bi-car-front me-2"></i>Transporte</span>
                                <span class="fw-bold text-success">40%</span>
                            </div>
                            <div class="ui-progress-bar"><div class="ui-progress-fill bg-success" style="width: 40%"></div></div>
                            <small class="text-muted mt-2 d-block">R$ 200 de R$ 500</small>
                        </div>

                        <div class="ui-progress-card mb-0">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-dark"><i class="bi bi-controller me-2"></i>Lazer</span>
                                <span class="fw-bold text-danger">110%</span>
                            </div>
                            <div class="ui-progress-bar"><div class="ui-progress-fill bg-danger" style="width: 100%"></div></div>
                            <small class="text-danger fw-bold mt-2 d-block">Estourou R$ 50,00</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5 offset-lg-1">
                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill fw-bold mb-3">Novo</span>
                    <h2 class="fw-bold display-6 mb-3">Defina Metas e não estoure o orçamento.</h2>
                    <p class="text-muted fs-5 mb-4">
                        Crie tetos de gastos para categorias como Mercado, Uber ou Lazer. 
                        As barras de progresso mudam de cor (Verde, Amarelo, Vermelho) conforme você gasta.
                    </p>
                    <ul class="list-unstyled d-flex flex-column gap-3">
                        <li class="d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3 fs-5"></i> Alertas visuais de limite</li>
                        <li class="d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3 fs-5"></i> Controle por categoria</li>
                        <li class="d-flex align-items-center"><i class="bi bi-check-circle-fill text-primary me-3 fs-5"></i> Planejamento mensal</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="feature-row">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 order-2 order-lg-1">
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold mb-3">BI Financeiro</span>
                    <h2 class="fw-bold display-6 mb-3">Relatórios que explicam seu dinheiro.</h2>
                    <p class="text-muted fs-5 mb-4">
                        Entenda para onde vai cada centavo com gráficos de pizza e evolução. Descubra seus gargalos financeiros.
                    </p>
                    <p class="text-muted">
                        Nosso indicador de <strong>Comprometimento de Renda</strong> avisa se você está vivendo um padrão de vida acima do sustentável.
                    </p>
                </div>
                <div class="col-lg-6 offset-lg-1 order-1 order-lg-2 mb-4 mb-lg-0">
                    <div class="feature-img-box text-center">
                        <div class="ui-chart-circle mb-4">
                            <div class="ui-chart-inner">
                                <div>
                                    <small class="d-block text-muted text-uppercase" style="font-size:0.6rem">Renda Presa</small>
                                    60%
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center gap-4">
                            <div class="text-start">
                                <span class="badge bg-primary rounded-circle p-1 me-1"> </span>
                                <small class="fw-bold">Essenciais</small>
                            </div>
                            <div class="text-start">
                                <span class="badge bg-danger rounded-circle p-1 me-1" style="background:#f72585"> </span>
                                <small class="fw-bold">Lazer</small>
                            </div>
                            <div class="text-start">
                                <span class="badge bg-secondary rounded-circle p-1 me-1"> </span>
                                <small class="fw-bold">Investimentos</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light" id="depoimentos">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Quem usa, recomenda 💬</h2>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="text-warning mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                        <p class="text-muted fst-italic">"O Edfinance foi o único que conseguiu mostrar exatamente meu limite futuro. As metas mensais me ajudaram a economizar R$ 500 no primeiro mês."</p>
                        <h6 class="fw-bold mt-4 mb-0">Rafael Ferreira</h6>
                        <small class="text-muted">Desenvolvedor</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="text-warning mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
                        <p class="text-muted fst-italic">"A função de assinaturas é incrível. O gráfico de comprometimento de renda abriu meus olhos sobre meus gastos fixos."</p>
                        <h6 class="fw-bold mt-4 mb-0">Camila Martins</h6>
                        <small class="text-muted">Designer</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="text-warning mb-3"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i></div>
                        <p class="text-muted fst-italic">"Simples, bonito e direto ao ponto. Funciona perfeitamente no meu celular. O suporte no WhatsApp é muito rápido."</p>
                        <h6 class="fw-bold mt-4 mb-0">Lucas Silva</h6>
                        <small class="text-muted">Empresário</small>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5" id="planos">
        <div class="container py-5">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Invista na sua tranquilidade.</h2>
                <p class="text-muted">Cancele quando quiser, sem burocracia.</p>
            </div>

            <div class="row justify-content-center align-items-center g-4">
                <div class="col-lg-4">
                    <div class="pricing-card">
                        <h5 class="fw-bold text-muted mb-4">MENSAL</h5>
                        <h2 class="display-4 fw-bold mb-0">R$ 29,90</h2>
                        <span class="text-muted small">cobrado todo mês</span>
                        
                        <ul class="list-unstyled text-start my-5 d-flex flex-column gap-3 opacity-75">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Acesso completo</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Metas e Orçamentos</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i> Cartões Ilimitados</li>
                        </ul>
                        <a href="#" class="btn btn-outline-custom w-100 py-3">Assinar Mensal</a>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="pricing-card featured">
                        <div class="badge-popular">POPULAR</div>
                        <h5 class="fw-bold text-primary mb-4">ANUAL</h5>
                        <h2 class="display-4 fw-bold mb-0">R$ 19,90</h2>
                        <span class="text-muted small">cobrado anualmente (R$ 238,80)</span>
                        
                        <ul class="list-unstyled text-start my-5 d-flex flex-column gap-3">
                            <li class="fw-bold text-dark"><i class="bi bi-star-fill text-warning me-2"></i> 2 MESES GRÁTIS</li>
                            <li><i class="bi bi-check-circle-fill text-primary me-2"></i> Tudo do plano mensal</li>
                            <li><i class="bi bi-check-circle-fill text-primary me-2"></i> Suporte Prioritário</li>
                            <li><i class="bi bi-check-circle-fill text-primary me-2"></i> Acesso a novas funções</li>
                        </ul>
                        <a href="#" class="btn btn-cta w-100 py-3 shadow-lg">QUERO ECONOMIZAR</a>
                        <p class="small text-muted mt-3 mb-0">Economia de R$ 120,00/ano</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white pt-5 pb-4 mt-5">
        <div class="container">
            <div class="row g-4 justify-content-between">
                <div class="col-md-4">
                    <h4 class="fw-bold mb-3">Edfinance</h4>
                    <p class="text-white-50 small">
                        O sistema financeiro inteligente para quem quer paz e previsibilidade.
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <h6 class="fw-bold mb-3">Contato</h6>
                    <div class="d-flex justify-content-md-end gap-3 mb-3">
                        <a href="https://instagram.com/erwisedev" class="text-white fs-5"><i class="bi bi-instagram"></i></a>
                        <a href="https://wa.me/5511934008521" class="text-white fs-5"><i class="bi bi-whatsapp"></i></a>
                        <a href="https://erwise.com.br" class="text-white fs-5"><i class="bi bi-globe"></i></a>
                    </div>
                    <p class="text-white-50 small mb-0">Suporte: 11 93400-8521</p>
                </div>
            </div>
            <div class="border-top border-secondary border-opacity-25 mt-4 pt-4 text-center">
                <p class="text-white-50 small mb-0">&copy; 2026 Edfinance. Desenvolvido por erwise.com.br</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>