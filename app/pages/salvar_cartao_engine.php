<?php
// public/index.php - O CONTROLADOR CENTRAL DO SISTEMA

// 1. Configurações de Sessão e Segurança
// ---------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    // Configurações recomendadas para evitar perda de sessão
    ini_set('session.cookie_lifetime', 86400); // 24 horas
    ini_set('session.gc_maxlifetime', 86400);
    ini_set('session.cookie_httponly', 1); // Protege contra roubo de cookie via JS
    session_start();
}

// 2. Definição de Caminhos Absolutos
// ---------------------------------------------------------
// Define a raiz do projeto e a pasta app para evitar erros de 'file not found'
define('ROOT_PATH', realpath(__DIR__ . '/../')); 
define('APP_PATH', ROOT_PATH . '/app');

// 3. Carregamento de Dependências
// ---------------------------------------------------------
// Configurações gerais (URL base, timezone, etc)
if (file_exists(APP_PATH . '/config/config.php')) {
    require_once APP_PATH . '/config/config.php';
}

// Conexão com o Banco de Dados
if (file_exists(APP_PATH . '/config/database.php')) {
    require_once APP_PATH . '/config/database.php';
} else {
    // Mata a execução se não tiver banco, para não expor erros de PHP na tela
    die("<div style='font-family:sans-serif; padding:20px; color:#ef4444;'><strong>Erro Crítico:</strong> Arquivo de conexão com banco de dados não encontrado em <em>app/config/database.php</em>.</div>");
}

// 4. Roteamento e Definição de Listas de Acesso
// ---------------------------------------------------------
$pg = isset($_GET['pg']) ? $_GET['pg'] : 'dashboard';

// SEGURANÇA: basename() impede que alguém tente acessar pastas do sistema (ex: ../../etc/passwd)
$pg = basename($pg); 

// LISTA A: Páginas Públicas (Não exigem login)
$paginas_publicas = [
    'login', 
    'cadastro', 
    'recuperar_senha'
];

// LISTA B: Páginas Sem Layout Padrão
// Estas páginas carregam SOZINHAS (sem header.php/footer.php)
// Inclui: Telas Cheias (Onboarding), Scripts de Processamento (Engines) e AJAX
$paginas_sem_layout = [
    'onboarding', 
    'logout', 
    'ajax_analise',           // Retorna JSON, não pode ter HTML em volta
    'salvar_cartao_engine',   // Redireciona, não pode ter HTML antes
    'salvar_conta_engine',
    'salvar_categoria_engine',
    'salvar_transacao_engine' // Caso você crie depois
];

// 5. Controle de Acesso (Login)
// ---------------------------------------------------------

// Se NÃO está logado E a página NÃO é pública -> Manda para o Login
if (!isset($_SESSION['usuarioid']) && !in_array($pg, $paginas_publicas)) {
    header("Location: index.php?pg=login");
    exit;
}

// Se JÁ ESTÁ logado E tenta acessar Login/Cadastro -> Manda para o Dashboard
if (isset($_SESSION['usuarioid']) && in_array($pg, ['login', 'cadastro'])) {
    header("Location: index.php?pg=dashboard");
    exit;
}

// 6. Renderização da Página
// ---------------------------------------------------------
$arquivo_pagina = APP_PATH . "/pages/{$pg}.php";

if (file_exists($arquivo_pagina)) {
    
    // CENÁRIO 1: Carregamento "Limpo" (Sem Header/Footer)
    // Usado para Login, Onboarding, Engines e Ajax
    if (in_array($pg, $paginas_publicas) || in_array($pg, $paginas_sem_layout)) {
        require_once $arquivo_pagina;
    } 
    
    // CENÁRIO 2: Carregamento Padrão (Com Menu e Rodapé)
    // Usado para Dashboard, Faturas, Relatórios, etc.
    else {
        require_once APP_PATH . '/includes/header.php';
        
        // Dica: Você pode colocar uma div container aqui se quiser um padding global
        require_once $arquivo_pagina;
        
        require_once APP_PATH . '/includes/footer.php';
    }

} else {
    // 7. Página 404 (Arquivo não existe)
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Página não encontrada</title>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;700&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Plus Jakarta Sans', sans-serif; display: flex; height: 100vh; align-items: center; justify-content: center; background: #f8fafc; color: #334155; margin: 0; text-align: center; }
            h1 { font-size: 4rem; margin: 0; color: #cbd5e1; }
            p { margin-bottom: 20px; font-weight: 500; }
            a { text-decoration: none; background: #4361ee; color: white; padding: 10px 20px; border-radius: 8px; font-weight: bold; transition: 0.2s; }
            a:hover { background: #3a56d4; }
        </style>
    </head>
    <body>
        <div>
            <h1>404</h1>
            <p>Ops! A página <strong><?= htmlspecialchars($pg) ?></strong> não foi encontrada.</p>
            <a href="index.php">Voltar ao Início</a>
        </div>
    </body>
    </html>
    <?php
}
?>