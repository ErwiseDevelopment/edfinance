<?php
// app/pages/categorias.php

if (!defined('APP_PATH')) exit; 

$uid = $_SESSION['usuarioid'];
$msg = '';

// --- LÓGICA DE PROCESSAMENTO ---

// 1. ADICIONAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo']; 
    $meta = str_replace(['.', ','], ['', '.'], $_POST['meta']);

    if(!empty($descricao)) {
        $stmt = $pdo->prepare("INSERT INTO categorias (usuarioid, categoriadescricao, categoriatipo, categoriameta) VALUES (?, ?, ?, ?)");
        if($stmt->execute([$uid, $descricao, $tipo, $meta])) {
            $_SESSION['mensagem_flash'] = "Categoria criada!";
            $_SESSION['tipo_flash'] = "success";
            echo "<script>window.location.href='index.php?pg=categorias';</script>";
            exit;
        }
    }
}

// 2. EDITAR
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $cat_id = $_POST['categoria_id'];
    $descricao = $_POST['descricao'];
    $tipo = $_POST['tipo'];
    $meta = str_replace(['.', ','], ['', '.'], $_POST['meta']);

    $stmt = $pdo->prepare("UPDATE categorias SET categoriadescricao = ?, categoriatipo = ?, categoriameta = ? WHERE categoriaid = ? AND usuarioid = ?");
    if($stmt->execute([$descricao, $tipo, $meta, $cat_id, $uid])) {
        $_SESSION['mensagem_flash'] = "Categoria atualizada!";
        $_SESSION['tipo_flash'] = "primary";
        echo "<script>window.location.href='index.php?pg=categorias';</script>";
        exit;
    }
}

