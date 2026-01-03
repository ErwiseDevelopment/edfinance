<?php
// app/pages/faturas.php

if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];
$mes_filtro = $_GET['mes'] ?? date('Y-m'); 
$cartao_selecionado = $_GET['cartoid'] ?? null;

// Lógica de Edição (Mantida)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_lancamento') {
    try {
        $id_edit = $_POST['id_conta'];
        $stmtCheck = $pdo->prepare("SELECT contasituacao FROM contas WHERE contasid = ? AND usuarioid = ?");
        $stmtCheck->execute([$id_edit, $uid]);
        if ($stmtCheck->fetchColumn() === 'Pago') throw new Exception("Item pago não pode ser editado.");

        $desc_edit = $_POST['descricao'];
        $val_edit = str_replace(['.', ','], ['', '.'], $_POST['valor']);
        $data_edit = $_POST['data'];
        $cat_edit = $_POST['categoria'];
        
        $pdo->prepare("UPDATE contas SET contadescricao=?, contavalor=?, contavencimento=?, categoriaid=? WHERE contasid=?")
            ->execute([$desc_edit, $val_edit, $data_edit, $cat_edit, $id_edit]);

        echo "<script>window.location.href='index.php?pg=faturas&cartoid=$cartao_selecionado&mes=$mes_filtro';</script>";
        exit;
    } catch (Exception $e) { $erro_msg = $e->getMessage(); }
}

// Datas
$data_atual = new DateTime($mes_filtro . "-01");
$mes_anterior = (clone $data_atual)->modify('-1 month')->format('Y-m');
$mes_proximo = (clone $data_atual)->modify('+1 month')->format('Y-m');

// Cartões e Categorias
$meus_cartoes = $pdo->prepare("SELECT * FROM cartoes WHERE usuarioid = ? ORDER BY cartonome ASC");
$meus_cartoes->execute([$uid]);
$meus_cartoes = $meus_cartoes->fetchAll();
if (!$cartao_selecionado && !empty($meus_cartoes)) $cartao_selecionado = $meus_cartoes[0]['cartoid'];

$lista_categorias = $pdo->prepare("SELECT * FROM categorias WHERE usuarioid = ? ORDER BY categoriadescricao");
$lista_categorias->execute([$uid]);
$lista_categorias = $lista_categorias->fetchAll();

// --- CÁLCULOS VISUAIS ---
$itens_fatura = [];
$total_gastos_bruto = 0; 
$total_ja_quitado = 0;   
$total_pendente_real = 0; 
$creditos_troco = 0;
$limite_cartao = 0; 
$dados_cartao = null;
$limite_comprometido = 0;

if ($cartao_selecionado) {
    foreach($meus_cartoes as $m) if($m['cartoid'] == $cartao_selecionado) { $dados_cartao = $m; $limite_cartao = $m['cartolimite']; }

    $stmt_f = $pdo->prepare("SELECT c.*, cat.categoriadescricao FROM contas c LEFT JOIN categorias cat ON c.categoriaid = cat.categoriaid WHERE c.usuarioid = ? AND c.cartoid = ? AND COALESCE(c.competenciafatura, c.contacompetencia) = ? ORDER BY c.contavencimento ASC");
    $stmt_f->execute([$uid, $cartao_selecionado, $mes_filtro]);
    $itens_fatura = $stmt_f->fetchAll();

    foreach($itens_fatura as $i) { 
        if ($i['contatipo'] == 'PagamentoFatura') {
            $creditos_troco += $i['contavalor'];
            $total_ja_quitado += $i['contavalor'];
        } else {
            $total_gastos_bruto += $i['contavalor'];
            if ($i['contasituacao'] == 'Pago') {
                $total_ja_quitado += $i['contavalor'];
            } else {
                $total_pendente_real += $i['contavalor'];
            }
        }
    }
    
    $stmt_l = $pdo->prepare("SELECT SUM(contavalor) FROM contas WHERE usuarioid=? AND cartoid=? AND contasituacao='Pendente' AND contatipo='Saída'");
    $stmt_l->execute([$uid, $cartao_selecionado]);
    $limite_comprometido = $stmt_l->fetchColumn() ?: 0;
}

// Saldo que o usuário tem que pagar AGORA
$saldo_devedor_visual = $total_pendente_real - $creditos_troco;
if ($saldo_devedor_visual < 0) $saldo_devedor_visual = 0;

$limite_disponivel = $limite_cartao - $limite_comprometido;
$perc_uso = ($limite_cartao > 0) ? ($limite_comprometido / $limite_cartao) * 100 : 0;
$cor_cartao = $dados_cartao['cartocor'] ?? '#1e293b';
?>

