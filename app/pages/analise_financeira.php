<?php
// app/pages/analise_financeira.php

// 1. Segurança e Setup
if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];
$mes_filtro = $_GET['mes'] ?? date('Y-m');

// --- 0. PREPARAÇÃO ---
$primeiro_dia_mes = $mes_filtro . "-01";

// --- AJUSTE IMPORTANTE ---
// Prioriza a 'competenciafatura' (data do cartão). Se for nula (débito/dinheiro), usa 'contacompetencia'.
$campo_data_real = "COALESCE(competenciafatura, contacompetencia)"; 

// --- 1. SALDO REAL ACUMULADO (CAIXA PASSADO) ---
$stmt_passado_real = $pdo->prepare("SELECT 
    SUM(CASE WHEN contatipo = 'Entrada' THEN contavalor ELSE 0 END) -
    SUM(CASE WHEN contatipo = 'Saída' THEN contavalor ELSE 0 END) as saldo
    FROM contas 
    WHERE usuarioid = ? 
    AND $campo_data_real < ? 
    AND contasituacao = 'Pago'");
$stmt_passado_real->execute([$uid, $mes_filtro]);
$saldo_anterior_real = $stmt_passado_real->fetch()['saldo'] ?? 0;

// --- 2. SALDO GERAL (PROJEÇÃO PASSADA) ---
$stmt_passado_geral = $pdo->prepare("SELECT 
    SUM(CASE WHEN contatipo = 'Entrada' THEN contavalor ELSE 0 END) -
    SUM(CASE WHEN contatipo = 'Saída' THEN contavalor ELSE 0 END) as saldo
    FROM contas 
    WHERE usuarioid = ? 
    AND $campo_data_real < ?");
$stmt_passado_geral->execute([$uid, $mes_filtro]);
$saldo_anterior_geral = $stmt_passado_geral->fetch()['saldo'] ?? 0;

// --- 3. TOTAIS DO MÊS ATUAL ---
$stmt_totais = $pdo->prepare("SELECT 
    SUM(CASE WHEN contatipo = 'Entrada' THEN contavalor ELSE 0 END) as e_total,
    SUM(CASE WHEN contatipo = 'Saída' THEN contavalor ELSE 0 END) as s_total,
    SUM(CASE WHEN contatipo = 'Entrada' AND contasituacao = 'Pago' THEN contavalor ELSE 0 END) as e_paga,
    SUM(CASE WHEN contatipo = 'Saída' AND contasituacao = 'Pago' THEN contavalor ELSE 0 END) as s_paga,
    SUM(CASE WHEN cartoid IS NOT NULL AND contatipo = 'Saída' THEN contavalor ELSE 0 END) as total_cartao
    FROM contas 
    WHERE usuarioid = ? 
    AND $campo_data_real = ?");
$stmt_totais->execute([$uid, $mes_filtro]);
$res = $stmt_totais->fetch();

$e_total_mes = abs($res['e_total'] ?? 0);
$s_total_mes = abs($res['s_total'] ?? 0);
$e_paga_mes  = abs($res['e_paga'] ?? 0);
$s_paga_mes  = abs($res['s_paga'] ?? 0);
$v_cartao    = abs($res['total_cartao'] ?? 0);

// --- CÁLCULOS FINAIS ---
$saldo_real_hoje = $saldo_anterior_real + ($e_paga_mes - $s_paga_mes);
$projecao_final = $saldo_anterior_geral + ($e_total_mes - $s_total_mes);

$taxa_poupanca = ($e_total_mes > 0) ? (($e_total_mes - $s_total_mes) / $e_total_mes) * 100 : 0;
$percentual_gasto = ($e_total_mes > 0) ? ($s_total_mes / $e_total_mes) * 100 : 0;

// Cor do Status de Gastos
$status_cor = 'text-success';
if($percentual_gasto > 70) $status_cor = 'text-warning';
if($percentual_gasto > 90) $status_cor = 'text-danger';

// --- 4. SAÚDE DO CRÉDITO ---
$stmt_cartoes = $pdo->prepare("SELECT SUM(cartolimite) as limite_total FROM cartoes WHERE usuarioid = ?");
$stmt_cartoes->execute([$uid]);
$limite_total = $stmt_cartoes->fetch()['limite_total'] ?? 0;

$stmt_uso = $pdo->prepare("SELECT SUM(contavalor) as total FROM contas WHERE usuarioid = ? AND cartoid IS NOT NULL AND contasituacao = 'Pendente' AND contatipo = 'Saída'");
$stmt_uso->execute([$uid]);
$total_preso = abs($stmt_uso->fetch()['total'] ?? 0);
$perc_limite = ($limite_total > 0) ? ($total_preso / $limite_total) * 100 : 0;

// --- 5. DADOS PARA GRÁFICOS ---
// Histórico (6 meses ATRÁS a partir do filtro)
$meses_hist = []; $valores_hist_e = []; $valores_hist_s = [];
for($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months", strtotime($primeiro_dia_mes)));
    $stmt_h = $pdo->prepare("SELECT SUM(CASE WHEN contatipo='Entrada' THEN contavalor ELSE 0 END) as e, SUM(CASE WHEN contatipo='Saída' THEN contavalor ELSE 0 END) as s FROM contas WHERE usuarioid=? AND $campo_data_real=?");
    $stmt_h->execute([$uid, $m]);
    $h = $stmt_h->fetch();
    
    $fmt = new IntlDateFormatter('pt_BR', IntlDateFormatter::NONE, IntlDateFormatter::NONE);
    $fmt->setPattern('MMM');
    $meses_hist[] = ucfirst($fmt->format(strtotime($m."-01")));
    
    $valores_hist_e[] = abs($h['e']??0); 
    $valores_hist_s[] = abs($h['s']??0);
}

// Pizza (Categorias)
$stmt_pizza = $pdo->prepare("SELECT cat.categoriadescricao as label, SUM(c.contavalor) as total FROM contas c JOIN categorias cat ON c.categoriaid = cat.categoriaid WHERE c.usuarioid=? AND $campo_data_real=? AND c.contatipo='Saída' GROUP BY cat.categoriaid ORDER BY total DESC");
$stmt_pizza->execute([$uid, $mes_filtro]);
$dados_pizza = $stmt_pizza->fetchAll(PDO::FETCH_ASSOC);

// Semanal
$stmt_sem = $pdo->prepare("SELECT FLOOR((DAY(contavencimento)-1)/7)+1 as semana, SUM(contavalor) as total FROM contas WHERE usuarioid=? AND $campo_data_real=? AND contatipo='Saída' GROUP BY semana ORDER BY semana");
$stmt_sem->execute([$uid, $mes_filtro]);
$semanal_res = $stmt_sem->fetchAll(PDO::FETCH_KEY_PAIR);
$valores_semanais = []; for($w=1; $w<=5; $w++) { $valores_semanais[] = $semanal_res[$w] ?? 0; }

// --- 6. LISTAGENS ---
$stmt_cat = $pdo->prepare("SELECT cat.categoriaid, cat.categoriadescricao as label, SUM(c.contavalor) as total FROM contas c JOIN categorias cat ON c.categoriaid = cat.categoriaid WHERE c.usuarioid=? AND $campo_data_real=? AND c.contatipo='Saída' GROUP BY cat.categoriaid ORDER BY total DESC");
$stmt_cat->execute([$uid, $mes_filtro]);
$categorias_lista = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

$stmt_all = $pdo->prepare("SELECT c.*, cat.categoriadescricao, car.cartonome FROM contas c LEFT JOIN categorias cat ON c.categoriaid = cat.categoriaid LEFT JOIN cartoes car ON c.cartoid = car.cartoid WHERE c.usuarioid=? AND $campo_data_real=? ORDER BY c.contavencimento DESC");
$stmt_all->execute([$uid, $mes_filtro]);
$todos_lancamentos = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

// Título do Mês
$fmt_titulo = new IntlDateFormatter('pt_BR', IntlDateFormatter::NONE, IntlDateFormatter::NONE);
$fmt_titulo->setPattern('MMMM yyyy');
$titulo_mes = ucfirst($fmt_titulo->format(strtotime($primeiro_dia_mes)));
?>

<style>
    /* CSS Padronizado */
    :root { --primary: #4361ee; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --dark: #1e293b; --purple: #7209b7; }
    
    .month-pill { padding: 8px 20px; border-radius: 50px; background: white; border: 1px solid #e2e8f0; color: #64748b; text-decoration: none; font-weight: 600; font-size: 0.85rem; white-space: nowrap; transition: 0.2s; }
    .month-pill.active { background: var(--primary); color: white; border-color: var(--primary); box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3); }
    .month-pill:hover { border-color: var(--primary); color: var(--primary); }
    
    .card-stat { border: none; border-radius: 24px; background: #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.03); height: 100%; padding: 24px; transition: transform 0.2s; }
    .card-stat:hover { transform: translateY(-3px); }
    
    .icon-box { width: 48px; height: 48px; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; font-size: 1.4rem; }
    
    .bg-purple-gradient { background: linear-gradient(135deg, #7209b7 0%, #4361ee 100%); color: white; }
    
    .category-btn { cursor: pointer; padding: 12px; border-radius: 16px; border: 1px solid #f1f5f9; background: #fff; width: 100%; text-align: left; margin-bottom: 8px; transition: 0.2s; display: block; }
    .category-btn:hover { background: #f8fafc; border-color: #e2e8f0; }
    .category-btn.active { border-color: var(--primary); background: #eef2ff; color: var(--primary); }
    
    .transaction-item { display: flex; align-items: center; padding: 14px; border-radius: 16px; margin-bottom: 8px; background: #fff; border: 1px solid #f1f5f9; transition: 0.2s; }
    .transaction-item:hover { transform: translateX(5px); border-color: #e2e8f0; }
    
    .hidden { display: none !important; }
</style>

<div class="container py-4">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-0">Dashboard Analítico</h4>
            <p class="text-muted small mb-0">Visão geral financeira de <strong><?= $titulo_mes ?></strong></p>
        </div>
        
        <div class="d-flex overflow-x-auto gap-2 pb-2" style="scrollbar-width: none; max-width: 100%;">
            <?php 
            for($i = -2; $i <= 2; $i++): 
                $m = date('Y-m', strtotime("+$i month", strtotime($primeiro_dia_mes)));
                
                $fmt_nav = new IntlDateFormatter('pt_BR', IntlDateFormatter::NONE, IntlDateFormatter::NONE);
                $fmt_nav->setPattern('MMM yy');
                if(date('Y', strtotime($m)) == date('Y')) {
                     $fmt_nav->setPattern('MMM');
                }
                $label = ucfirst($fmt_nav->format(strtotime($m."-01")));
            ?>
                <a href="?pg=analise_financeira&mes=<?= $m ?>" class="month-pill <?= $mes_filtro == $m ? 'active' : '' ?>">
                    <?= $label ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card-stat p-3 text-center text-md-start">
                <div class="icon-box bg-success bg-opacity-10 text-success mx-auto mx-md-0"><i class="bi bi-arrow-up-circle"></i></div>
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.7rem;">Entradas</small>
                <h5 class="fw-bold mb-0 text-success">R$ <?= number_format($e_total_mes, 2, ',', '.') ?></h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-stat p-3 text-center text-md-start">
                <div class="icon-box bg-danger bg-opacity-10 text-danger mx-auto mx-md-0"><i class="bi bi-arrow-down-circle"></i></div>
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.7rem;">Saídas</small>
                <h5 class="fw-bold mb-0 text-danger">R$ <?= number_format($s_total_mes, 2, ',', '.') ?></h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-stat p-3 text-center text-md-start">
                <div class="icon-box bg-primary bg-opacity-10 text-primary mx-auto mx-md-0"><i class="bi bi-wallet2"></i></div>
                <small class="text-muted fw-bold d-block text-uppercase" style="font-size: 0.7rem;">Saldo em Caixa</small>
                <h5 class="fw-bold mb-0 text-primary">R$ <?= number_format($saldo_real_hoje, 2, ',', '.') ?></h5>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-stat p-3 bg-dark text-white text-center text-md-start">
                <div class="icon-box bg-white bg-opacity-25 text-white mx-auto mx-md-0"><i class="bi bi-calculator"></i></div>
                <small class="text-white-50 fw-bold d-block text-uppercase" style="font-size: 0.7rem;">Projeção Final</small>
                <h5 class="fw-bold mb-0">R$ <?= number_format($projecao_final, 2, ',', '.') ?></h5>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-7">
            <div class="card-stat bg-purple-gradient text-white">
                <h6 class="fw-bold mb-4 small text-uppercase opacity-75"><i class="bi bi-credit-card-2-front me-2"></i> Limite Global de Crédito</h6>
                <div class="row align-items-end">
                    <div class="col-md-6 border-end border-white border-opacity-25">
                        <small class="opacity-75 d-block mb-1">Comprometido</small>
                        <h2 class="fw-bold mb-2">R$ <?= number_format($total_preso, 2, ',', '.') ?></h2>
                        <div class="progress mb-2 bg-black bg-opacity-25" style="height: 8px; border-radius: 10px;">
                            <div class="progress-bar bg-white" style="width: <?= min(100, $perc_limite) ?>%"></div>
                        </div>
                        <small class="opacity-75"><?= number_format($perc_limite, 1) ?>% utilizado</small>
                    </div>
                    <div class="col-md-6 ps-md-4 pt-3 pt-md-0">
                        <small class="opacity-75 d-block mb-1">Disponível para uso</small>
                        <h3 class="fw-bold mb-1">R$ <?= number_format($limite_total - $total_preso, 2, ',', '.') ?></h3>
                        <small class="opacity-50" style="font-size: 0.75rem;">Limite total: R$ <?= number_format($limite_total, 2, ',', '.') ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-lg-5">
            <div class="card-stat text-center d-flex flex-column justify-content-center">
                <h6 class="fw-bold mb-3 small text-uppercase text-muted">Comprometimento da Renda</h6>
                <div class="position-relative d-inline-flex align-items-center justify-content-center mb-3">
                    <div style="width: 120px; height: 120px; border-radius: 50%; border: 10px solid #f1f5f9; display: flex; align-items: center; justify-content: center;">
                        <h2 class="fw-bold <?= $status_cor ?> mb-0"><?= round($percentual_gasto) ?>%</h2>
                    </div>
                </div>
                <?php if($percentual_gasto < 70): ?>
                    <small class="text-success fw-bold bg-success bg-opacity-10 px-3 py-1 rounded-pill align-self-center"><i class="bi bi-check-circle-fill me-1"></i> Saúde Excelente</small>
                <?php else: ?>
                    <small class="text-warning fw-bold bg-warning bg-opacity-10 px-3 py-1 rounded-pill align-self-center"><i class="bi bi-exclamation-triangle-fill me-1"></i> Atenção aos Gastos</small>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card-stat">
                <h6 class="fw-bold mb-4 small text-uppercase text-muted">Gastos por Categoria</h6>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartPizza"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card-stat">
                <h6 class="fw-bold mb-4 small text-uppercase text-muted">Evolução Semana a Semana</h6>
                <div style="height: 250px; position: relative;">
                    <canvas id="chartSemanal"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card-stat mb-4">
        <h6 class="fw-bold mb-4 small text-uppercase text-muted">Histórico Semestral (Fluxo de Caixa)</h6>
        <div style="height: 300px; position: relative;">
            <canvas id="chartEvolucao"></canvas>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-lg-4">
            <div class="card-stat">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold m-0 small text-uppercase text-muted">Top Categorias</h6>
                    <a href="?pg=analise_categorias" class="btn btn-sm btn-light rounded-pill px-3 fw-bold border" style="font-size: 0.7rem;">
                        RELATÓRIOS
                    </a>
                </div>
                <div style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                    <?php foreach($categorias_lista as $c): $p = ($s_total_mes > 0) ? ($c['total'] / $s_total_mes) * 100 : 0; ?>
                    <div class="category-btn" onclick="filterCategory(<?= $c['categoriaid'] ?>, this)">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-bold text-dark"><?= $c['label'] ?></span>
                            <span class="small fw-bold text-muted">R$ <?= number_format($c['total'], 2, ',', '.') ?></span>
                        </div>
                        <div class="progress" style="height: 6px; background: #f1f5f9; border-radius: 10px;">
                            <div class="progress-bar bg-primary" style="width: <?= $p ?>%; border-radius: 10px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-8">
            <div class="card-stat">
                <h6 class="fw-bold mb-4 small text-uppercase text-muted">Extrato Filtrado</h6>
                <div id="extrato-container" style="max-height: 500px; overflow-y: auto;">
                    <?php foreach($todos_lancamentos as $l): $pago = ($l['contasituacao'] == 'Pago'); ?>
                    <div class="transaction-item js-item" data-catid="<?= $l['categoriaid'] ?>">
                        <div class="me-3">
                            <div style="width: 40px; height: 40px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background: <?= $l['contatipo'] == 'Saída' ? '#fee2e2' : '#dcfce7' ?>; color: <?= $l['contatipo'] == 'Saída' ? '#ef4444' : '#10b981' ?>;">
                                <i class="bi <?= $l['contatipo'] == 'Saída' ? 'bi-bag' : 'bi-wallet2' ?>"></i>
                            </div>
                        </div>
                        
                        <div class="flex-grow-1 text-truncate">
                            <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;"><?= $l['contadescricao'] ?></h6>
                            <small class="text-muted" style="font-size: 0.75rem;">
                                <?= date('d/m', strtotime($l['contavencimento'])) ?> • <?= $l['categoriadescricao'] ?> 
                                <?= $l['cartonome'] ? " • <span class='badge bg-light text-dark border'>{$l['cartonome']}</span>" : "" ?>
                            </small>
                        </div>
                        
                        <div class="text-end ms-3">
                            <span class="fw-bold d-block <?= $l['contatipo'] == 'Saída' ? 'text-danger' : 'text-success' ?>" style="font-size: 0.9rem;">
                                <?= $l['contatipo'] == 'Saída' ? '-' : '+' ?> R$ <?= number_format(abs($l['contavalor']), 2, ',', '.') ?>
                            </span>
                            <?php if($pago): ?>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill" style="font-size: 0.6rem;"><i class="bi bi-check-circle-fill me-1"></i> PAGO</span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill" style="font-size: 0.6rem;">PENDENTE</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Filtro JS Simples
    function filterCategory(catId, btn) {
        const isActive = btn.classList.contains('active');
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        
        if (isActive) {
            // Remove filtro
            document.querySelectorAll('.js-item').forEach(i => i.classList.remove('hidden'));
        } else {
            // Aplica filtro
            btn.classList.add('active');
            document.querySelectorAll('.js-item').forEach(i => {
                if (i.dataset.catid == catId) {
                    i.classList.remove('hidden');
                } else {
                    i.classList.add('hidden');
                }
            });
        }
    }

    // Configuração Comum dos Gráficos
    Chart.defaults.font.family = "'Plus Jakarta Sans', sans-serif";
    Chart.defaults.color = '#64748b';

    // Gráfico de Evolução (Linha)
    new Chart(document.getElementById('chartEvolucao'), {
        type: 'line',
        data: {
            labels: <?= json_encode($meses_hist) ?>,
            datasets: [
                { 
                    label: 'Entradas', 
                    data: <?= json_encode($valores_hist_e) ?>, 
                    borderColor: '#10b981', 
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4, 
                    fill: true,
                    pointRadius: 4
                },
                { 
                    label: 'Saídas', 
                    data: <?= json_encode($valores_hist_s) ?>, 
                    borderColor: '#ef4444', 
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4, 
                    fill: true,
                    pointRadius: 4
                }
            ]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { legend: { position: 'top', align: 'end', labels: { usePointStyle: true } } },
            scales: { y: { grid: { borderDash: [5, 5] }, beginAtZero: true }, x: { grid: { display: false } } }
        }
    });

    // Gráfico de Pizza
    new Chart(document.getElementById('chartPizza'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($dados_pizza, 'label')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($dados_pizza, 'total')) ?>,
                backgroundColor: ['#4361ee', '#7209b7', '#f72585', '#4cc9f0', '#3a86ff', '#10b981', '#f59e0b'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            cutout: '75%', 
            plugins: { legend: { display: false } } 
        }
    });

    // Gráfico Semanal (Barras)
    new Chart(document.getElementById('chartSemanal'), {
        type: 'bar',
        data: {
            labels: ['Sem 1', 'Sem 2', 'Sem 3', 'Sem 4', 'Sem 5'],
            datasets: [{ 
                label: 'Gastos', 
                data: <?= json_encode($valores_semanais) ?>, 
                backgroundColor: '#4361ee', 
                borderRadius: 8,
                barThickness: 20
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { legend: { display: false } },
            scales: { y: { display: false }, x: { grid: { display: false } } }
        }
    });
</script>