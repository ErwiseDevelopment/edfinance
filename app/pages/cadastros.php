<?php
// app/pages/cadastros.php

if (!defined('APP_PATH')) exit; 
?>

<style>
    /* --- ESTILO DOS CARDS DE MENU --- */
    .menu-card {
        background: white;
        border-radius: 20px;
        padding: 25px 20px;
        border: 1px solid rgba(0,0,0,0.04);
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        text-align: center;
        transition: all 0.2s ease-in-out;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-decoration: none;
        color: #1e293b;
        position: relative;
        overflow: hidden;
    }

    .menu-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        border-color: rgba(67, 97, 238, 0.3);
    }

    /* Ícone Grande */
    .icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        margin-bottom: 15px;
        transition: transform 0.2s;
    }
    
    .menu-card:hover .icon-wrapper {
        transform: scale(1.15) rotate(5deg);
    }

    /* Cores Suaves para os Ícones */
    .bg-blue   { background: #eff6ff; color: #3b82f6; }
    .bg-green  { background: #f0fdf4; color: #22c55e; }
    .bg-purple { background: #faf5ff; color: #a855f7; }
    .bg-orange { background: #fff7ed; color: #f97316; }
    .bg-red    { background: #fef2f2; color: #ef4444; }
    .bg-cyan   { background: #ecfeff; color: #06b6d4; }
    .bg-dark   { background: #f1f5f9; color: #475569; }

    /* Texto */
    .menu-title { font-weight: 700; font-size: 1rem; margin-bottom: 5px; }
    .menu-desc { font-size: 0.8rem; color: #64748b; line-height: 1.4; margin-bottom: 0; }

    /* Separadores de Seção */
    .section-title {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #94a3b8;
        margin: 30px 0 15px 5px;
        display: flex;
        align-items: center;
    }
    .section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e2e8f0;
        margin-left: 15px;
    }
</style>

<div class="container py-4">
    
    <div class="mb-2">
        <h3 class="fw-bold mb-0">Menu Geral</h3>
        <p class="text-muted small">Acesso rápido a todas as funcionalidades.</p>
    </div>

    <div class="section-title"><i class="bi bi-calendar-event me-2"></i> Rotina</div>
    <div class="row g-3">
        
        <div class="col-6 col-md-4 col-lg-3">
            <a href="index.php?pg=fluxo_caixa" class="menu-card">
                <div class="icon-wrapper bg-green">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div class="menu-title">Fluxo de Caixa</div>
                <p class="menu-desc">Entradas e saídas do mês.</p>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="index.php?pg=faturas" class="menu-card">
                <div class="icon-wrapper bg-blue">
                    <i class="bi bi-receipt"></i>
                </div>
                <div class="menu-title">Faturas</div>
                <p class="menu-desc">Gerencie seus cartões.</p>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="index.php?pg=cadastro_assinatura" class="menu-card">
                <div class="icon-wrapper bg-cyan">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div class="menu-title">Assinaturas</div>
                <p class="menu-desc">Pagamentos recorrentes.</p>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="index.php?pg=analise_financeira" class="menu-card">
                <div class="icon-wrapper bg-purple">
                    <i class="bi bi-pie-chart-fill"></i>
                </div>
                <div class="menu-title">Dashboard</div>
                <p class="menu-desc">Visão geral e gráficos.</p>
            </a>
        </div>

    </div>

    <div class="section-title"><i class="bi bi-gear-wide-connected me-2"></i> Configurações</div>
    <div class="row g-3">
        
        <div class="col-6 col-md-4 col-lg-3">
            <a href="index.php?pg=categorias" class="menu-card">
                <div class="icon-wrapper bg-orange">
                    <i class="bi bi-tags-fill"></i>
                </div>
                <div class="menu-title">Categorias</div>
                <p class="menu-desc">Tipos de despesas/receitas.</p>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="index.php?pg=cadastro_cartao" class="menu-card">
                <div class="icon-wrapper bg-red">
                    <i class="bi bi-credit-card-2-front-fill"></i>
                </div>
                <div class="menu-title">Meus Cartões</div>
                <p class="menu-desc">Limites e datas.</p>
            </a>
        </div>

        <div class="col-6 col-md-4 col-lg-3">
            <a href="index.php?pg=metas_mensais" class="menu-card">
                <div class="icon-wrapper bg-dark">
                    <i class="bi bi-bullseye"></i>
                </div>
                <div class="menu-title">Metas</div>
                <p class="menu-desc">Limites de gastos.</p>
            </a>
        </div>

    </div>

    <div class="section-title"><i class="bi bi-person-badge me-2"></i> Conta</div>
    <div class="row g-3 mb-5">
        
        <div class="col-12 col-md-6">
            <a href="index.php?pg=perfil" class="menu-card flex-row align-items-center text-start p-3">
                <div class="icon-wrapper bg-dark mb-0 me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <div class="menu-title">Meu Perfil</div>
                    <p class="menu-desc">Dados da conta e senha.</p>
                </div>
            </a>
        </div>

        <div class="col-12 col-md-6">
            <a href="logout.php" class="menu-card flex-row align-items-center text-start p-3 border-danger bg-danger bg-opacity-10 text-danger">
                <div class="icon-wrapper bg-white text-danger mb-0 me-3" style="width: 50px; height: 50px; font-size: 1.2rem;">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
                <div>
                    <div class="menu-title text-danger">Sair do Sistema</div>
                    <p class="menu-desc text-danger opacity-75">Encerrar sessão segura.</p>
                </div>
            </a>
        </div>

    </div>

</div>