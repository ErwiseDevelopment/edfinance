<?php
// app/pages/ajax_rapido_categoria.php

// 1. Limpeza de Buffer (Essencial para não quebrar o JSON)
if (ob_get_length()) ob_clean(); 

header('Content-Type: application/json');
ini_set('display_errors', 0); 

// 2. Conexão com Banco 
if (!isset($pdo)) {
    $db_file = __DIR__ . "/../config/database.php";
    if (file_exists($db_file)) {
        require_once $db_file;
        if (session_status() === PHP_SESSION_NONE) session_start();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro: Banco de dados não encontrado.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $uid = $_SESSION['usuarioid'] ?? null;
    
    if (!$uid) {
        echo json_encode(['status' => 'error', 'message' => 'Sessão expirada.']);
        exit;
    }

    // Limpa espaços extras e padroniza para evitar "Mercado " vs "Mercado"
    $descricao = trim($_POST['categoriadescricao'] ?? '');
    $tipo_raw = $_POST['categoriatipo'] ?? 'Saída'; 

    if (empty($descricao)) {
        echo json_encode(['status' => 'error', 'message' => 'O nome da categoria é obrigatório.']);
        exit;
    }

    // Tradução (Formulário envia Entrada/Saída -> Banco espera Receita/Despesa)
    $tipo_db = ($tipo_raw == 'Entrada') ? 'Receita' : 'Despesa';

    try {
        // --- 1. VERIFICAÇÃO DE DUPLICIDADE (BLOQUEIO TOTAL) ---
        // Verifica se já existe esse nome exato para esse tipo e usuário
        $check = $pdo->prepare("
            SELECT categoriaid 
            FROM categorias 
            WHERE usuarioid = ? 
            AND categoriadescricao = ? 
            AND categoriatipo = ?
        ");
        $check->execute([$uid, $descricao, $tipo_db]);

        if ($check->rowCount() > 0) {
            // MUDANÇA AQUI: Retorna ERRO se já existir
            echo json_encode([
                'status' => 'error', 
                'message' => 'Já existe uma categoria "' . $descricao . '" cadastrada como ' . $tipo_raw . '.'
            ]);
            exit;
        }

        // --- 2. INSERÇÃO (SE NÃO EXISTIR) ---
        $stmt = $pdo->prepare("INSERT INTO categorias (usuarioid, categoriadescricao, categoriatipo) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$uid, $descricao, $tipo_db])) {
            echo json_encode([
                'status' => 'success', 
                'id' => $pdo->lastInsertId(), 
                'nome' => $descricao
            ]);
        } else {
            throw new Exception("Não foi possível salvar no banco.");
        }

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Erro: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método inválido.']);
}
exit;
?>