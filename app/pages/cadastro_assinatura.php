<?php
// app/pages/cadastro_assinatura.php

if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];

// Mensagens Flash
$mensagem = "";
if (isset($_SESSION['mensagem_flash'])) {
    $tipo = $_SESSION['tipo_flash'] ?? 'success';
    $msg = $_SESSION['mensagem_flash'];
    $icone = ($tipo == 'success') ? 'bi-check-circle-fill' : 'bi-info-circle-fill';
    $mensagem = "<div class='alert alert-{$tipo} border-0 shadow-sm py-3 rounded-4 mb-4 d-flex align-items-center animate-fade-in'><i class='bi {$icone} fs-4 me-3'></i><div>{$msg}</div><button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button></div>";
    unset($_SESSION['mensagem_flash']); unset($_SESSION['tipo_flash']);
}

// Consultas
$stmt_cat = $pdo->prepare("SELECT * FROM categorias WHERE usuarioid = ? AND categoriatipo = 'Despesa' ORDER BY categoriadescricao ASC");
$stmt_cat->execute([$uid]);
$categorias = $stmt_cat->fetchAll();

$stmt_cart = $pdo->prepare("SELECT * FROM cartoes WHERE usuarioid = ? ORDER BY cartonome ASC");
$stmt_cart->execute([$uid]);
$cartoes = $stmt_cart->fetchAll();

