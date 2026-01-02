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
        $stmt = $pdo->prepare("UPDATE assinaturas SET ativo = NOT ativo WHERE assinaturaid = ? AND usuarioid = ?");
        $stmt->execute([$id, $uid]);
        $_SESSION['mensagem_flash'] = "Status alterado!";
        $_SESSION['tipo_flash'] = "primary";
    }
    
    header("Location: index.php?pg=cadastro_assinatura");
    exit;
}

// --- ROTA DE SALVAR/EDITAR (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction(); // Inicia transação para segurança

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
        $cartoid = !empty($_POST['cartoid']) ? $_POST['cartoid'] : null;

        if (!empty($id)) {
            // --- EDITAR ---
            $sql = "UPDATE assinaturas SET categoriaid=?, cartoid=?, titulo=?, valor=?, dia_cobranca=? WHERE assinaturaid=? AND usuarioid=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$categoria, $cartoid, $titulo, $valor, $dia, $id, $uid]);
            $_SESSION['mensagem_flash'] = "Assinatura atualizada!";
        
        } else {
            // --- NOVO CADASTRO ---
            $sql = "INSERT INTO assinaturas (usuarioid, categoriaid, cartoid, titulo, valor, dia_cobranca, ativo) VALUES (?, ?, ?, ?, ?, ?, 1)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid, $categoria, $cartoid, $titulo, $valor, $dia]);
            
            // Pega o ID da assinatura criada
            $new_ass_id = $pdo->lastInsertId();

            // =======================================================
            // LÓGICA DE GERAÇÃO IMEDIATA (O "Cron" Instantâneo)
            // =======================================================
            
            // Define data de vencimento para o MÊS ATUAL
            $hoje = new DateTime();
            $dia_venc = str_pad($dia, 2, '0', STR_PAD_LEFT);
            $data_vencimento = $hoje->format('Y-m') . "-" . $dia_venc; // Ex: 2026-01-15
            
            // Cálculos de Cartão (igual à engine de contas)
            $competencia_fatura = null;
            $contacompetencia = $hoje->format('Y-m');

            if ($cartoid) {
                $stmt_c = $pdo->prepare("SELECT cartofechamento FROM cartoes WHERE cartoid = ?");
                $stmt_c->execute([$cartoid]);
                $dia_fechamento = (int)$stmt_c->fetchColumn();

                $data_obj = new DateTime($data_vencimento);
                $dia_compra = (int)$data_obj->format('d');
                
                // Se a data de cobrança for depois do fechamento, joga para o próximo mês
                if ($dia_compra >= $dia_fechamento) {
                    $data_obj->modify('first day of next month');
                }
                $competencia_fatura = $data_obj->format('Y-m');
            }

            // Insere na tabela de contas (Lançamento Financeiro Real)
            $sql_conta = "INSERT INTO contas (
                usuarioid, categoriaid, contadescricao, contavalor, 
                contavencimento, contacompetencia, competenciafatura, 
                contatipo, contafixa, cartoid, contasituacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Saída', 0, ?, 'Pendente')";
            
            $stmtConta = $pdo->prepare($sql_conta);
            $stmtConta->execute([
                $uid, 
                $categoria, 
                $titulo . " (Assinatura)", 
                $valor,
                $data_vencimento,
                $contacompetencia,
                $competencia_fatura,
                $cartoid
            ]);

            // Atualiza a assinatura para marcar que JÁ RODOU este mês
            // Assim o cron_assinaturas.php não vai duplicar quando você logar de novo
            $sql_upd = "UPDATE assinaturas SET ultima_geracao = ? WHERE assinaturaid = ?";
            $stmtUpd = $pdo->prepare($sql_upd);
            $stmtUpd->execute([date('Y-m-d'), $new_ass_id]);

            $_SESSION['mensagem_flash'] = "Nova assinatura cadastrada e lançada no mês atual!";
        }

        $pdo->commit();
        $_SESSION['tipo_flash'] = "success";

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensagem_flash'] = "Erro: " . $e->getMessage();
        $_SESSION['tipo_flash'] = "danger";
    }

    header("Location: index.php?pg=cadastro_assinatura");
    exit;
}
?>