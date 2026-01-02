<?php
// app/pages/analise_categoria.php

if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];
$mes_inicio_default = date('Y-m', strtotime('-5 months'));
$mes_fim_default = date('Y-m');

// Busca categorias (apenas Despesa/Saída)
$stmt = $pdo->prepare("SELECT categoriaid, categoriadescricao FROM categorias WHERE usuarioid = ? AND (categoriatipo = 'Despesa' OR categoriatipo = 'Saída') ORDER BY categoriadescricao ASC");
$stmt->execute([$uid]);
$todas_categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    :root { --primary: #4361ee; --success: #10b981; --danger: #ef4444; --warning: #f59e0b; --dark: #1e293b; --muted: #64748b; }
    
    .animate-fade-in { animation: fadeInDown 0.4s ease-out; }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    /* Card Padrão */
    .card-app { 
        background: #fff; border-radius: 24px; padding: 25px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); 
        height: 100%; position: relative;
    }

    /* Labels e Inputs */
    .label-app { font-size: 0.75rem; font-weight: 700; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .input-app { 
        background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 12px 15px; 
        border-radius: 12px; font-weight: 600; color: var(--dark); width: 100%; transition: 0.2s; 
    }
    .input-app:focus { outline: none; background-color: #fff; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1); }

    /* KPIs */
    .kpi-value { font-size: 1.6rem; font-weight: 800; color: var(--dark); line-height: 1.1; letter-spacing: -0.5px; }
    .kpi-label { font-size: 0.75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; margin-bottom: 8px; }
    
    .icon-kpi { 
        width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; 
        font-size: 1.4rem; margin-bottom: 12px; 
    }

    /* Progress Bar */
    .progress-thick { height: 10px; border-radius: 20px; background: #f1f5f9; overflow: hidden; margin-top: 15px; }
    
    /* Overlay de Carregamento */
    .chart-overlay { 
        position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(255,255,255,0.8); backdrop-filter: blur(2px);
        display: none; justify-content: center; align-items: center; z-index: 10; border-radius: 24px; 
    }
</style>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="index.php?pg=dashboard" class="btn btn-light rounded-circle shadow-sm me-3 border"><i class="bi bi-arrow-left"></i></a>
            <div class="d-inline-block align-middle">
                <h4 class="fw-bold m-0">Análise Detalhada</h4>
                <small class="text-muted">Evolução e comportamento por categoria</small>
            </div>
        </div>
    </div>

    <?php if (empty($todas_categorias)): ?>
        <div class="card-app text-center py-5">
            <div class="opacity-50 mb-3"><i class="bi bi-inbox fs-1"></i></div>
            <h5 class="fw-bold">Nenhuma categoria encontrada</h5>
            <p class="text-muted">Cadastre categorias do tipo "Despesa" para ver análises.</p>
            <a href="index.php?pg=categorias" class="btn btn-primary rounded-pill fw-bold">Ir para Categorias</a>
        </div>
    <?php else: ?>

        <div class="card-app mb-4">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="label-app">Selecionar Categoria</label>
                    <select id="selectCategoria" class="input-app" onchange="carregarDados()">
                        <?php foreach($todas_categorias as $cat): ?>
                            <option value="<?= $cat['categoriaid'] ?>"><?= $cat['categoriadescricao'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="label-app">Início</label>
                    <input type="month" id="evoInicio" class="input-app" value="<?= $mes_inicio_default ?>" onchange="carregarDados()">
                </div>
                <div class="col-6 col-md-3">
                    <label class="label-app">Fim</label>
                    <input type="month" id="evoFim" class="input-app" value="<?= $mes_fim_default ?>" onchange="carregarDados()">
                </div>
                <div class="col-12 col-md-2">
                    <button onclick="carregarDados()" class="btn btn-primary w-100 fw-bold py-2 shadow-sm" style="border-radius: 12px; height: 48px;">
                        <i class="bi bi-search me-2"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>

        <div id="boxMeta" class="card-app mb-4 bg-dark text-white border-0 d-none animate-fade-in" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
            <div class="d-flex justify-content-between align-items-end mb-2">
                <div>
                    <h6 class="fw-bold m-0 text-white"><i class="bi bi-bullseye me-2 text-danger"></i>Performance da Meta</h6>
                    <small class="opacity-75">Acumulado do período selecionado</small>
                </div>
                <span class="badge bg-white text-dark fw-bold px-3 py-2 rounded-pill" id="txtMetaStatus">...</span>
            </div>
            
            <div class="progress progress-thick" style="background: rgba(255,255,255,0.1);">
                <div id="barMeta" class="progress-bar" role="progressbar" style="width: 0%; transition: 1s;"></div>
            </div>
            
            <div class="d-flex justify-content-between mt-3 small">
                <span>Gasto: <strong id="txtMetaGasto" class="text-warning">R$ 0,00</strong></span>
                <span>Teto Definido: <strong id="txtMetaTotal">R$ 0,00</strong></span>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-4">
                <div class="card-app p-4 d-flex flex-column align-items-center text-center justify-content-center">
                    <div class="icon-kpi bg-primary bg-opacity-10 text-primary"><i class="bi bi-wallet2"></i></div>
                    <div class="kpi-label">Total Gasto</div>
                    <div class="kpi-value text-primary" id="kpiTotal">...</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card-app p-4 d-flex flex-column align-items-center text-center justify-content-center">
                    <div class="icon-kpi bg-success bg-opacity-10 text-success"><i class="bi bi-cart-check"></i></div>
                    <div class="kpi-label">Lançamentos</div>
                    <div class="kpi-value text-success" id="kpiQtd">...</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card-app p-4 d-flex flex-column align-items-center text-center justify-content-center">
                    <div class="icon-kpi bg-warning bg-opacity-10 text-warning"><i class="bi bi-calculator"></i></div>
                    <div class="kpi-label">Média / Compra</div>
                    <div class="kpi-value text-warning" id="kpiMedia">...</div>
                </div>
            </div>
        </div>

        <div class="card-app mb-4">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h6 class="fw-bold text-dark m-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Evolução Financeira</h6>
                <span class="badge bg-light text-muted border">Gasto Real vs Meta Mensal</span>
            </div>
            <div style="height: 320px; position: relative;">
                <canvas id="chartEvolucao"></canvas>
                <div id="loaderMain" class="chart-overlay">
                    <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"></div>
                    <span class="mt-2 fw-bold text-muted small">Analisando dados...</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card-app">
                    <h6 class="fw-bold text-dark mb-4">Gasto por Dia da Semana</h6>
                    
                    <div style="height: 250px;"><canvas id="chartSemana"></canvas></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card-app">
                    <h6 class="fw-bold text-dark mb-4">Concentração Mensal</h6>
                    
                    <div style="height: 250px;"><canvas id="chartMesSemana"></canvas></div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php if (!empty($todas_categorias)): ?>

    let chartEv = null, chartDay = null, chartWeek = null;

    // Função Principal que carrega tudo
    function carregarDados() {
        const catId = document.getElementById('selectCategoria').value;
        const ini = document.getElementById('evoInicio').value;
        const fim = document.getElementById('evoFim').value;
        
        if(!catId || !ini || !fim) return;

        // Ativa Loader
        document.getElementById('loaderMain').style.display = 'flex';
        document.querySelectorAll('.kpi-value').forEach(e => e.style.opacity = 0.3);

        const fd = new FormData();
        fd.append('acao', 'evolucao_categoria');
        fd.append('categoria_id', catId);
        fd.append('mes_inicio', ini);
        fd.append('mes_fim', fim);

        // ATENÇÃO: Certifique-se que o arquivo 'ajax_analise.php' existe na mesma pasta
        fetch('../app/pages/ajax_analise.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if(json.status !== 'success') throw new Error(json.message);

            // 1. Atualiza KPIs
            document.getElementById('kpiTotal').innerText = "R$ " + json.kpi.total;
            document.getElementById('kpiQtd').innerText = json.kpi.qtd;
            document.getElementById('kpiMedia').innerText = "R$ " + json.kpi.media;
            document.querySelectorAll('.kpi-value').forEach(e => e.style.opacity = 1);
            
            // 2. Atualiza Box de Meta
            atualizarMeta(json.meta, json.kpi.total);

            // 3. Renderiza Gráficos
            renderEvolucao(json.evolucao.labels, json.evolucao.gasto, json.evolucao.meta);
            renderDiasSemana(json.semana);
            renderSemanaMes(json.mes_semanas);
        })
        .catch(err => { 
            console.error(err); 
            // alert("Não foi possível carregar os dados. Verifique se há lançamentos.");
        })
        .finally(() => { 
            document.getElementById('loaderMain').style.display = 'none'; 
        });
    }

    // Lógica da Barra de Meta (Cores e Status)
    function atualizarMeta(meta, gastoFormatado) {
        const box = document.getElementById('boxMeta');
        const bar = document.getElementById('barMeta');
        const txtStatus = document.getElementById('txtMetaStatus');
        
        if(!meta.tem_meta) { 
            box.classList.add('d-none'); 
            return; 
        }
        
        box.classList.remove('d-none');
        document.getElementById('txtMetaGasto').innerText = "R$ " + gastoFormatado;
        document.getElementById('txtMetaTotal').innerText = "R$ " + meta.total_periodo;
        
        let perc = meta.perc;
        bar.style.width = Math.min(perc, 100) + "%";
        
        // Cores da barra
        if(perc > 100) {
            bar.className = 'progress-bar bg-danger'; // Estourou
            txtStatus.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${perc.toFixed(0)}% (Estourou)`;
            txtStatus.className = 'badge bg-danger text-white fw-bold px-3 py-2 rounded-pill';
        } else if (perc > 85) {
            bar.className = 'progress-bar bg-warning'; // Alerta
            txtStatus.innerHTML = `${perc.toFixed(0)}% Consumido`;
            txtStatus.className = 'badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill';
        } else {
            bar.className = 'progress-bar bg-success'; // OK
            txtStatus.innerHTML = `${perc.toFixed(0)}% Consumido`;
            txtStatus.className = 'badge bg-success text-white fw-bold px-3 py-2 rounded-pill';
        }
    }

    // GRÁFICO 1: LINHA (EVOLUÇÃO) + LINHA TRACEJADA (META)
    function renderEvolucao(labels, dataGasto, dataMeta) {
        const ctx = document.getElementById('chartEvolucao').getContext('2d');
        if(chartEv) chartEv.destroy();

        // Gradiente bonito
        let grad = ctx.createLinearGradient(0,0,0,300);
        grad.addColorStop(0, 'rgba(67, 97, 238, 0.3)');
        grad.addColorStop(1, 'rgba(67, 97, 238, 0.0)');

        const temMeta = dataMeta.some(v => v > 0);

        const metaDataset = temMeta ? [{
            label: 'Meta Definida',
            data: dataMeta,
            borderColor: '#ef4444',
            borderWidth: 2,
            borderDash: [5, 5],
            pointRadius: 0,
            pointHoverRadius: 4,
            fill: false,
            tension: 0
        }] : [];

        chartEv = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Gasto Real',
                        data: dataGasto,
                        borderColor: '#4361ee',
                        backgroundColor: grad,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4361ee',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },
                    ...metaDataset
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: { 
                    legend: { position: 'top', align: 'end' },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        padding: 12,
                        titleFont: { size: 13 },
                        bodyFont: { size: 13 },
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: { 
                    y: { 
                        beginAtZero: true, 
                        grid: { borderDash: [5,5], color: '#f1f5f9' }, 
                        ticks: { font: { size: 11 }, callback: v=>'R$ '+v } 
                    },
                    x: { 
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            }
        });
    }

    // GRÁFICO 2: BARRAS (DIAS DA SEMANA)
    function renderDiasSemana(d) {
        const ctx = document.getElementById('chartSemana').getContext('2d');
        if(chartDay) chartDay.destroy();
        
        chartDay = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'],
                datasets: [{ 
                    label: 'Gasto Médio', 
                    data: d, 
                    backgroundColor: '#e2e8f0', 
                    hoverBackgroundColor: '#4361ee', 
                    borderRadius: 8,
                    barPercentage: 0.6
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }, 
                scales: { 
                    y: { display: false }, 
                    x: { grid: { display: false } } 
                } 
            }
        });
    }

    // GRÁFICO 3: DONUT (SEMANAS DO MÊS)
    function renderSemanaMes(d) {
        const ctx = document.getElementById('chartMesSemana').getContext('2d');
        if(chartWeek) chartWeek.destroy();
        
        chartWeek = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Semana 1','Semana 2','Semana 3','Semana 4','Semana 5'],
                datasets: [{ 
                    data: d, 
                    backgroundColor: ['#4cc9f0','#4361ee','#3a0ca3','#f72585','#7209b7'], 
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%', 
                plugins: { 
                    legend: { position: 'right', labels: { boxWidth: 12, usePointStyle: true, font: { size: 11 } } } 
                } 
            }
        });
    }

    // Inicia carregamento
    document.addEventListener('DOMContentLoaded', () => { setTimeout(carregarDados, 300); });

<?php endif; ?>
</script>
