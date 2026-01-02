<?php
// app/pages/salvar_cartao_engine.php

if (!defined('APP_PATH')) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_SESSION['usuarioid'];
    $id = $_POST['id'] ?? '';
    $nome = $_POST['nome'];
    $fechamento = $_POST['fechamento'];
    $vencimento = $_POST['vencimento'];
    $limite = !empty($_POST['limite']) ? $_POST['limite'] : 0;
    
    // Captura a cor (se não vier nada, usa cinza escuro padrão)
    $cor = !empty($_POST['cor']) ? $_POST['cor'] : '#1e293b';

    try {
        if (!empty($id)) {
            // EDITAR (Incluindo cartocor)
            $stmt = $pdo->prepare("UPDATE cartoes SET cartonome=?, cartofechamento=?, cartovencimento=?, cartolimite=?, cartocor=? WHERE cartoid=? AND usuarioid=?");
            $stmt->execute([$nome, $fechamento, $vencimento, $limite, $cor, $id, $uid]);
            $_SESSION['mensagem_flash'] = "Cartão atualizado!";
        } else {
            // CRIAR NOVO (Incluindo cartocor)
            $stmt = $pdo->prepare("INSERT INTO cartoes (usuarioid, cartonome, cartofechamento, cartovencimento, cartolimite, cartocor) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$uid, $nome, $fechamento, $vencimento, $limite, $cor]);
            $_SESSION['mensagem_flash'] = "Cartão criado com sucesso!";
        }
        
        $_SESSION['tipo_flash'] = "success";

    } catch (Exception $e) {
        $_SESSION['mensagem_flash'] = "Erro ao salvar cartão.";
        $_SESSION['tipo_flash'] = "danger";
    }

    header("Location: index.php?pg=cadastro_cartao");
    exit;
}
?>