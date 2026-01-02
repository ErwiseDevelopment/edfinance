<?php
// public/index.php - O PORTEIRO DO SISTEMA

session_start();

// 1. Define caminhos absolutos (Segurança e Praticidade)
define('ROOT_PATH', dirname(__DIR__)); // Volta para 'financeiro_sistema/'
define('APP_PATH', ROOT_PATH . '/app');

// 2. Carrega configurações e banco de dados
// Certifique-se que o database.php está em 'app/config/database.php'
if (file_exists(APP_PATH . '/config/database.php')) {
    require_once APP_PATH . '/config/database.php';
} else {
    die("Erro Crítico: Arquivo de banco de dados não encontrado em app/config/database.php");
}

// 3. Verifica Login (Proteção Global)
// Se não tiver logado e não estiver tentando logar/cadastrar, manda pro login
$pagina = isset($_GET['pg']) ? $_GET['pg'] : 'dashboard';

// Páginas que não precisam de login
$paginas_publicas = ['login', 'cadastro', 'login_engine', 'register_engine'];

if (!isset($_SESSION['usuarioid']) && !in_array($pagina, $paginas_publicas)) {
    // Redireciona para login se tentar acessar dashboard sem sessão
    header("Location: index.php?pg=login");
    exit;
}

// 4. Carrega o Cabeçalho (Header)
// O header só deve aparecer se o usuário estiver logado e não for uma página 'limpa' (como login)
if (isset($_SESSION['usuarioid']) && !in_array($pagina, $paginas_publicas)) {
    require_once APP_PATH . '/includes/header.php';
}

// 5. Roteamento (Carrega o Miolo da página)
$arquivo_pagina = APP_PATH . "/pages/{$pagina}.php";

if (file_exists($arquivo_pagina)) {
    require_once $arquivo_pagina;
} else {
    echo "<div class='container py-5 text-center'><h3>Erro 404</h3><p>Página não encontrada.</p><a href='index.php' class='btn btn-primary'>Voltar ao Início</a></div>";
}

// 6. Carrega o Rodapé (Footer)
if (isset($_SESSION['usuarioid']) && !in_array($pagina, $paginas_publicas)) {
    require_once APP_PATH . '/includes/footer.php';
}
?>