<?php
// app/pages/fluxo_caixa_engine.php

if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];

// --- LÓGICA DE EXCLUSÃO (GET) ---
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $mes_retorno = $_GET['mes'] ?? date('Y-m');

    $sql = $pdo->prepare("DELETE FROM contas WHERE contasid = ? AND usuarioid = ?");
    if ($sql->execute([$id, $uid])) {
        $_SESSION['mensagem_flash'] = "Lançamento excluído!";
        $_SESSION['tipo_flash'] = "danger";
    }
    
    // Redireciona
    header("Location: index.php?pg=fluxo_caixa&mes=$mes_retorno");
    exit;
}

// --- LÓGICA DE ATUALIZAÇÃO (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $id = $_POST['id'];
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $vencimento = $_POST['vencimento'];
    $cartoid = !empty($_POST['cartoid']) ? $_POST['cartoid'] : null;
    $contafixa = isset($_POST['contafixa']) ? 1 : 0;
    
    // Recupera o mês atual para voltar para a mesma tela
    $mes_retorno = $_POST['mes_atual'] ?? date('Y-m');

    // Lógica de Competência (Fatura vs Normal)
    $data_obj = new DateTime($vencimento);
    $competencia_normal = $data_obj->format('Y-m');
    $competencia_fatura = null;

    if ($cartoid) {
        $stmt_c = $pdo->prepare("SELECT cartofechamento FROM cartoes WHERE cartoid = ?");
        $stmt_c->execute([$cartoid]);
        $fch = (int)$stmt_c->fetchColumn();
        $dia_compra = (int)$data_obj->format('d');
        
        $data_fatura = clone $data_obj;
        if ($dia_compra >= $fch) { 
            $data_fatura->modify('first day of next month'); 
        }
        $competencia_fatura = $data_fatura->format('Y-m');
    }

    $sql = $pdo->prepare("UPDATE contas SET contadescricao = ?, contavalor = ?, contavencimento = ?, contacompetencia = ?, competenciafatura = ?, cartoid = ?, contafixa = ? WHERE contasid = ? AND usuarioid = ?");
    
    if ($sql->execute([$descricao, $valor, $vencimento, $competencia_normal, $competencia_fatura, $cartoid, $contafixa, $id, $uid])) {
        $_SESSION['mensagem_flash'] = "Lançamento atualizado com sucesso!";
        $_SESSION['tipo_flash'] = "success";
    } else {
        $_SESSION['mensagem_flash'] = "Erro ao atualizar lançamento.";
        $_SESSION['tipo_flash'] = "danger";
    }

    header("Location: index.php?pg=fluxo_caixa&mes=$mes_retorno");
    exit;
}
?>