// Lista Assinaturas Existentes
$stmt_ass = $pdo->prepare("
    SELECT a.*, c.categoriadescricao, car.cartonome, car.cartocor 
    FROM assinaturas a
    LEFT JOIN categorias c ON a.categoriaid = c.categoriaid
    LEFT JOIN cartoes car ON a.cartoid = car.cartoid
    WHERE a.usuarioid = ?
    ORDER BY a.ativo DESC, a.dia_cobranca ASC
");
$stmt_ass->execute([$uid]);
$lista_assinaturas = $stmt_ass->fetchAll();
?>

<style>
    .card-custom { background: white; border-radius: 24px; border: 1px solid #f1f5f9; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05); }
    .input-custom { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; font-weight: 600; color: #334155; }
    .input-custom:focus { background-color: #fff; border-color: #4361ee; box-shadow: 0 0 0 4px rgba(67,97,238,0.1); }
    
    .ass-item { transition: 0.2s; border: 1px solid #f1f5f9; border-radius: 16px; margin-bottom: 10px; background: #fff; }
    .ass-item:hover { transform: translateY(-2px); border-color: #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .ass-inactive { opacity: 0.6; filter: grayscale(1); background: #f8fafc; }
    
    .badge-day { width: 35px; height: 35px; background: #eef2ff; color: #4361ee; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.9rem; flex-shrink: 0; }
    
    .btn-action-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s; border: none; background: transparent; color: #94a3b8; }
    .btn-action-icon:hover { background: #f1f5f9; color: #334155; }
</style>

<div class="container py-4">
    
    <div class="d-flex align-items-center mb-4">
        <a href="index.php?pg=cadastros" class="btn btn-light rounded-circle shadow-sm me-3 border"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h4 class="fw-bold mb-0">Assinaturas Recorrentes</h4>
            <small class="text-muted">Gerencie pagamentos automáticos (Netflix, Spotify, etc)</small>
        </div>
    </div>

    <?= $mensagem ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card-custom p-4">
                <h6 class="fw-bold mb-4 text-primary"><i class="bi bi-plus-circle me-2"></i> Nova / Editar Assinatura</h6>
                
                <form action="index.php?pg=salvar_assinatura_engine" method="POST">
                    <input type="hidden" name="id" id="form_id">
                    
                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">SERVIÇO / NOME</label>
                        <input type="text" name="titulo" id="form_titulo" class="form-control input-custom" placeholder="Ex: Netflix, Academia..." required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="small fw-bold text-muted mb-1">VALOR (R$)</label>
                            <input type="text" name="valor" id="form_valor" class="form-control input-custom money-mask" placeholder="0,00" required>
                        </div>
                        <div class="col-6">
                            <label class="small fw-bold text-muted mb-1">DIA COBRANÇA</label>
                            <input type="number" name="dia_cobranca" id="form_dia" class="form-control input-custom" placeholder="Dia (1-31)" min="1" max="31" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small fw-bold text-muted mb-1">CATEGORIA</label>
                        <select name="categoriaid" id="form_categoria" class="form-select input-custom" required>
                            <option value="">Selecione...</option>
                            <?php foreach($categorias as $c): ?>
                                <option value="<?= $c['categoriaid'] ?>"><?= $c['categoriadescricao'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="small fw-bold text-muted mb-1">FORMA DE PAGAMENTO</label>
                        <select name="cartoid" id="form_cartao" class="form-select input-custom">
                            <option value="">Débito em Conta / Boleto</option>
                            <?php foreach($cartoes as $car): ?>
                                <option value="<?= $car['cartoid'] ?>">Cartão <?= $car['cartonome'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm" id="btnSalvar">Salvar Assinatura</button>
                        <button type="button" class="btn btn-light py-3 rounded-4 border" onclick="limparForm()" id="btnCancelar" style="display:none;">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-7">
            <h6 class="fw-bold mb-3 text-muted ps-2">Minhas Assinaturas</h6>
            
            <?php if(empty($lista_assinaturas)): ?>
                <div class="text-center py-5 text-muted opacity-50 border rounded-4 bg-white">
                    <i class="bi bi-calendar-check fs-1 mb-2 d-block"></i> Nenhuma assinatura cadastrada.
                </div>
            <?php endif; ?>

            <?php foreach($lista_assinaturas as $a): ?>
                <div class="ass-item p-3 d-flex align-items-center <?= $a['ativo'] ? '' : 'ass-inactive' ?>">
                    
                    <div class="badge-day me-3" title="Dia da cobrança">
                        <?= str_pad($a['dia_cobranca'], 2, '0', STR_PAD_LEFT) ?>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><?= $a['titulo'] ?></h6>
                            <?php if(!$a['ativo']): ?>
                                <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">PAUSADA</span>
                            <?php endif; ?>
                        </div>
                        <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                            <span>R$ <?= number_format($a['valor'], 2, ',', '.') ?></span>
                            <span>•</span>
                            <span><?= $a['categoriadescricao'] ?></span>
                            <?php if($a['cartonome']): ?>
                                <span>•</span>
                                <span class="badge bg-light text-dark border" style="font-weight: 500;">
                                    <i class="bi bi-credit-card-2-front-fill me-1" style="color: <?= $a['cartocor'] ?>;"></i>
                                    <?= $a['cartonome'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-1 ms-3">
                        <button class="btn-action-icon" title="Editar" onclick='editar(<?= json_encode($a) ?>)'>
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        
                        <a href="index.php?pg=salvar_assinatura_engine&acao=toggle&id=<?= $a['assinaturaid'] ?>" 
                           class="btn-action-icon <?= $a['ativo'] ? 'text-success' : 'text-secondary' ?>" 
                           title="<?= $a['ativo'] ? 'Pausar cobrança' : 'Reativar cobrança' ?>">
                            <i class="bi bi-power"></i>
                        </a>

                        <a href="index.php?pg=salvar_assinatura_engine&acao=excluir&id=<?= $a['assinaturaid'] ?>" 
                           class="btn-action-icon text-danger" 
                           onclick="return confirm('Tem certeza que deseja excluir permanentemente?')" 
                           title="Excluir">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Máscara de Moeda Simples
    const moneyInput = document.querySelector('.money-mask');
    moneyInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if(value === '') return;
        value = (value / 100).toFixed(2) + '';
        value = value.replace(".", ",");
        value = value.replace(/(\d)(?=(\d{3})+(?!\d))/g, "$1.");
        e.target.value = value;
    });

    // Função de Editar (Preenche o form)
    function editar(item) {
        document.getElementById('form_id').value = item.assinaturaid;
        document.getElementById('form_titulo').value = item.titulo;
        
        // Formata valor para PT-BR
        let valor = parseFloat(item.valor).toFixed(2).replace('.', ',');
        document.getElementById('form_valor').value = valor;
        
        document.getElementById('form_dia').value = item.dia_cobranca;
        document.getElementById('form_categoria').value = item.categoriaid;
        document.getElementById('form_cartao').value = item.cartoid || "";

        // UI Changes
        document.getElementById('btnSalvar').innerText = "Atualizar Assinatura";
        document.getElementById('btnSalvar').classList.remove('btn-primary');
        document.getElementById('btnSalvar').classList.add('btn-dark');
        document.getElementById('btnCancelar').style.display = 'inline-block';
        
        // Scroll suave para o form (mobile)
        document.querySelector('.card-custom').scrollIntoView({ behavior: 'smooth' });
    }

    function limparForm() {
        document.getElementById('form_id').value = "";
        document.getElementById('form_titulo').value = "";
        document.getElementById('form_valor').value = "";
        document.getElementById('form_dia').value = "";
        document.getElementById('form_categoria').value = "";
        document.getElementById('form_cartao').value = "";

        document.getElementById('btnSalvar').innerText = "Salvar Assinatura";
        document.getElementById('btnSalvar').classList.add('btn-primary');
        document.getElementById('btnSalvar').classList.remove('btn-dark');
        document.getElementById('btnCancelar').style.display = 'none';
    }
</script>