<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
if (!isset($_SESSION['usuarioid'])) {
    // Ajuste o redirecionamento conforme sua estrutura de pastas
    // Se estiver usando index.php?pg=login, prefira manter o padrão
    header("Location: index.php?pg=login"); 
    exit;
}

// Configuração de Data
$formatter = new IntlDateFormatter(
    'pt_BR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    'America/Sao_Paulo',          
    IntlDateFormatter::GREGORIAN,
    "dd 'de' MMMM"
);
$data_extenso = ucfirst($formatter->format(new DateTime()));

// Tratamento seguro do nome (Previne XSS)
$nome_completo = $_SESSION['usuarionome'] ?? 'Usuário';
$primeiro_nome = explode(' ', $nome_completo)[0];
$nome_seguro = htmlspecialchars($primeiro_nome, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ED Pro - Finanças</title>
    
    <meta name="theme-color" content="#ffffff">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    
    <style>
        :root {
            --primary-color: #4361ee;
            --bg-body: #f8fafc;
        }

        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-body);
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Estilizado */
        .main-header {
            background: #fff;
            padding: 15px 0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .brand-link {
            text-decoration: none;
            display: block;
        }

        .brand-logo {
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 1px;
            color: var(--primary-color);
            text-transform: uppercase;
            margin-bottom: 2px;
            display: block;
        }

        .welcome-text {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0;
            line-height: 1.2;
        }

        .date-text {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
        }

        .action-btn {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s;
            border: 1px solid transparent;
        }

        .btn-menu {
            background: #f1f5f9;
            color: #475569;
            margin-right: 8px;
        }
        .btn-menu:hover { background: #e2e8f0; color: #1e293b; }

        .btn-logout {
            background: #fef2f2;
            color: #ef4444;
            border-color: #fee2e2;
        }
        .btn-logout:hover {
            background: #fee2e2;
            color: #dc2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(239, 68, 68, 0.1);
        }

        /* Ajuste para telas pequenas */
        @media (max-width: 576px) {
            .welcome-text { font-size: 1rem; }
            .main-header { padding: 12px 0; }
        }
    </style>
</head>
<body>

<header class="main-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            
            <a href="index.php?pg=dashboard" class="brand-link">
                <span class="brand-logo">ED PRO Financeiro</span>
                <div class="d-flex flex-column">
                    <h1 class="welcome-text">
                        Olá, <?= $nome_seguro ?>! 👋
                    </h1>
                    <span class="date-text"><?= $data_extenso ?></span>
                </div>
            </a>
            
            <div class="d-flex align-items-center">
                <a href="index.php?pg=cadastros" class="action-btn btn-menu" title="Menu Geral">
                    <i class="bi bi-grid-fill"></i>
                </a>

                <a href="logout.php" class="action-btn btn-logout" title="Sair do sistema">
                    <i class="bi bi-box-arrow-right fs-5"></i>
                </a>
            </div>

        </div>
    </div>
</header>

<main class="container mb-5">