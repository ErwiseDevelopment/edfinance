<?php
// app/pages/faturas.php

// Proteção contra acesso direto
if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];
$mes_filtro = $_GET['mes'] ?? date('Y-m'); 
$cartao_selecionado = $_GET['cartoid'] ?? null;

// --- LÓGICA DE SALVAR EDIÇÃO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar_lancamento') {
    try {
        $id_edit = $_POST['id_conta'];
        
        $stmtCheck = $pdo->prepare("SELECT contasituacao FROM contas WHERE contasid = ? AND usuarioid = ?");
        $stmtCheck->execute([$id_edit, $uid]);
        $status_atual = $stmtCheck->fetchColumn();

        if ($status_atual === 'Pago') {
            throw new Exception("Bloqueado: Não é possível editar um lançamento que já foi pago.");
        }

        $desc_edit = $_POST['descricao'];
        $val_edit = str_replace(['R$', ' ', '.'], '', $_POST['valor']);
        $val_edit = str_replace(',', '.', $val_edit);
        $data_edit = $_POST['data'];
        $cat_edit = $_POST['categoria'];
        $comp_edit = date('Y-m', strtotime($data_edit));

        $stmtUpdate = $pdo->prepare("UPDATE contas SET contadescricao=?, contavalor=?, contavencimento=?, contacompetencia=?, categoriaid=? WHERE contasid=? AND usuarioid=?");
        $stmtUpdate->execute([$desc_edit, $val_edit, $data_edit, $comp_edit, $cat_edit, $id_edit, $uid]);

        echo "<script>window.location.href='index.php?pg=faturas&cartoid=$cartao_selecionado&mes=$mes_filtro';</script>";
        exit;

    } catch (Exception $e) {
        $erro_msg = $e->getMessage();
    }
}

// Navegação
$data_atual = new DateTime($mes_filtro . "-01");
$mes_anterior = (clone $data_atual)->modify('-1 month')->format('Y-m');
$mes_proximo = (clone $data_atual)->modify('+1 month')->format('Y-m');

$stmt_cards = $pdo->prepare("SELECT * FROM cartoes WHERE usuarioid = ? ORDER BY cartonome ASC");
$stmt_cards->execute([$uid]);
$meus_cartoes = $stmt_cards->fetchAll();

if (!$cartao_selecionado && !empty($meus_cartoes)) $cartao_selecionado = $meus_cartoes[0]['cartoid'];

$stmt_cats = $pdo->prepare("SELECT categoriaid, categoriadescricao FROM categorias WHERE usuarioid = ? ORDER BY categoriadescricao");
$stmt_cats->execute([$uid]);
$lista_categorias = $stmt_cats->fetchAll();

$itens_fatura = [];
$total_gastos_bruto = 0;
$total_ja_quitado = 0;
$total_pendente_real = 0; 
$creditos_troco = 0;

$limite_cartao = 0;
$dados_cartao = null;
$limite_comprometido_total = 0;

if ($cartao_selecionado) {
    foreach($meus_cartoes as $m) { 
        if($m['cartoid'] == $cartao_selecionado) { 
            $dados_cartao = $m; 
            $limite_cartao = $m['cartolimite']; 
        }
    }

    $sql_fatura = "SELECT c.*, cat.categoriadescricao 
                   FROM contas c 
                   LEFT JOIN categorias cat ON c.categoriaid = cat.categoriaid 
                   WHERE c.usuarioid = ? AND c.cartoid = ? 
                   AND COALESCE(c.competenciafatura, c.contacompetencia) = ? 
                   ORDER BY c.contavencimento ASC";
    
    $stmt_f = $pdo->prepare($sql_fatura);
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

    $stmt_limite = $pdo->prepare("SELECT SUM(contavalor) as total FROM contas WHERE usuarioid = ? AND cartoid = ? AND contasituacao = 'Pendente' AND contatipo = 'Saída'");
    $stmt_limite->execute([$uid, $cartao_selecionado]);
    $limite_comprometido_total = $stmt_limite->fetch()['total'] ?? 0;
}

// Saldo Devedor Real
$saldo_devedor_visual = $total_pendente_real - $creditos_troco;
if ($saldo_devedor_visual < 0) $saldo_devedor_visual = 0;

$limite_disponivel = $limite_cartao - $limite_comprometido_total;
$perc_uso = ($limite_cartao > 0) ? ($limite_comprometido_total / $limite_cartao) * 100 : 0;
$cor_cartao_atual = $dados_cartao['cartocor'] ?? '#1e293b';
?>

