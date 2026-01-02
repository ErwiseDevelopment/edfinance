<?php
// app/pages/acoes_cartao.php

if (!defined('APP_PATH')) exit;

$id = $_GET['id'] ?? null;
$acao = $_GET['acao'] ?? null;
$uid = $_SESSION['usuarioid'];

if ($acao === 'excluir' && $id) {
    try {
        // Primeiro, verifica se tem contas vinculadas (opcional: ou deleta em cascata)
        // Aqui vou apenas deletar o cartão. Se o banco tiver FK RESTRICT, vai dar erro.
        // Se tiver FK SET NULL ou CASCADE, funciona.
        
        $stmt = $pdo->prepare("DELETE FROM cartoes WHERE cartoid = ? AND usuarioid = ?");
        $stmt->execute([$id, $uid]);
        
        $_SESSION['mensagem_flash'] = "Cartão excluído!";
        $_SESSION['tipo_flash'] = "danger";
        
    } catch (Exception $e) {
        $_SESSION['mensagem_flash'] = "Não foi possível excluir. Verifique se há faturas vinculadas.";
        $_SESSION['tipo_flash'] = "danger";
    }
}

header("Location: index.php?pg=cadastro_cartao");
exit;
?>