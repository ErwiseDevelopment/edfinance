<?php
// app/pages/fluxo_caixa.php

if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];
$mes_filtro = $_GET['mes'] ?? date('Y-m');
$mensagem = "";

// --- SISTEMA DE MENSAGENS FLASH (Sucesso/Erro) ---
if (isset($_SESSION['mensagem_flash'])) {
    $msg = $_SESSION['mensagem_flash'];
    $tipo = $_SESSION['tipo_flash'] ?? 'success';
    
    $icone = match($tipo) {
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-trash-fill',
        'warning' => 'bi-arrow-counterclockwise',
        'default' => 'bi-info-circle-fill'
    };

    $mensagem = "
    <div class='alert alert-{$tipo} border-0 shadow-sm py-3 rounded-4 mb-4 d-flex align-items-center animate-fade-in'>
        <i class='bi {$icone} fs-4 me-3'></i>
        <div>{$msg}</div>
        <button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button>
    </div>";

    unset($_SESSION['mensagem_flash']);
    unset($_SESSION['tipo_flash']);
}

$data_alvo = new DateTime($mes_filtro . "-01");
$mes_atual_txt = $data_alvo->format('Y-m');

// --- CONSULTAS ---
$stmt_cartoes = $pdo->prepare("SELECT * FROM cartoes WHERE usuarioid = ? ORDER BY cartonome ASC");
$stmt_cartoes->execute([$uid]);
$lista_cartoes = $stmt_cartoes->fetchAll();

// 1. Entradas
$stmt_entradas = $pdo->prepare("SELECT c.* FROM contas c WHERE c.usuarioid = ? AND c.contacompetencia = ? AND c.contatipo = 'Entrada' ORDER BY c.contavencimento ASC");
$stmt_entradas->execute([$uid, $mes_filtro]);
$entradas = $stmt_entradas->fetchAll();

// 2. Saídas Diretas
$stmt_saidas = $pdo->prepare("SELECT c.* FROM contas c WHERE c.usuarioid = ? AND c.contacompetencia = ? AND c.contatipo = 'Saída' AND c.cartoid IS NULL ORDER BY c.contavencimento ASC");
$stmt_saidas->execute([$uid, $mes_filtro]);
$saidas = $stmt_saidas->fetchAll();

