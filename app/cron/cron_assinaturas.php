<?php
// app/cron/cron_assinaturas.php

// Se for rodar via CRON do servidor (linha de comando), defina os caminhos manualmente
// Se for rodar via include no login, os caminhos já existem
if (session_status() === PHP_SESSION_NONE) {
    // Ajuste conforme sua estrutura se for rodar externo
    require_once __DIR__ . '/../config/database.php'; 
}

$hoje = new DateTime();
$competencia_atual = $hoje->format('Y-m');

try {
    $pdo->beginTransaction();

    // 1. Busca assinaturas ativas que AINDA NÃO rodaram neste mês
    // Verifica se 'ultima_geracao' é nulo ou se é de um mês anterior ao atual
    $sql = "SELECT * FROM assinaturas 
            WHERE ativo = 1 
            AND (ultima_geracao IS NULL OR DATE_FORMAT(ultima_geracao, '%Y-%m') < ?)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$competencia_atual]);
    $assinaturas = $stmt->fetchAll();

    $gerados = 0;

    foreach ($assinaturas as $ass) {
        $uid = $ass['usuarioid'];
        
        // Define data de vencimento para este mês
        // Ex: Se hoje é Janeiro e dia_cobranca é 15 -> 2026-01-15
        $dia = str_pad($ass['dia_cobranca'], 2, '0', STR_PAD_LEFT);
        $data_vencimento = $hoje->format('Y-m') . "-" . $dia;
        
        // --- LÓGICA DE CARTÃO (Mesma da Engine) ---
        $competencia_fatura = null;
        $contacompetencia = $hoje->format('Y-m');

        if (!empty($ass['cartoid'])) {
            // Busca fechamento do cartão
            $stmt_c = $pdo->prepare("SELECT cartofechamento FROM cartoes WHERE cartoid = ?");
            $stmt_c->execute([$ass['cartoid']]);
            $dia_fechamento = (int)$stmt_c->fetchColumn();

            $data_obj = new DateTime($data_vencimento);
            $dia_compra = (int)$data_obj->format('d');
            
            // Se a cobrança é depois do fechamento, joga para fatura do mês seguinte
            if ($dia_compra >= $dia_fechamento) {
                $data_obj->modify('first day of next month');
            }
            $competencia_fatura = $data_obj->format('Y-m');
        }

        // --- INSERE NA TABELA DE CONTAS ---
        $insert = "INSERT INTO contas (
            usuarioid, categoriaid, contadescricao, contavalor, 
            contavencimento, contacompetencia, competenciafatura, 
            contatipo, contafixa, cartoid, contasituacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'Saída', 0, ?, 'Pendente')";
        
        $stmtIns = $pdo->prepare($insert);
        $stmtIns->execute([
            $uid, 
            $ass['categoriaid'], 
            $ass['titulo'] . " (Assinatura)", // Ex: Netflix (Assinatura)
            $ass['valor'],
            $data_vencimento,
            $contacompetencia,
            $competencia_fatura,
            $ass['cartoid']
        ]);

        // --- ATUALIZA A ASSINATURA PARA NÃO RODAR DE NOVO ESTE MÊS ---
        // Marcamos que a última geração foi hoje (ou na data de vencimento deste mês)
        $upd = $pdo->prepare("UPDATE assinaturas SET ultima_geracao = ? WHERE assinaturaid = ?");
        $upd->execute([date('Y-m-d'), $ass['assinaturaid']]);

        $gerados++;
    }

    $pdo->commit();
    
    // Se quiser logar
    // echo "Processo finalizado. $gerados assinaturas geradas.";

} catch (Exception $e) {
    $pdo->rollBack();
    // error_log("Erro no Cron de Assinaturas: " . $e->getMessage());
}
?>