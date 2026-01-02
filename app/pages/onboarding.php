<?php
// app/pages/onboarding.php

// Ajuste do caminho do banco de dados (usando __DIR__ para segurança)
require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não estiver logado, manda pro login
if (!isset($_SESSION['usuarioid'])) {
    header("Location: index.php?pg=login");
    exit;
}

// Pega o primeiro nome para personalizar (com segurança XSS)
$nome_completo = $_SESSION['usuarionome'] ?? 'Usuário';
$primeiro_nome = htmlspecialchars(explode(' ', $nome_completo)[0]);

// LÓGICA DE FINALIZAÇÃO
if (isset($_GET['concluir']) && $_GET['concluir'] == '1') {
    $uid = $_SESSION['usuarioid'];
    
    // 1. Marca que não é mais primeiro acesso
    $stmt = $pdo->prepare("UPDATE usuarios SET usuario_primeiro_acesso = 0 WHERE usuarioid = ?");
    $stmt->execute([$uid]);
    
    // 2. Redireciona para o MENU GERAL (Cadastros) como você pediu
    // Usando a rota correta do index.php
    header("Location: index.php?pg=cadastros");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo ao ED Pro</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

    <style>
        :root {
            --primary: #4361ee;
            --dark: #1e293b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            /* Fundo sutil */
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 20px 20px;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0;
        }

        .onboarding-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 20px;
            perspective: 1000px;
        }

        .onboarding-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.6);
            padding: 40px 30px;
            height: 620px; /* Altura fixa para estabilidade */
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s;
        }

        /* Animação dos Slides */
        .slide {
            display: none;
            flex-direction: column;
            height: 100%;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .slide.active { display: flex; }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Área Visual (Ícone e Bolas) */
        .visual-area {
            flex: 1.5;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .icon-box {
            width: 110px; height: 110px;
            border-radius: 30px;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem;
            position: relative;
            z-index: 2;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            transform: rotate(-5deg);
            transition: 0.5s;
        }
        
        .bg-circle {
            position: absolute; border-radius: 50%;
            opacity: 0.15; z-index: 1;
            width: 220px; height: 220px;
            background: currentColor;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            filter: blur(40px);
        }

        /* Texto */
        .text-area { flex: 1; text-align: center; padding-top: 20px; }
        
        h2 { 
            font-weight: 800; color: var(--dark); margin-bottom: 12px; letter-spacing: -0.5px; font-size: 1.6rem;
        }
        .highlight { color: var(--primary); }
        
        p { color: #64748b; line-height: 1.5; font-size: 0.95rem; margin-bottom: 0; padding: 0 10px; }

        /* Controles */
        .controls-area { margin-top: auto; }

        .progress-bar-container {
            width: 100%; height: 6px; background: #e2e8f0; border-radius: 10px;
            margin-bottom: 25px; overflow: hidden;
        }
        .progress-fill {
            height: 100%; background: var(--primary); width: 20%;
            border-radius: 10px; transition: width 0.4s ease;
        }

        .btn-next {
            background: var(--dark); color: white; border: none;
            padding: 16px; border-radius: 16px; font-weight: 700; width: 100%;
            font-size: 1rem; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-next:hover { background: var(--primary); transform: translateY(-2px); box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3); }

        .btn-skip {
            background: transparent; border: none; width: 100%;
            color: #94a3b8; font-weight: 600; font-size: 0.85rem;
            margin-top: 15px; cursor: pointer; transition: 0.2s;
        }
        .btn-skip:hover { color: var(--dark); }

        /* Variações de Cores por Slide */
        .slide-1 .icon-box { background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white; }
        .slide-1 .bg-circle { color: #4361ee; }

        .slide-2 .icon-box { background: linear-gradient(135deg, #10b981, #059669); color: white; transform: rotate(5deg); }
        .slide-2 .bg-circle { color: #10b981; }

        .slide-3 .icon-box { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; transform: rotate(-3deg); }
        .slide-3 .bg-circle { color: #f59e0b; }

        .slide-4 .icon-box { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; transform: rotate(3deg); }
        .slide-4 .bg-circle { color: #8b5cf6; }

        .slide-5 .icon-box { background: linear-gradient(135deg, #0f172a, #334155); color: white; transform: rotate(0deg) scale(1.1); }
        .slide-5 .bg-circle { color: #0f172a; }

        @media(max-height: 700px) {
            .onboarding-card { height: 90vh; max-height: 600px; padding: 25px; }
            .visual-area { flex: 1; }
            .icon-box { width: 90px; height: 90px; font-size: 2.5rem; }
        }
    </style>
</head>
<body>

<div class="onboarding-wrapper">
    <div class="onboarding-card">
        
        <div class="slide slide-1 active" id="slide-1">
            <div class="visual-area">
                <div class="bg-circle"></div>
                <div class="icon-box"><i class="bi bi-emoji-smile"></i></div>
            </div>
            <div class="text-area">
                <h2>Olá, <span class="highlight"><?= $primeiro_nome ?></span>! 👋</h2>
                <p>Seja bem-vindo ao <strong>ED Pro</strong>. Estamos muito felizes em te ajudar a conquistar sua liberdade financeira.</p>
            </div>
        </div>

        <div class="slide slide-2" id="slide-2">
            <div class="visual-area">
                <div class="bg-circle"></div>
                <div class="icon-box"><i class="bi bi-wallet-fill"></i></div>
            </div>
            <div class="text-area">
                <h2>Centralize Tudo</h2>
                <p>Chega de abrir vários apps. Cadastre seus bancos e dinheiro físico aqui para ver seu saldo real consolidado.</p>
            </div>
        </div>

        <div class="slide slide-3" id="slide-3">
            <div class="visual-area">
                <div class="bg-circle"></div>
                <div class="icon-box"><i class="bi bi-credit-card-2-front-fill"></i></div>
            </div>
            <div class="text-area">
                <h2>Domine os Cartões</h2>
                <p>Controle limites, datas de fechamento e faturas. Saiba quanto do seu crédito já está comprometido.</p>
            </div>
        </div>

        <div class="slide slide-4" id="slide-4">
            <div class="visual-area">
                <div class="bg-circle"></div>
                <div class="icon-box"><i class="bi bi-pie-chart-fill"></i></div>
            </div>
            <div class="text-area">
                <h2>Relatórios Inteligentes</h2>
                <p>Categorize seus gastos (Mercado, Lazer, Assinaturas) e visualize graficamente para onde seu dinheiro vai.</p>
            </div>
        </div>

        <div class="slide slide-5" id="slide-5">
            <div class="visual-area">
                <div class="bg-circle"></div>
                <div class="icon-box"><i class="bi bi-shield-check"></i></div>
            </div>
            <div class="text-area">
                <h2>Tudo Seguro</h2>
                <p>Seus dados são privados. Vamos começar configurando suas categorias e contas?</p>
            </div>
        </div>

        <div class="controls-area">
            <div class="progress-bar-container">
                <div class="progress-fill" id="progressBar"></div>
            </div>

            <button class="btn-next" onclick="nextSlide()" id="btnAction">
                Continuar <i class="bi bi-arrow-right"></i>
            </button>
            <button class="btn-skip" onclick="finishTour()" id="btnSkip">
                Pular introdução
            </button>
        </div>

    </div>
</div>

<script>
    let currentSlide = 1;
    const totalSlides = 5;

    function nextSlide() {
        if (currentSlide < totalSlides) {
            // Remove classe do atual
            document.getElementById(`slide-${currentSlide}`).classList.remove('active');
            
            // Avança
            currentSlide++;
            
            // Adiciona classe no novo
            document.getElementById(`slide-${currentSlide}`).classList.add('active');

            // Atualiza Barra
            updateProgress();

            // Se for o último slide
            if (currentSlide === totalSlides) {
                const btn = document.getElementById('btnAction');
                btn.innerHTML = 'Começar Agora <i class="bi bi-rocket-takeoff-fill"></i>';
                btn.style.background = "#10b981"; // Verde sucesso
                
                // Esconde botão pular
                const skipBtn = document.getElementById('btnSkip');
                skipBtn.style.opacity = '0';
                skipBtn.style.pointerEvents = 'none';
                
                // Dispara confetes!
                fireConfetti();
            }
        } else {
            // Finaliza se clicar no botão verde
            finishTour();
        }
    }

    function updateProgress() {
        const percent = (currentSlide / totalSlides) * 100;
        document.getElementById('progressBar').style.width = `${percent}%`;
    }

    // Redireciona passando o parâmetro GET que o PHP lá em cima vai ler
    function finishTour() {
        // Recarrega a página com ?concluir=1
        window.location.href = '?concluir=1';
    }

    // Efeito de Confetes
    function fireConfetti() {
        try {
            var duration = 3 * 1000;
            var animationEnd = Date.now() + duration;
            var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 9999 };

            function randomInRange(min, max) { return Math.random() * (max - min) + min; }

            var interval = setInterval(function() {
                var timeLeft = animationEnd - Date.now();

                if (timeLeft <= 0) { return clearInterval(interval); }

                var particleCount = 50 * (timeLeft / duration);
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
                confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
            }, 250);
        } catch(e) {
            console.log("Confetti library not loaded");
        }
    }
</script>

</body>
</html>