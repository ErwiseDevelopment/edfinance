<?php
// public/index.php - O PORTEIRO DO SISTEMA (Front Controller)

// 1. Configurações Iniciais e Segurança de Sessão
// ---------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // Define tempo de vida do cookie (opcional, ex: 1 dia) e segurança
    ini_set('session.cookie_lifetime', 86400);
    ini_set('session.cookie_httponly', 1); // Impede acesso via JS
    session_start();
}

// 2. Definição de Caminhos Absolutos (Evita erros de 'require')
// ---------------------------------------------------------
// Se este arquivo está em /public, o ROOT é um nível acima
define('ROOT_PATH', realpath(__DIR__ . '/../')); 
define('APP_PATH', ROOT_PATH . '/app');

// 3. Carregamento de Configurações e Banco de Dados
// ---------------------------------------------------------
// Carrega configurações gerais (timezone, erros, etc)
if (file_exists(APP_PATH . '/config/config.php')) {
    require_once APP_PATH . '/config/config.php';
}

// Carrega conexão com banco
if (file_exists(APP_PATH . '/config/database.php')) {
    require_once APP_PATH . '/config/database.php';
} else {
    die("<div style='padding:20px;font-family:sans-serif;'>Erro Crítico: Configuração de banco de dados não encontrada.</div>");
}

// 4. Roteamento e Sanitização (Segurança Anti-LFI)
// ---------------------------------------------------------
$pg = isset($_GET['pg']) ? $_GET['pg'] : 'dashboard';

// SEGURANÇA CRÍTICA: basename() impede que o usuário navegue entre pastas 
// (ex: ?pg=../../config/database)
$pg = basename($pg); 

// Lista de páginas que NÃO exigem login (Públicas)
$paginas_publicas = ['login', 'recuperar_senha'];

// Lista de páginas que EXIGEM login, mas NÃO devem carregar o Header/Footer padrão
// (São páginas com design próprio, tela cheia)
$paginas_full_screen = ['onboarding', 'logout'];

// 5. Controle de Acesso (ACL)
// ---------------------------------------------------------
if (!isset($_SESSION['usuarioid']) && !in_array($pg, $paginas_publicas)) {
    // Se não está logado e tenta acessar página privada -> Manda pro Login
    header("Location: index.php?pg=login");
    exit;
}

// Se já está logado e tenta acessar o login -> Manda pro Dashboard
if (isset($_SESSION['usuarioid']) && $pg === 'login') {
    header("Location: index.php?pg=dashboard");
    exit;
}

// 6. Renderização da Página
// ---------------------------------------------------------
$arquivo_pagina = APP_PATH . "/pages/{$pg}.php";

if (file_exists($arquivo_pagina)) {
    
    // CASO 1: Página Pública (Login) OU Página Privada Full Screen (Onboarding)
    // Carrega apenas o arquivo, sem o layout padrão
    if (in_array($pg, $paginas_publicas) || in_array($pg, $paginas_full_screen)) {
        require_once $arquivo_pagina;
    } 
    // CASO 2: Página Privada Padrão (Dashboard, Faturas, etc)
    // Carrega com Header e Footer
    else {
        require_once APP_PATH . '/includes/header.php';
        
        // Wrapper para o conteúdo principal (opcional, ajuda no layout)
        // echo '<div class="main-content">'; 
        require_once $arquivo_pagina;
        // echo '</div>';
        
        require_once APP_PATH . '/includes/footer.php';
    }

} else {
    // CASO 3: Página não encontrada (404)
    // Pode criar um arquivo app/pages/404.php para ficar mais bonito
    http_response_code(404);
    echo "<div style='display:flex; height:100vh; align-items:center; justify-content:center; flex-direction:column; font-family:sans-serif; background:#f8fafc;'>";
    echo "<h1 style='font-size:3rem; color:#cbd5e1; margin-bottom:10px;'>404</h1>";
    echo "<p style='color:#64748b;'>Página não encontrada.</p>";
    echo "<a href='index.php' style='text-decoration:none; color:#4361ee; font-weight:bold;'>Voltar ao Início</a>";
    echo "</div>";
}
?>