<style>
    body { background-color: #f8fafc; color: #334155; }
    
    .card-fatura { background: #fff; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    .card-fatura-header { color: #fff; padding: 25px; position: relative; overflow: hidden; }
    .card-fatura-header::before {
        content: ''; position: absolute; top: -50%; right: -10%; width: 200px; height: 200px;
        background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
        pointer-events: none;
    }

    .chip-cartao { padding: 8px 18px; border-radius: 50px; border: 1px solid #dee2e6; font-size: 0.85rem; text-decoration: none; color: #6c757d; background: #fff; white-space: nowrap; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
    .chip-cartao:hover { background: #f1f5f9; }
    .chip-cartao.active { background: #1e293b; color: #fff; border-color: #1e293b; }
    .dot-cor { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

    .mini-card-visual {
        width: 50px; height: 32px; border-radius: 6px; background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3); position: relative; backdrop-filter: blur(5px);
    }
    .mini-card-visual .chip { width: 10px; height: 8px; background: rgba(255,215,0, 0.8); border-radius: 2px; position: absolute; top: 8px; left: 6px; }
    .mini-card-visual .wifi { position: absolute; top: 6px; right: 6px; font-size: 10px; opacity: 0.7; }

    .month-nav { background: #fff; border-radius: 15px; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #eee; }
    .month-nav a { color: #212529; text-decoration: none; font-weight: bold; }
    
    .btn-view-report { background: #fff; border: 1px solid #edf2f7; border-radius: 18px; padding: 15px; text-decoration: none; color: #2d3748; display: flex; align-items: center; justify-content: center; height: 100%; transition: 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
    
    .btn-pay-invoice { background: #fff; color: #1e293b; font-weight: 800; border: none; padding: 10px 20px; border-radius: 12px; width: 100%; margin-top: 15px; transition: 0.2s; }
    
    .tipo-pagamento { background-color: #f0fdf4 !important; border-left: 4px solid #16a34a !important; }
    .tipo-pagamento .valor-texto { color: #16a34a !important; }

    .btn-action { border: none; background: #f1f5f9; width: 34px; height: 34px; border-radius: 8px; color: #64748b; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; margin-left: 5px; }
    .btn-action:hover { background: #e2e8f0; color: #1e293b; transform: translateY(-2px); }
    .btn-action.text-danger:hover { background: #fee2e2; color: #ef4444; }
</style>

<div class="container py-4 mb-5">
    
    <?php if(isset($erro_msg)): ?>
        <div class="alert alert-danger rounded-4 shadow-sm mb-4 border-0">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $erro_msg ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold m-0">Faturas</h4>
            <small class="text-muted">Gerencie seus vencimentos</small>
        </div>
        <div class="month-nav shadow-sm">
            <a href="?pg=faturas&cartoid=<?= $cartao_selecionado ?>&mes=<?= $mes_anterior ?>"><i class="bi bi-chevron-left"></i></a>
            <span class="mx-3 text-uppercase small fw-bold"><?= (new IntlDateFormatter('pt_BR', 0, 0, null, null, "MMMM yyyy"))->format($data_atual) ?></span>
            <a href="?pg=faturas&cartoid=<?= $cartao_selecionado ?>&mes=<?= $mes_proximo ?>"><i class="bi bi-chevron-right"></i></a>
        </div>
    </div>

    <div class="d-flex overflow-x-auto gap-2 mb-4 pb-2" style="scrollbar-width: none;">
        <?php foreach($meus_cartoes as $ct): 
            $cor = $ct['cartocor'] ?? '#ccc';
        ?>
            <a href="?pg=faturas&cartoid=<?= $ct['cartoid'] ?>&mes=<?= $mes_filtro ?>" class="chip-cartao <?= $cartao_selecionado == $ct['cartoid'] ? 'active' : '' ?>">
                <span class="dot-cor" style="background: <?= $cor ?>;"></span>
                <?= $ct['cartonome'] ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6">
            <a href="?pg=resumo_cartoes" class="btn-view-report">
                <i class="bi bi-pie-chart-fill me-2 text-primary"></i>
                <span class="small fw-bold">RESUMO</span>
            </a>
        </div>
        <div class="col-6">
            <a href="?pg=faturas_geral" class="btn-view-report">
                <i class="bi bi-list-check me-2 text-success"></i>
                <span class="small fw-bold">VER TODAS</span>
            </a>
        </div>
    </div>

    <?php if($cartao_selecionado): ?>
        
        <div class="card-fatura mb-4">
            <div class="card-fatura-header" style="background: <?= $cor_cartao_atual ?>;">
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center">
                        <div class="mini-card-visual me-3">
                            <div class="chip"></div>
                            <i class="bi bi-wifi text-white wifi"></i>
                        </div>
                        <div style="line-height: 1.1;">
                            <span class="d-block small opacity-75">Fatura Atual</span>
                            <span class="fw-bold"><?= $dados_cartao['cartonome'] ?></span>
                        </div>
                    </div>
                    
                    <?php if($saldo_devedor_visual <= 0.01): ?>
                        <span class="badge bg-success text-white rounded-pill shadow-sm">PAGA</span>
                    <?php else: ?>
                        <span class="badge bg-white text-dark rounded-pill shadow-sm">ABERTA</span>
                    <?php endif; ?>
                </div>

                <div class="row mt-3 mb-2">
                    <div class="col-6">
                        <small class="d-block opacity-75">Total Gastos</small>
                        <span class="fw-bold">R$ <?= number_format($total_gastos_bruto, 2, ',', '.') ?></span>
                    </div>
                    <div class="col-6 text-end">
                        <small class="d-block opacity-75 text-success">Total Quitado</small>
                        <span class="fw-bold text-success">R$ <?= number_format($total_ja_quitado, 2, ',', '.') ?></span>
                    </div>
                </div>
                <hr class="opacity-25 my-2">

                <small class="d-block opacity-75 mt-2">Restante a Pagar</small>
                <h1 class="fw-bold m-0 display-5">R$ <?= number_format($saldo_devedor_visual, 2, ',', '.') ?></h1>
                <div class="small opacity-75 mt-1">Vence dia <?= date('d/m', strtotime($dados_cartao['cartovencimento'].'-'.$mes_filtro)) ?></div>
                
                <?php if($saldo_devedor_visual > 0.01): ?>
                    <button type="button" class="btn-pay-invoice shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPagarFatura" style="color: <?= $cor_cartao_atual ?>;">
                        <i class="bi bi-wallet2 me-1"></i> PAGAR FATURA
                    </button>
                <?php else: ?>
                    <div class="mt-3 text-center p-2 bg-success bg-opacity-25 rounded-3 border border-success border-opacity-25 fw-bold text-white small">
                        <i class="bi bi-hand-thumbs-up-fill me-1"></i> FATURA QUITADA
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="p-4 bg-white">
                <div class="d-flex justify-content-between mb-1 small fw-bold text-muted">
                    <span>USADO: R$ <?= number_format($limite_comprometido_total, 2, ',', '.') ?></span>
                    <span class="text-success">LIVRE: R$ <?= number_format($limite_disponivel, 2, ',', '.') ?></span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div class="progress-bar" style="width: <?= min(100, $perc_uso) ?>%; background-color: <?= $cor_cartao_atual ?>;"></div>
                </div>
            </div>
        </div>

        <h6 class="fw-bold mb-3 px-1">Lançamentos</h6>
        <?php if(empty($itens_fatura)): ?>
            <div class="p-5 text-center text-muted bg-white rounded-4 border">Nenhum lançamento.</div>
        <?php else: foreach($itens_fatura as $it): 
            $is_pagamento = ($it['contatipo'] == 'PagamentoFatura');
            $classe = $is_pagamento ? 'tipo-pagamento' : 'bg-white';
            $icone = $is_pagamento ? 'bi-check-circle-fill text-success' : ($it['contasituacao'] == 'Pago' ? 'bi-check-circle-fill text-muted' : 'bi-cart');
            $sinal = $is_pagamento ? '-' : '';
            $cor_valor = $is_pagamento ? 'text-success' : 'text-dark';
            $esta_pago = ($it['contasituacao'] == 'Pago');
        ?>
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-2 d-flex flex-row justify-content-between align-items-center <?= $classe ?>">
                <div class="d-flex align-items-center" style="overflow: hidden;">
                    <div class="bg-light p-2 rounded-3 me-3 flex-shrink-0">
                        <i class="bi <?= $icone ?>"></i>
                    </div>
                    <div style="min-width: 0;">
                        <span class="fw-bold d-block small text-truncate <?= ($esta_pago && !$is_pagamento) ? 'text-decoration-line-through opacity-50' : '' ?>">
                            <?= $it['contadescricao'] ?>
                        </span>
                        <small class="text-muted" style="font-size: 0.65rem;">
                            <?= date('d/m', strtotime($it['contavencimento'])) ?> • 
                            <?= $is_pagamento ? 'Abatimento' : ($it['categoriadescricao'] ?? 'Geral') ?>
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center text-end ms-2">
                    <div class="me-3">
                        <span class="fw-bold small d-block <?= $cor_valor ?>">
                            <?= $sinal ?> R$ <?= number_format($it['contavalor'], 2, ',', '.') ?>
                        </span>
                        <?php if($it['contaparcela_total'] > 1): ?>
                            <small class="badge bg-light text-dark" style="font-size: 0.55rem;"><?= $it['contaparcela_num'] ?>/<?= $it['contaparcela_total'] ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if($esta_pago): ?>
                        <div class="text-muted opacity-50 px-2" title="Item pago (Bloqueado)">
                            <i class="bi bi-lock-fill fs-5"></i>
                        </div>
                    <?php else: ?>
                        <?php if(!$is_pagamento): ?>
                            <button class="btn-action text-primary" onclick='abrirEditar(<?= json_encode($it) ?>)'><i class="bi bi-pencil-square"></i></button>
                            <button onclick="confirmarExclusao(<?= $it['contasid'] ?>)" class="btn-action text-danger"><i class="bi bi-trash3"></i></button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; endif; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalPagarFatura" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="index.php?pg=processar_pagamento_fatura" method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Baixar Fatura</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">
                    Informe quanto deseja pagar.
                </p>
                <input type="hidden" name="cartao_id" value="<?= $cartao_selecionado ?>">
                <input type="hidden" name="competencia" value="<?= $mes_filtro ?>">
                
                <div class="mb-3 bg-light p-3 rounded-3 border">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Total Pendente:</small>
                        <strong>R$ <?= number_format($total_pendente_real, 2, ',', '.') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between text-success">
                        <small>Créditos Disp.:</small>
                        <strong>- R$ <?= number_format($creditos_troco, 2, ',', '.') ?></strong>
                    </div>
                    <hr class="my-1">
                    <div class="d-flex justify-content-between">
                        <small class="fw-bold">Total a Pagar:</small>
                        <strong class="fw-bold">R$ <?= number_format($saldo_devedor_visual, 2, ',', '.') ?></strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-bold small">Valor a Pagar Agora (R$)</label>
                    <input type="text" name="valor_pago" id="inputValorPagar" 
                           class="form-control fw-bold fs-4 text-success money" 
                           value="<?= number_format($saldo_devedor_visual, 2, ',', '.') ?>" required>
                    <div id="avisoMaximo" class="text-danger small fw-bold mt-1 d-none">
                        O valor não pode ser maior que o saldo devedor.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold small">Data do Pagamento</label>
                    <input type="date" name="data_pagamento" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="submit" class="btn btn-dark fw-bold w-100 rounded-3">Confirmar Pagamento</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Editar Lançamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="acao" value="editar_lancamento">
                <input type="hidden" name="id_conta" id="edit_id">
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Descrição</label>
                    <input type="text" name="descricao" id="edit_desc" class="form-control" required>
                </div>
                <div class="row g-2">
                    <div class="col-6 mb-3">
                        <label class="small fw-bold text-muted">Valor (R$)</label>
                        <input type="text" name="valor" id="edit_valor" class="form-control" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="small fw-bold text-muted">Data</label>
                        <input type="date" name="data" id="edit_data" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="small fw-bold text-muted">Categoria</label>
                    <select name="categoria" id="edit_cat" class="form-select">
                        <?php foreach($lista_categorias as $c): ?>
                            <option value="<?= $c['categoriaid'] ?>"><?= $c['categoriadescricao'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<script>
// Variável com o valor máximo permitido
const maxValorPermitido = <?= $saldo_devedor_visual ?>;

function abrirEditar(item) {
    document.getElementById('edit_id').value = item.contasid;
    document.getElementById('edit_desc').value = item.contadescricao;
    let val = parseFloat(item.contavalor).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    document.getElementById('edit_valor').value = val;
    document.getElementById('edit_data').value = item.contavencimento;
    document.getElementById('edit_cat').value = item.categoriaid;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

function confirmarExclusao(id) {
    if (confirm("Excluir este lançamento?")) {
        window.location.href = "index.php?pg=acoes_conta&id=" + id + "&acao=excluir&origem=faturas";
    }
}

// Máscara de Moeda e Bloqueio de Valor
const moneyInputs = document.querySelectorAll('#edit_valor, #inputValorPagar');
moneyInputs.forEach(input => {
    input.addEventListener('input', function(e) {
        let v = e.target.value.replace(/\D/g, "");
        v = (v/100).toFixed(2) + "";
        v = v.replace(".", ",");
        v = v.replace(/(\d)(\d{3})(\d{3}),/g, "$1.$2.$3,");
        v = v.replace(/(\d)(\d{3}),/g, "$1.$2,");
        e.target.value = v;

        // Lógica de bloqueio apenas para o input de pagar
        if (input.id === 'inputValorPagar') {
            let valorDigitado = parseFloat(v.replace('.','').replace(',','.'));
            let aviso = document.getElementById('avisoMaximo');
            
            if (valorDigitado > maxValorPermitido) {
                // Se passar do valor, reseta para o máximo e mostra aviso
                aviso.classList.remove('d-none');
                let maxFormatado = maxValorPermitido.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                e.target.value = maxFormatado; // Força o valor máximo
            } else {
                aviso.classList.add('d-none');
            }
        }
    });
});
</script>