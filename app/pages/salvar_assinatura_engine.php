<?php
// app/pages/salvar_assinatura_engine.php

// 1. Limpeza de Buffer
if (ob_get_length()) ob_clean();

// 2. Proteção
if (!defined('APP_PATH')) exit;
if (!isset($_SESSION['usuarioid'])) exit;

$uid = $_SESSION['usuarioid'];

// --- ROTA DE EXCLUSÃO / ALTERAR STATUS (GET) ---
if (isset($_GET['acao'])) {
    $id = $_GET['id'];
    
    if ($_GET['acao'] == 'excluir') {
        $stmt = $pdo->prepare("DELETE FROM assinaturas WHERE assinaturaid = ? AND usuarioid = ?");
        $stmt->execute([$id, $uid]);
        $_SESSION['mensagem_flash'] = "Assinatura removida!";
        $_SESSION['tipo_flash'] = "danger";
    } 
    elseif ($_GET['acao'] == 'toggle') {
        // Inverte o status (Ativo <-> Inativo)
        $stmt = $pdo->prepare("UPDATE assinaturas SET ativo = NOT ativo WHERE assinaturaid = ? AND usuarioid = ?");
        $stmt->execute([$id, $uid]);
        $_SESSION['mensagem_flash'] = "Status da assinatura alterado!";
        $_SESSION['tipo_flash'] = "primary";
    }
    
    header("Location: index.php?pg=cadastro_assinatura");
    exit;
}

// --- ROTA DE SALVAR/EDITAR (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Tratamento de Valor
        $valor_raw = $_POST['valor'];
        $valor = 0.00;
        if (!empty($valor_raw)) {
            $temp = str_replace(['R$', ' '], '', $valor_raw);
            if (strpos($temp, ',') !== false) {
                $temp = str_replace('.', '', $temp);
                $temp = str_replace(',', '.', $temp);
            }
            $valor = (float)$temp;
        }

        $id = $_POST['id'] ?? '';
        $titulo = trim($_POST['titulo']);
        $categoria = $_POST['categoriaid'];
        $dia = (int)$_POST['dia_cobranca'];
        
        // Cartão é opcional (pode ser débito em conta)
        $cartoid = !empty($_POST['cartoid']) ? $_POST['cartoid'] : null;

        if (!empty($id)) {
            // EDITAR
            $sql = "UPDATE assinaturas SET categoriaid=?, cartoid=?, titulo=?, valor=?, dia_cobranca=? WHERE assinaturaid=? AND usuarioid=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$categoria, $cartoid, $titulo, $valor, $dia, $id, $uid]);
            $_SESSION['mensagem_flash'] = "Assinatura atualizada!";
        } else {
            // NOVO
            $sql = "INSERT INTO assinaturas (usuarioid, categoriaid, cartoid, titulo, valor, dia_cobranca, ativo) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid, $categoria, $cartoid, $titulo, $valor, $dia]);
            $_SESSION['mensagem_flash'] = "Nova assinatura cadastrada!";
        }

        $_SESSION['tipo_flash'] = "success";

    } catch (Exception $e) {
        $_SESSION['mensagem_flash'] = "Erro: " . $e->getMessage();
        $_SESSION['tipo_flash'] = "danger";
    }

    header("Location: index.php?pg=cadastro_assinatura");
    exit;
}
?>