// 3. Faturas
$stmt_faturas = $pdo->prepare("
    SELECT 
        car.cartonome, car.cartoid, car.cartovencimento, car.cartocor, 
        SUM(c.contavalor) as total_fatura,
        SUM(CASE WHEN c.contasituacao = 'Pendente' THEN 1 ELSE 0 END) as qtd_pendentes
    FROM contas c 
    JOIN cartoes car ON c.cartoid = car.cartoid 
    WHERE c.usuarioid = ? 
    AND COALESCE(c.competenciafatura, c.contacompetencia) = ? 
    GROUP BY car.cartoid
");
$stmt_faturas->execute([$uid, $mes_atual_txt]);
$faturas_mes = $stmt_faturas->fetchAll();

// Totais
$total_entradas = array_sum(array_column($entradas, 'contavalor'));
$total_saidas_diretas = array_sum(array_column($saidas, 'contavalor'));
$total_faturas = array_sum(array_column($faturas_mes, 'total_fatura'));
$total_geral_saidas = $total_saidas_diretas + $total_faturas;
$saldo_do_mes = $total_entradas - $total_geral_saidas;
?>

<style>
    /* (Mantenha o CSS original aqui, igual ao anterior) */
    body { background-color: #f8fafc; color: #1e293b; }
    .animate-fade-in { animation: fadeInDown 0.4s ease-out; }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    .month-pill { white-space: nowrap; padding: 8px 20px; border-radius: 50px; background: #fff; color: #64748b; text-decoration: none; font-size: 0.85rem; border: 1px solid #e2e8f0; font-weight: 600; transition: 0.2s; }
    .month-pill:hover { background: #f1f5f9; color: #4361ee; }
    .month-pill.active { background: #4361ee; color: #fff; border-color: #4361ee; box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3); }
    .section-header { font-size: 0.85rem; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
    .text-receita { color: #10b981; }
    .text-despesa { color: #ef4444; }
    .transaction-card { background: #fff; border-radius: 20px; padding: 12px 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s; }
    .transaction-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.03); }
    .is-paid .item-desc { text-decoration: line-through; color: #94a3b8; }
    .is-paid .item-value { opacity: 0.5; text-decoration: line-through; }
    .item-desc { font-weight: 700; color: #1e293b; display: block; font-size: 0.9rem; line-height: 1.2; }
    .item-date { font-size: 0.75rem; color: #94a3b8; font-weight: 500; }
    .item-value { font-weight: 800; font-size: 0.95rem; }
    .btn-check-toggle { width: 40px; height: 40px; border-radius: 50%; border: 2px solid #e2e8f0; background: transparent; color: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; transition: all 0.2s ease; text-decoration: none; }
    .btn-check-toggle:hover { border-color: #10b981; color: #10b981; background: #f0fdf4; }
    .btn-check-toggle.active { background: #10b981; border-color: #10b981; color: #fff; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
    .icon-fatura { width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .btn-icon { width: 32px; height: 32px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; transition: 0.2s; color: #94a3b8; background: transparent; }
    .btn-icon:hover { background: #f1f5f9; color: #1e293b; }
    .balance-card { background: #1e293b; color: white; border-radius: 20px; padding: 20px; margin-bottom: 30px; }
</style>

<div class="container py-4">
    
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Fluxo de Caixa</h4>
        <p class="text-muted small">Controle mensal de entradas e saídas.</p>
    </div>

    <div class="d-flex overflow-x-auto gap-2 mb-4 pb-2" style="scrollbar-width: none;">
        <?php for($i = -2; $i <= 4; $i++): 
            $m = date('Y-m', strtotime("+$i month", strtotime(date('Y-m-01'))));
            $label = (new IntlDateFormatter('pt_BR', 0, 0, null, null, 'MMMM yy'))->format(strtotime($m."-01"));
        ?>
            <a href="?pg=fluxo_caixa&mes=<?= $m ?>" class="month-pill <?= $mes_filtro == $m ? 'active' : '' ?>"><?= ucfirst($label) ?></a>
        <?php endfor; ?>
    </div>

    <?= $mensagem ?>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="balance-card shadow-sm d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-white-50 small fw-bold text-uppercase">Balanço Previsto</span>
                    <h2 class="fw-bold m-0 text-white">R$ <?= number_format($saldo_do_mes, 2, ',', '.') ?></h2>
                </div>
                <div class="text-end">
                    <div class="badge bg-success bg-opacity-25 text-success mb-1">+ <?= number_format($total_entradas, 2, ',', '.') ?></div>
                    <div class="d-block"></div>
                    <div class="badge bg-danger bg-opacity-25 text-danger">- <?= number_format($total_geral_saidas, 2, ',', '.') ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="section-header">
                <span class="text-receita"><i class="bi bi-arrow-up-circle me-2"></i> Receitas</span>
            </div>
            
            <?php if(empty($entradas)): ?> 
                <div class="text-center py-5 text-muted bg-white rounded-4 border border-light opacity-50">
                    <i class="bi bi-inbox fs-1 mb-2 d-block"></i> <small>Vazio</small>
                </div> 
            <?php endif; ?>

            <?php foreach($entradas as $e): $isPago = ($e['contasituacao'] == 'Pago'); ?>
                <div class="transaction-card shadow-sm <?= $isPago ? 'is-paid' : '' ?>">
                    <div class="d-flex align-items-center overflow-hidden">
                        <div class="me-3">
                            <a href="index.php?pg=acoes_conta&acao=<?= $isPago ? 'estornar' : 'pagar' ?>&id=<?= $e['contasid'] ?>&origem=fluxo" 
                               class="btn-check-toggle <?= $isPago ? 'active' : '' ?>" 
                               title="<?= $isPago ? 'Marcar como pendente' : 'Marcar como recebido' ?>">
                               <i class="bi bi-check-lg"></i>
                            </a>
                        </div>
                        <div class="text-truncate">
                            <span class="item-desc text-truncate"><?= $e['contadescricao'] ?></span>
                            <span class="item-date">
                                <?= date('d/m', strtotime($e['contavencimento'])) ?>
                                <?php if($e['contafixa']): ?> • <i class="bi bi-arrow-repeat" title="Fixa"></i><?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="text-end ms-2 d-flex flex-column align-items-end">
                        <div class="item-value text-receita mb-1">R$ <?= number_format($e['contavalor'], 2, ',', '.') ?></div>
                        <div class="d-flex gap-1">
                            <button onclick='abrirModalEdicao(<?= json_encode($e) ?>)' class="btn-icon" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                            <a href="?pg=fluxo_caixa_engine&acao=excluir&id=<?= $e['contasid'] ?>&mes=<?= $mes_filtro ?>" class="btn-icon text-danger" onclick="return confirm('Excluir?')" title="Excluir"><i class="bi bi-trash-fill"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="col-lg-6">
            <div class="section-header">
                <span class="text-despesa"><i class="bi bi-arrow-down-circle me-2"></i> Despesas</span>
            </div>
            
            <?php foreach($faturas_mes as $fat): 
                $faturaPaga = ($fat['qtd_pendentes'] == 0);
                $corCartao = $fat['cartocor'] ?? '#4361ee';
            ?>
                <div class="transaction-card shadow-sm border-start border-4 <?= $faturaPaga ? 'is-paid' : '' ?>" style="border-color: <?= $corCartao ?> !important;">
                    <div class="d-flex align-items-center">
                        <div class="icon-box icon-fatura me-3 shadow-sm" style="background: <?= $corCartao ?>; color: #fff;">
                            <i class="bi bi-credit-card-2-front-fill"></i>
                        </div>
                        <div>
                            <span class="item-desc">Fatura <?= $fat['cartonome'] ?></span>
                            <?php if($faturaPaga): ?>
                                <small class="text-success fw-bold">PAGA</small>
                            <?php else: ?>
                                <small style="color: <?= $corCartao ?>; font-weight: bold;">Vence <?= $fat['cartovencimento'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="item-value text-despesa mb-1">R$ <?= number_format($fat['total_fatura'], 2, ',', '.') ?></div>
                        <a href="index.php?pg=faturas&cartoid=<?= $fat['cartoid'] ?>&mes=<?= $mes_filtro ?>" class="small fw-bold text-decoration-none" style="color: <?= $corCartao ?>;">Ver</a>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if(empty($saidas) && empty($faturas_mes)): ?> 
                <div class="text-center py-5 text-muted bg-white rounded-4 border border-light opacity-50">
                    <i class="bi bi-check2-circle fs-1 mb-2 d-block"></i> <small>Vazio</small>
                </div> 
            <?php endif; ?>
            
            <?php foreach($saidas as $s): $isPago = ($s['contasituacao'] == 'Pago'); ?>
                <div class="transaction-card shadow-sm <?= $isPago ? 'is-paid' : '' ?>">
                    <div class="d-flex align-items-center overflow-hidden">
                        <div class="me-3">
                            <a href="index.php?pg=acoes_conta&acao=<?= $isPago ? 'estornar' : 'pagar' ?>&id=<?= $s['contasid'] ?>&origem=fluxo" 
                               class="btn-check-toggle <?= $isPago ? 'active' : '' ?>" 
                               title="<?= $isPago ? 'Marcar como pendente' : 'Marcar como pago' ?>">
                               <i class="bi bi-check-lg"></i>
                            </a>
                        </div>
                        <div class="text-truncate">
                            <span class="item-desc text-truncate"><?= $s['contadescricao'] ?></span>
                            <span class="item-date">
                                <?= date('d/m', strtotime($s['contavencimento'])) ?>
                                <?php if($s['contafixa']): ?> • <i class="bi bi-arrow-repeat" title="Fixa"></i><?php endif; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="text-end ms-2 d-flex flex-column align-items-end">
                        <div class="item-value text-despesa mb-1">R$ <?= number_format($s['contavalor'], 2, ',', '.') ?></div>
                        <div class="d-flex gap-1">
                            <button onclick='abrirModalEdicao(<?= json_encode($s) ?>)' class="btn-icon" title="Editar"><i class="bi bi-pencil-fill"></i></button>
                            <a href="?pg=fluxo_caixa_engine&acao=excluir&id=<?= $s['contasid'] ?>&mes=<?= $mes_filtro ?>" class="btn-icon text-danger" onclick="return confirm('Excluir?')" title="Excluir"><i class="bi bi-trash-fill"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarConta" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <form method="POST" action="index.php?pg=fluxo_caixa_engine">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="mes_atual" value="<?= $mes_filtro ?>">
                
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="fw-bold m-0">Editar Lançamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3"><label class="form-label small fw-bold text-muted">Descrição</label><input type="text" name="descricao" id="edit_descricao" class="form-control form-control-lg bg-light border-0" required></div>
                    <div class="row mb-3">
                        <div class="col-6"><label class="form-label small fw-bold text-muted">Valor</label><input type="number" step="0.01" name="valor" id="edit_valor" class="form-control form-control-lg bg-light border-0" required></div>
                        <div class="col-6"><label class="form-label small fw-bold text-muted">Vencimento</label><input type="date" name="vencimento" id="edit_vencimento" class="form-control form-control-lg bg-light border-0" required></div>
                    </div>
                    <div id="edit_div_cartao" class="mb-3">
                        <label class="form-label small fw-bold text-muted">Forma de Pagamento</label>
                        <select name="cartoid" id="edit_cartoid" class="form-select form-select-lg bg-light border-0">
                            <option value="">Saldo (Dinheiro/Pix)</option>
                            <?php foreach($lista_cartoes as $c): ?><option value="<?= $c['cartoid'] ?>"><?= $c['cartonome'] ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check form-switch bg-light p-3 rounded-3"><label class="form-check-label fw-bold" for="edit_contafixa">Repetir todo mês</label><input class="form-check-input ms-2" type="checkbox" name="contafixa" id="edit_contafixa"></div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-dark w-100 py-3 rounded-4 fw-bold shadow-sm">SALVAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalEdicao(conta) {
    document.getElementById('edit_id').value = conta.contasid;
    document.getElementById('edit_descricao').value = conta.contadescricao;
    document.getElementById('edit_valor').value = conta.contavalor;
    document.getElementById('edit_vencimento').value = conta.contavencimento;
    document.getElementById('edit_cartoid').value = conta.cartoid || "";
    document.getElementById('edit_contafixa').checked = (conta.contafixa == 1);
    
    // Esconde seleção de cartão se for receita (Entrada)
    document.getElementById('edit_div_cartao').style.display = (conta.contatipo === 'Entrada') ? 'none' : 'block';
    
    new bootstrap.Modal(document.getElementById('modalEditarConta')).show();
}
</script>