<style>
    body { background-color: #f8fafc; color: #334155; }
    .card-fatura { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .card-fatura-header { color: #fff; padding: 25px; position: relative; }
    .card-fatura-header::before { content: ''; position: absolute; top: -50%; right: -10%; width: 200px; height: 200px; background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%); pointer-events: none; }
    .chip-cartao { padding: 8px 18px; border-radius: 50px; border: 1px solid #e2e8f0; text-decoration: none; color: #64748b; background: #fff; display: flex; align-items: center; gap: 8px; transition: 0.2s; font-size: 0.85rem; }
    .chip-cartao.active { background: #1e293b; color: #fff; border-color: #1e293b; }
    .dot { width: 10px; height: 10px; border-radius: 50%; }
    .btn-action { border: none; background: #f1f5f9; width: 34px; height: 34px; border-radius: 8px; color: #64748b; display: flex; align-items: center; justify-content: center; transition: 0.2s; margin-left: 5px; }
    .btn-action:hover { background: #e2e8f0; color: #1e293b; }
    .tipo-pagamento { background-color: #f0fdf4 !important; border-left: 4px solid #16a34a !important; }
    .tipo-pagamento .valor { color: #16a34a !important; }
</style>

<div class="container py-4 mb-5">
    <?php if(isset($erro_msg)): ?><div class="alert alert-danger rounded-4 border-0 mb-4"><?= $erro_msg ?></div><?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold m-0">Faturas</h4>
        <div class="bg-white border rounded-pill px-3 py-2 shadow-sm d-flex align-items-center gap-3">
            <a href="?pg=faturas&cartoid=<?= $cartao_selecionado ?>&mes=<?= $mes_anterior ?>" class="text-dark"><i class="bi bi-chevron-left"></i></a>
            <span class="small fw-bold text-uppercase"><?= (new IntlDateFormatter('pt_BR', 0, 0, null, null, "MMM yyyy"))->format($data_atual) ?></span>
            <a href="?pg=faturas&cartoid=<?= $cartao_selecionado ?>&mes=<?= $mes_proximo ?>" class="text-dark"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <div class="d-flex overflow-auto gap-2 mb-4 pb-2">
        <?php foreach($meus_cartoes as $ct): $c = $ct['cartocor'] ?? '#ccc'; ?>
            <a href="?pg=faturas&cartoid=<?= $ct['cartoid'] ?>&mes=<?= $mes_filtro ?>" class="chip-cartao <?= $cartao_selecionado == $ct['cartoid'] ? 'active' : '' ?>">
                <span class="dot" style="background: <?= $c ?>;"></span> <?= $ct['cartonome'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if($cartao_selecionado): ?>
        <div class="card-fatura mb-4">
            <div class="card-fatura-header" style="background: <?= $cor_cartao ?>;">
                <div class="d-flex justify-content-between mb-3">
                    <span class="fw-bold fs-5"><?= $dados_cartao['cartonome'] ?></span>
                    <span class="badge <?= $saldo_devedor_visual <= 0.01 ? 'bg-success' : 'bg-white text-dark' ?> rounded-pill">
                        <?= $saldo_devedor_visual <= 0.01 ? 'PAGA' : 'ABERTA' ?>
                    </span>
                </div>
                <div class="row mt-3">
                    <div class="col-6">
                        <small class="opacity-75 d-block">Total Gastos</small>
                        <span class="fw-bold">R$ <?= number_format($total_gastos_bruto, 2, ',', '.') ?></span>
                    </div>
                    <div class="col-6 text-end">
                        <small class="opacity-75 d-block">Total Quitado</small>
                        <span class="fw-bold text-warning">R$ <?= number_format($total_ja_quitado, 2, ',', '.') ?></span>
                    </div>
                </div>
                <hr class="opacity-25">
                <small class="opacity-75 d-block">Restante a Pagar</small>
                <h1 class="fw-bold display-5">R$ <?= number_format($saldo_devedor_visual, 2, ',', '.') ?></h1>
                
                <?php if($saldo_devedor_visual > 0.01): ?>
                    <button class="btn btn-light w-100 rounded-pill fw-bold mt-3" data-bs-toggle="modal" data-bs-target="#modalPagarFatura" style="color: <?= $cor_cartao ?>">
                        <i class="bi bi-wallet2 me-2"></i> PAGAR AGORA
                    </button>
                <?php else: ?>
                    <div class="bg-success bg-opacity-25 p-2 rounded-3 text-center fw-bold small mt-3 text-white border border-white border-opacity-25">Fatura Quitada</div>
                <?php endif; ?>
            </div>
            <div class="p-3">
                <div class="d-flex justify-content-between small fw-bold text-muted mb-1">
                    <span>USADO: R$ <?= number_format($limite_comprometido, 2, ',', '.') ?></span>
                    <span class="text-success">LIVRE: R$ <?= number_format($limite_disponivel, 2, ',', '.') ?></span>
                </div>
                <div class="progress" style="height: 6px;"><div class="progress-bar" style="width: <?= $perc_uso ?>%; background: <?= $cor_cartao ?>"></div></div>
            </div>
        </div>

        <h6 class="fw-bold text-muted px-1 mb-3">Extrato</h6>
        <?php foreach($itens_fatura as $it): 
            $is_pg = ($it['contatipo'] == 'PagamentoFatura');
            $pago = ($it['contasituacao'] == 'Pago');
            $bg = $is_pg ? 'tipo-pagamento' : 'bg-white';
            $cor_val = $is_pg ? 'valor' : 'text-dark';
            $icon = $is_pg ? 'bi-arrow-down-left-circle-fill text-success' : ($pago ? 'bi-check-circle-fill text-muted' : 'bi-bag');
        ?>
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-2 d-flex flex-row justify-content-between align-items-center <?= $bg ?>">
                <div class="d-flex align-items-center overflow-hidden">
                    <div class="bg-light p-2 rounded-3 me-3 flex-shrink-0"><i class="bi <?= $icon ?>"></i></div>
                    <div class="text-truncate">
                        <span class="fw-bold d-block small text-truncate <?= ($pago && !$is_pg) ? 'text-decoration-line-through opacity-50' : '' ?>"><?= $it['contadescricao'] ?></span>
                        <small class="text-muted" style="font-size: 0.65rem;"><?= date('d/m', strtotime($it['contavencimento'])) ?> • <?= $is_pg ? 'Crédito' : $it['categoriadescricao'] ?></small>
                    </div>
                </div>
                <div class="d-flex align-items-center text-end ms-2">
                    <span class="fw-bold small d-block me-3 <?= $cor_val ?>"><?= $is_pg ? '-' : '' ?> R$ <?= number_format($it['contavalor'], 2, ',', '.') ?></span>
                    <?php if($pago): ?>
                        <i class="bi bi-lock-fill text-muted opacity-25 px-2"></i>
                    <?php elseif(!$is_pg): ?>
                        <button class="btn-action text-primary" onclick='abrirEditar(<?= json_encode($it) ?>)'><i class="bi bi-pencil"></i></button>
                        <button class="btn-action text-danger" onclick="confirmarExclusao(<?= $it['contasid'] ?>)"><i class="bi bi-trash"></i></button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalPagarFatura" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="index.php?pg=processar_pagamento_fatura" method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Pagar Fatura</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="cartao_id" value="<?= $cartao_selecionado ?>"><input type="hidden" name="competencia" value="<?= $mes_filtro ?>">
                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between mb-1"><small>Pendente Bruto:</small><strong>R$ <?= number_format($total_pendente_real, 2, ',', '.') ?></strong></div>
                    <div class="d-flex justify-content-between text-success"><small>Créditos Disp.:</small><strong>- R$ <?= number_format($creditos_troco, 2, ',', '.') ?></strong></div>
                    <hr class="my-1"><div class="d-flex justify-content-between"><small class="fw-bold">Total a Pagar:</small><strong class="fw-bold">R$ <?= number_format($saldo_devedor_visual, 2, ',', '.') ?></strong></div>
                </div>
                <label class="fw-bold small">Valor (R$)</label>
                <input type="text" name="valor_pago" id="inputValorPagar" class="form-control fw-bold fs-4 text-success money" value="<?= number_format($saldo_devedor_visual, 2, ',', '.') ?>" required>
                <div id="avisoMaximo" class="text-danger small fw-bold mt-1 d-none">Valor não pode exceder o saldo devedor.</div>
                <label class="fw-bold small mt-3">Data</label><input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="modal-footer border-0 pt-0"><button type="submit" class="btn btn-dark w-100 rounded-3 fw-bold">Confirmar</button></div>
        </form>
    </div>
</div>

<script>
// Variável com o valor máximo permitido (Saldo Visual)
const maxValorPermitido = <?= $saldo_devedor_visual ?>;

// Funções de Modal e Máscara
function abrirEditar(item) { /* ... mesma lógica ... */ }
function confirmarExclusao(id) { if(confirm("Excluir?")) window.location.href="index.php?pg=acoes_conta&id="+id+"&acao=excluir&origem=faturas"; }

const inputs = document.querySelectorAll('.money');
inputs.forEach(input => {
    input.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, "");
        v = (v/100).toFixed(2) + ""; v = v.replace(".", ","); v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,"); v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
        e.target.value = v;

        if (input.id === 'inputValorPagar') {
            let val = parseFloat(v.replace('.','').replace(',','.'));
            let aviso = document.getElementById('avisoMaximo');
            if (val > maxValorPermitido) {
                aviso.classList.remove('d-none');
                e.target.value = maxValorPermitido.toLocaleString('pt-BR', {minimumFractionDigits: 2});
            } else { aviso.classList.add('d-none'); }
        }
    });
});
</script>