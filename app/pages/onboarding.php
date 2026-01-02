<?php
require_once "../config/database.php";
session_start();

if (!isset($_SESSION['usuarioid'])) {
    header("Location: login.php");
    exit;
}

// Pega o primeiro nome para personalizar
$nome_completo = $_SESSION['usuarionome'] ?? 'Usuário';
$primeiro_nome = explode(' ', $nome_completo)[0];

// Lógica para finalizar
if (isset($_GET['concluir']) && $_GET['concluir'] == '1') {
    $uid = $_SESSION['usuarioid'];
    $stmt = $pdo->prepare("UPDATE usuarios SET usuario_primeiro_acesso = 0 WHERE usuarioid = ?");
    $stmt->execute([$uid]);
    
    header("Location: cadastros.php");
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
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
            /* Fundo sutil com padrão */
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 20px 20px;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .onboarding-wrapper {
            width: 100%;
            max-width: 500px;
            perspective: 1000px;
        }

        .onboarding-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px); /* Efeito de vidro */
            border-radius: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 40px;
            height: 650px; /* Altura fixa para não pular */
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

        /* Área da Imagem/Ícone */
        .visual-area {
            flex: 1.5;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .icon-box {
            width: 120px; height: 120px;
            border-radius: 35px;
            display: flex; align-items: center; justify-content: center;
            font-size: 3.5rem;
            position: relative;
            z-index: 2;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transform: rotate(-5deg);
            transition: 0.5s;
        }
        
        /* Círculos de Fundo Animados */
        .bg-circle {
            position: absolute; border-radius: 50%;
            opacity: 0.1; z-index: 1;
        }
        .c1 { width: 250px; height: 250px; background: currentColor; top: 50%; left: 50%; transform: translate(-50%, -50%); filter: blur(40px); }

        /* Textos */
        .text-area { flex: 1; text-align: center; padding-top: 20px; }
        
        h2 { 
            font-weight: 800; color: #1e293b; margin-bottom: 15px; letter-spacing: -0.5px; 
            font-size: 1.75rem;
        }
        .highlight { color: #4361ee; }
        
        p { color: #64748b; line-height: 1.6; font-size: 1rem; margin-bottom: 0; padding: 0 10px; }

        /* Controles (Bolinhas e Botões) */
        .controls-area {
            margin-top: auto;
        }

        .progress-bar-container {
            width: 100%; height: 4px; background: #e2e8f0; border-radius: 4px;
            margin-bottom: 30px; overflow: hidden;
        }
        .progress-fill {
            height: 100%; background: #4361ee; width: 20%; /* Inicial */
            border-radius: 4px; transition: width 0.4s ease;
        }

        .btn-next {
            background: #1e293b; color: white; border: none;
            padding: 18px; border-radius: 18px; font-weight: 700; width: 100%;
            font-size: 1rem; cursor: pointer; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-next:hover { background: #4361ee; transform: translateY(-2px); box-shadow: 0 10px 25px rgba(67, 97, 238, 0.3); }

        .btn-skip {
            background: transparent; border: none; width: 100%;
            color: #94a3b8; font-weight: 600; font-size: 0.9rem;
            margin-top: 15px; cursor: pointer; transition: 0.2s;
        }
        .btn-skip:hover { color: #1e293b; }

        /* Estilos Específicos por Slide */
        .slide-1 .icon-box { background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white; }
        .slide-1 .c1 { color: #4361ee; }

        .slide-2 .icon-box { background: linear-gradient(135deg, #10b981, #059669); color: white; transform: rotate(5deg); }
        .slide-2 .c1 { color: #10b981; }

        .slide-3 .icon-box { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; transform: rotate(-3deg); }
        .slide-3 .c1 { color: #f59e0b; }

        .slide-4 .icon-box { background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; transform: rotate(3deg); }
        .slide-4 .c1 { color: #8b5cf6; }

        .slide-5 .icon-box { background: linear-gradient(135deg, #0f172a, #334155); color: white; transform: rotate(0deg) scale(1.1); }
        .slide-5 .c1 { color: #0f172a; }

    </style>
</head>
<body>

<div class="onboarding-wrapper">
    <div class="onboarding-card">
        
        <div class="slide slide-1 active" id="slide-1">
            <div class="visual-area">
                <div class="bg-circle c1"></div>
                <div class="icon-box"><i class="bi bi-emoji-smile"></i></div>
            </div>
            <div class="text-area">
                <h2>Olá, <span class="highlight"><?= $primeiro_nome ?></span>! 👋</h2>
                <p>Seja bem-vindo ao <strong>ED Pro</strong>. Estamos muito felizes em te ajudar a conquistar sua liberdade financeira.</p>
            </div>
        </div>

        <div class="slide slide-2" id="slide-2">
            <div class="visual-area">
                <div class="bg-circle c1"></div>
                <div class="icon-box"><i class="bi bi-wallet-fill"></i></div>
            </div>
            <div class="text-area">
                <h2>Centralize Tudo</h2>
                <p>Chega de abrir 5 apps de banco. Cadastre suas contas correntes e dinheiro físico aqui para ver seu saldo real.</p>
            </div>
        </div>

        <div class="slide slide-3" id="slide-3">
            <div class="visual-area">
                <div class="bg-circle c1"></div>
                <div class="icon-box"><i class="bi bi-credit-card-2-front-fill"></i></div>
            </div>
            <div class="text-area">
                <h2>Domine os Cartões</h2>
                <p>Controle seus limites e faturas. Saiba exatamente quanto você já comprometeu do seu crédito.</p>
            </div>
        </div>

        <div class="slide slide-4" id="slide-4">
            <div class="visual-area">
                <div class="bg-circle c1"></div>
                <div class="icon-box"><i class="bi bi-pie-chart-fill"></i></div>
            </div>
            <div class="text-area">
                <h2>Relatórios Inteligentes</h2>
                <p>Categorize seus gastos (ex: Mercado, Lazer) e veja gráficos automáticos de para onde seu dinheiro vai.</p>
            </div>
        </div>

        <div class="slide slide-5" id="slide-5">
            <div class="visual-area">
                <div class="bg-circle c1"></div>
                <div class="icon-box"><i class="bi bi-shield-check"></i></div>
            </div>
            <div class="text-area">
                <h2>Tudo Seguro</h2>
                <p>Seus dados são criptografados e visíveis apenas por você. Vamos começar a configurar seu perfil?</p>
            </div>
        </div>

        <div class="controls-area">
            <div class="progress-bar-container">
                <div class="progress-fill" id="progressBar"></div>
            </div>

            <button class="btn-next" onclick="nextSlide()" id="btnAction">
                Continuar <i class="bi bi-arrow-right"></i>
            </button>
            <button class="btn-skip" onclick="skipTour()" id="btnSkip">
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
            // Avança slide
            document.getElementById(`slide-${currentSlide}`).classList.remove('active');
            currentSlide++;
            document.getElementById(`slide-${currentSlide}`).classList.add('active');

            // Atualiza Barra
            updateProgress();

            // Se for o último slide
            if (currentSlide === totalSlides) {
                const btn = document.getElementById('btnAction');
                btn.innerHTML = 'Começar Agora <i class="bi bi-rocket-takeoff-fill"></i>';
                btn.style.background = "#10b981"; // Verde sucesso
                
                // Esconde botão pular
                document.getElementById('btnSkip').style.opacity = '0';
                document.getElementById('btnSkip').style.pointerEvents = 'none';
                
                // Dispara confetes!
                fireConfetti();
            }
        } else {
            // Finaliza
            finishTour();
        }
    }

    function updateProgress() {
        const percent = (currentSlide / totalSlides) * 100;
        document.getElementById('progressBar').style.width = `${percent}%`;
    }

    function skipTour() {
        // Vai direto para o final (banco de dados)
        finishTour();
    }

    function finishTour() {
        window.location.href = '?concluir=1';
    }

    // Efeito de Confetes (Chuva de papel)
    function fireConfetti() {
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
    }
</script>

</body>
</html>