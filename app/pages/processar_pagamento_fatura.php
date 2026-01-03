<?php
// app/pages/processar_pagamento_fatura.php

require_once __DIR__ . "/../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_SESSION['usuarioid'];
    $cartao_id = $_POST['cartao_id'];
    $competencia = $_POST['competencia'];
    $data_pagto = $_POST['data_pagamento'];
    
    // Tratamento do valor informado
    $valor_input = $_POST['valor_pago'];
    $valor_input = str_replace(['.', 'R$', ' '], '', $valor_input); 
    $valor_input = str_replace(',', '.', $valor_input);
    $novo_pagamento = (float)$valor_input;

    if ($novo_pagamento <= 0) {
        echo "<script>window.location.href='index.php?pg=faturas&cartoid=$cartao_id&mes=$competencia&erro=valor_invalido';</script>";
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. BUSCAR E SOMAR CRÉDITOS JÁ EXISTENTES (Trocos anteriores)
        // Precisamos somar isso ao pagamento novo para ter força de pagar contas maiores
        $sqlCreditos = "SELECT contasid, contavalor FROM contas 
                        WHERE usuarioid = ? AND cartoid = ? 
                        AND competenciafatura = ? 
                        AND contatipo = 'PagamentoFatura'";
        $stmtC = $pdo->prepare($sqlCreditos);
        $stmtC->execute([$uid, $cartao_id, $competencia]);
        $creditos_antigos = $stmtC->fetchAll();

        $saldo_creditos = 0;
        foreach($creditos_antigos as $c) {
            $saldo_creditos += $c['contavalor'];
            // Vamos deletar os créditos antigos para recriar apenas o saldo final (limpeza)
            $pdo->prepare("DELETE FROM contas WHERE contasid = ?")->execute([$c['contasid']]);
        }

        // 2. DEFINIR O "PODER DE FOGO" TOTAL (Novo Pagamento + O que já tinha de crédito)
        $valor_disponivel = $novo_pagamento + $saldo_creditos;

        // 3. BUSCAR DESPESAS PENDENTES
        $sql = "SELECT * FROM contas 
                WHERE usuarioid = ? 
                AND cartoid = ? 
                AND COALESCE(competenciafatura, contacompetencia) = ? 
                AND contasituacao = 'Pendente'
                AND contatipo != 'PagamentoFatura'
                ORDER BY contavencimento ASC, contasid ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$uid, $cartao_id, $competencia]);
        $lancamentos = $stmt->fetchAll();

        // 4. SEGURANÇA: Verificar se o valor não excede a dívida total
        // Se pagou a mais, ajustamos para quitar tudo e zerar (evita crédito negativo ou excessivo)
        $total_divida = 0;
        foreach($lancamentos as $l) { $total_divida += $l['contavalor']; }

        if ($valor_disponivel > $total_divida) {
            $valor_disponivel = $total_divida; // Teto máximo é a dívida
        }

        // 5. LOOP DE BAIXA (CASCATA)
        foreach ($lancamentos as $conta) {
            if ($valor_disponivel <= 0.001) break; 

            $valor_item = (float)$conta['contavalor'];

            // Se tem dinheiro suficiente para pagar ESTE item inteiro
            // (Considerando margem de erro de float 0.01)
            if ($valor_disponivel >= ($valor_item - 0.01)) {
                
                $upd = $pdo->prepare("UPDATE contas SET contasituacao = 'Pago' WHERE contasid = ?");
                $upd->execute([$conta['contasid']]);
                
                $valor_disponivel -= $valor_item;
            }
        }

        // 6. SE SOBROU TROCO, CRIA UM ÚNICO CRÉDITO NOVO
        if ($valor_disponivel > 0.01) {
            
            $stmtCat = $pdo->prepare("SELECT categoriaid FROM categorias WHERE usuarioid = ? LIMIT 1");
            $stmtCat->execute([$uid]);
            $cat_id = $stmtCat->fetchColumn();

            $ins = $pdo->prepare("INSERT INTO contas (
                usuarioid, cartoid, categoriaid, contadescricao, 
                contavalor, contavencimento, contacompetencia, competenciafatura, 
                contatipo, contasituacao, contaparcela_num, contaparcela_total
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PagamentoFatura', 'Pago', 1, 1)");

            $ins->execute([
                $uid, 
                $cartao_id, 
                $cat_id, 
                "Crédito/Abatimento", 
                $valor_disponivel, 
                $data_pagto, 
                $competencia, 
                $competencia
            ]);
        }
        
        $pdo->commit();
        echo "<script>window.location.href='index.php?pg=faturas&cartoid=$cartao_id&mes=$competencia&msg=pagamento_processado';</script>";
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erro ao processar: " . $e->getMessage());
    }
}
?>