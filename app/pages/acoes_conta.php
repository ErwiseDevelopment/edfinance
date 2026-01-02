<?php
// app/pages/acoes_conta.php

if (!defined('APP_PATH')) exit;

$id    = $_GET['id'] ?? null;
$acao  = $_GET['acao'] ?? null;
$uid   = $_SESSION['usuarioid'];

if (!$id || !$uid) {
    header("Location: index.php?pg=dashboard");
    exit;
}

// --- PAGAR ---
if ($acao === 'pagar') {
    $sql = $pdo->prepare("UPDATE contas SET contasituacao = 'Pago' WHERE contasid = ? AND usuarioid = ?");
    $sql->execute([$id, $uid]);
    $_SESSION['mensagem_flash'] = "Conta marcada como <strong>Paga</strong> com sucesso!";
    $_SESSION['tipo_flash'] = "success";
}

// --- ESTORNAR ---
if ($acao === 'estornar') {
    $sql = $pdo->prepare("UPDATE contas SET contasituacao = 'Pendente' WHERE contasid = ? AND usuarioid = ?");
    $sql->execute([$id, $uid]);
    $_SESSION['mensagem_flash'] = "Pagamento estornado! A conta está <strong>Pendente</strong> novamente.";
    $_SESSION['tipo_flash'] = "warning";
}

// --- EXCLUIR ---
if ($acao === 'excluir') {
    $stmt = $pdo->prepare("SELECT contagrupoid, contadescricao, contaparcela_total FROM contas WHERE contasid = ? AND usuarioid = ?");
    $stmt->execute([$id, $uid]);
    $conta = $stmt->fetch();

    if ($conta) {
        if ($conta['contaparcela_total'] > 1) {
            $descricao_base = preg_replace('/\s\(\d+\/\d+\)$/', '', $conta['contadescricao']);
            $sql_del = $pdo->prepare("DELETE FROM contas WHERE usuarioid = ? AND (contagrupoid = ? OR contadescricao LIKE ?)");
            $sql_del->execute([$uid, $conta['contagrupoid'] ?? 0, $descricao_base . ' (%)']);
            $_SESSION['mensagem_flash'] = "Todas as parcelas foram excluídas!";
        } else {
            $sql_del = $pdo->prepare("DELETE FROM contas WHERE contasid = ? AND usuarioid = ?");
            $sql_del->execute([$id, $uid]);
            $_SESSION['mensagem_flash'] = "Lançamento excluído com sucesso!";
        }
        $_SESSION['tipo_flash'] = "danger";
    }
}

// Retorna para a página anterior
$origem = $_SERVER['HTTP_REFERER'] ?? 'index.php?pg=dashboard';
header("Location: " . $origem);
exit;
?>