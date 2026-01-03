<?php
// app/pages/auditoria_faturas.php

// Proteção básica
if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];

// --- LÓGICA DE CORREÇÃO (SÓ RODA SE CLICAR NO BOTÃO) ---
$msg_sucesso = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'corrigir_tudo') {
    try {
        $corrigidos = 0;
        $lista_correcoes = json_decode($_POST['lista_correcoes'], true);

        if (is_array($lista_correcoes)) {
            $pdo->beginTransaction();
            
            $stmtUpd = $pdo->prepare("UPDATE contas SET competenciafatura = ? WHERE contasid = ? AND usuarioid = ?");
            
            foreach ($lista_correcoes as $item) {
                $stmtUpd->execute([$item['correto'], $item['id'], $uid]);
                $corrigidos++;
            }
            
            $pdo->commit();
            $msg_sucesso = "$corrigidos lançamentos foram corrigidos com sucesso!";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "<div class='alert alert-danger'>Erro ao corrigir: " . $e->getMessage() . "</div>";
    }
}

// --- BUSCAR DADOS PARA ANÁLISE ---
// Busca todas as despesas vinculadas a cartões
$sql = "SELECT 
            c.contasid, c.contadescricao, c.contavencimento, c.contavalor, c.competenciafatura,
            car.cartonome, car.cartofechamento, car.cartovencimento
        FROM contas c
        JOIN cartoes car ON c.cartoid = car.cartoid
        WHERE c.usuarioid = ? AND c.cartoid IS NOT NULL AND c.contatipo = 'Saída'
        ORDER BY c.contavencimento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$uid]);
$lancamentos = $stmt->fetchAll();

$divergencias = [];

foreach ($lancamentos as $l) {
    // 1. Dados Originais
    $data_compra = new DateTime($l['contavencimento']);
    $dia_compra  = (int)$data_compra->format('d');
    
    $dia_fechamento = (int)$l['cartofechamento'];
    $dia_vencimento = (int)$l['cartovencimento'];
    
    $fatura_atual_db = $l['competenciafatura']; // Ex: 2026-02

    // 2. Calcular Fatura Correta (Com a NOVA Lógica)
    
    // Clonamos a data e setamos dia 1 para evitar problemas de virada de mês (ex: 31 jan + 1 mes)
    $data_calc = clone $data_compra;
    $data_calc->setDate($data_calc->format('Y'), $data_calc->format('m'), 1);

    $meses_adicionar = 0;

    // REGRA 1: Compra feita no dia ou após o fechamento?
    if ($dia_compra >= $dia_fechamento) {
        $meses_adicionar++;
    }

    // REGRA 2: Cartão com vencimento cruzado (Vence dia 05, Fecha dia 25)?
    // Se o vencimento é menor que o fechamento, o cartão sempre paga no mês seguinte ao fiscal
    if ($dia_vencimento < $dia_fechamento) {
        $meses_adicionar++;
    }

    if ($meses_adicionar > 0) {
        $data_calc->modify("+$meses_adicionar months");
    }

    $fatura_correta_calc = $data_calc->format('Y-m');

    // 3. Comparar
    if ($fatura_atual_db !== $fatura_correta_calc) {
        $divergencias[] = [
            'id' => $l['contasid'],
            'descricao' => $l['contadescricao'],
            'data_compra' => $l['contavencimento'],
            'valor' => $l['contavalor'],
            'cartao' => $l['cartonome'],
            'regra' => "F:$dia_fechamento / V:$dia_vencimento",
            'atual' => $fatura_atual_db,
            'correto' => $fatura_correta_calc
        ];
    }
}
?>

<div class="container py-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-0"><i class="bi bi-search me-2"></i>Auditoria de Faturas</h3>
            <p class="text-muted">Verifica se as faturas foram gravadas no mês correto.</p>
        </div>
        <a href="index.php?pg=dashboard" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <?php if($msg_sucesso): ?>
        <div class="alert alert-success shadow-sm rounded-4 mb-4">
            <i class="bi bi-check-circle-fill me-2"></i> <?= $msg_sucesso ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            
            <?php if(empty($divergencias)): ?>
                <div class="text-center py-5 text-success">
                    <i class="bi bi-shield-check display-1"></i>
                    <h4 class="fw-bold mt-3">Tudo Certo!</h4>
                    <p class="text-muted">Todos os seus lançamentos estão nas faturas corretas.</p>
                </div>
            <?php else: ?>
                
                <div class="alert alert-warning border-0 d-flex align-items-center mb-4">
                    <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                    <div>
                        <h6 class="fw-bold m-0">Atenção! Encontramos <?= count($divergencias) ?> lançamentos incorretos.</h6>
                        <small>Isso acontece devido à mudança na regra de cartões que viram o mês.</small>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Data Compra</th>
                                <th>Descrição</th>
                                <th>Cartão (Fecha/Vence)</th>
                                <th class="text-danger">Fatura Gravada</th>
                                <th class="text-success">Deveria Ser</th>
                                <th class="text-end">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($divergencias as $d): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($d['data_compra'])) ?></td>
                                    <td class="fw-bold"><?= $d['descricao'] ?></td>
                                    <td class="small text-muted"><?= $d['cartao'] ?> (<?= $d['regra'] ?>)</td>
                                    <td><span class="badge bg-danger bg-opacity-10 text-danger"><?= date('m/Y', strtotime($d['atual'].'-01')) ?></span></td>
                                    <td><span class="badge bg-success bg-opacity-10 text-success"><?= date('m/Y', strtotime($d['correto'].'-01')) ?></span></td>
                                    <td class="text-end fw-bold">R$ <?= number_format($d['valor'], 2, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 p-3 bg-light rounded-3 text-end">
                    <form method="POST">
                        <input type="hidden" name="acao" value="corrigir_tudo">
                        <textarea name="lista_correcoes" style="display:none;"><?= json_encode($divergencias) ?></textarea>
                        
                        <span class="text-muted small me-3">Confira a lista acima antes de confirmar.</span>
                        <button type="submit" class="btn btn-primary fw-bold px-4 py-2 rounded-pill">
                            <i class="bi bi-tools me-2"></i> Corrigir Todos Automaticamente
                        </button>
                    </form>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>