// 3. EXCLUIR
if (isset($_GET['acao']) && $_GET['acao'] === 'excluir' && isset($_GET['id'])) {
    $id_excluir = $_GET['id'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM contas WHERE categoriaid = ? AND usuarioid = ?");
    $check->execute([$id_excluir, $uid]);
    
    if ($check->fetchColumn() > 0) {
        $_SESSION['mensagem_flash'] = "Erro: Existem lançamentos nesta categoria.";
        $_SESSION['tipo_flash'] = "danger";
    } else {
        $pdo->prepare("DELETE FROM categorias WHERE categoriaid = ? AND usuarioid = ?")->execute([$id_excluir, $uid]);
        $_SESSION['mensagem_flash'] = "Categoria removida!";
        $_SESSION['tipo_flash'] = "warning";
    }
    echo "<script>window.location.href='index.php?pg=categorias';</script>";
    exit;
}

// Feedback Flash
if (isset($_SESSION['mensagem_flash'])) {
    $msg = $_SESSION['mensagem_flash'];
    $tipo = $_SESSION['tipo_flash'];
    $icone = ($tipo == 'success') ? 'bi-check-circle-fill' : 'bi-info-circle-fill';
    $msg_html = "<div class='alert alert-{$tipo} border-0 shadow-sm py-3 rounded-4 mb-4 d-flex align-items-center animate-fade-in'><i class='bi {$icone} fs-4 me-3'></i><div>{$msg}</div><button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button></div>";
    unset($_SESSION['mensagem_flash'], $_SESSION['tipo_flash']);
} else { $msg_html = ""; }

// Buscar Categorias
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE usuarioid = ? ORDER BY categoriatipo DESC, categoriadescricao ASC");
$stmt->execute([$uid]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .animate-fade-in { animation: fadeInDown 0.4s ease-out; }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

    .card-add { background: #fff; border-radius: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.02); }

    .category-item {
        background: #fff; border-radius: 16px; padding: 15px; margin-bottom: 10px;
        display: flex; align-items: center; justify-content: space-between;
        transition: transform 0.2s, box-shadow 0.2s; border: 1px solid rgba(0,0,0,0.04);
    }
    .category-item:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); }

    .icon-box {
        width: 48px; height: 48px; border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; margin-right: 15px;
    }

    .btn-action { width: 35px; height: 35px; border-radius: 10px; border: none; display: flex; align-items: center; justify-content: center; transition: 0.2s; background: transparent; color: #94a3b8; }
    .btn-action:hover { background: #f1f5f9; color: #1e293b; }
</style>

<div class="container py-4">
    
    <?= $msg_html ?>

    <div class="d-flex align-items-center mb-4">
        <a href="index.php?pg=cadastros" class="btn btn-light rounded-circle shadow-sm me-3 border"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h4 class="fw-bold mb-0">Categorias</h4>
            <small class="text-muted">Gerencie grupos de receitas e despesas</small>
        </div>
        <span class="ms-auto badge bg-white border text-dark rounded-pill px-3 py-2 shadow-sm">
            <?= count($categorias) ?> Total
        </span>
    </div>

    <div class="card-add p-4 mb-4">
        <span class="d-block text-muted small fw-bold mb-3 text-uppercase">Nova Categoria</span>
        <form method="POST" class="row g-2 align-items-end">
            <input type="hidden" name="acao" value="adicionar">
            
            <div class="col-12 col-md-5">
                <label class="form-label small text-muted ms-1">Nome</label>
                <input type="text" name="descricao" class="form-control form-control-lg bg-light border-0" placeholder="Ex: Mercado" required>
            </div>
            
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted ms-1">Tipo</label>
                <select name="tipo" class="form-select form-select-lg bg-light border-0 fw-bold" required>
                    <option value="Despesa">Despesa</option>
                    <option value="Receita">Receita</option>
                    <option value="Ambos">Ambos</option>
                </select>
            </div>
            
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted ms-1">Meta (R$)</label>
                <input type="number" step="0.01" name="meta" class="form-control form-control-lg bg-light border-0" placeholder="0,00">
            </div>

            <div class="col-12 col-md-1">
                <button type="submit" class="btn btn-primary w-100 btn-lg rounded-3 shadow-sm h-100">
                    <i class="bi bi-plus-lg"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="mb-5">
        <?php if(empty($categorias)): ?>
            <div class="text-center py-5 text-muted opacity-50">
                <i class="bi bi-tags fs-1 mb-2 d-block"></i> <small>Nenhuma categoria cadastrada.</small>
            </div>
        <?php else: foreach($categorias as $c): 
                if($c['categoriatipo'] == 'Receita') { $bgIcon='#dcfce7'; $colorIcon='#16a34a'; $icon='bi-arrow-up-circle'; }
                elseif($c['categoriatipo'] == 'Despesa') { $bgIcon='#fee2e2'; $colorIcon='#dc2626'; $icon='bi-arrow-down-circle'; }
                else { $bgIcon='#e0f2fe'; $colorIcon='#0284c7'; $icon='bi-arrow-left-right'; }
            ?>
                <div class="category-item">
                    <div class="d-flex align-items-center">
                        <div class="icon-box" style="background: <?= $bgIcon ?>; color: <?= $colorIcon ?>;">
                            <i class="bi <?= $icon ?>"></i>
                        </div>
                        <div>
                            <span class="fw-bold fs-6 d-block text-dark"><?= $c['categoriadescricao'] ?></span>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <small class="text-muted" style="font-size: 0.75rem;"><?= $c['categoriatipo'] ?></small>
                                <?php if($c['categoriameta'] > 0): ?>
                                    <span class="badge bg-light text-muted border fw-normal" style="font-size: 0.65rem;">Meta: R$ <?= number_format($c['categoriameta'],2,',','.') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <button class="btn-action" onclick="abrirModalEditar(<?= $c['categoriaid'] ?>, '<?= $c['categoriadescricao'] ?>', '<?= $c['categoriatipo'] ?>', '<?= $c['categoriameta'] ?>')"><i class="bi bi-pencil-fill"></i></button>
                        <a href="?pg=categorias&acao=excluir&id=<?= $c['categoriaid'] ?>" class="btn-action text-danger" onclick="return confirm('Excluir?')"><i class="bi bi-trash"></i></a>
                    </div>
                </div>
            <?php endforeach; endif; ?>
    </div>
</div>

<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Editar Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="categoria_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nome</label>
                        <input type="text" name="descricao" id="edit_descricao" class="form-control form-control-lg bg-light border-0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Tipo</label>
                        <select name="tipo" id="edit_tipo" class="form-select form-select-lg bg-light border-0">
                            <option value="Despesa">Despesa</option>
                            <option value="Receita">Receita</option>
                            <option value="Ambos">Ambos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Meta (R$)</label>
                        <input type="number" step="0.01" name="meta" id="edit_meta" class="form-control form-control-lg bg-light border-0">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 py-3 rounded-4 fw-bold shadow-sm">SALVAR</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function abrirModalEditar(id, descricao, tipo, meta) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_descricao').value = descricao;
        document.getElementById('edit_tipo').value = tipo;
        document.getElementById('edit_meta').value = meta;
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditar')).show();
    }
</script>