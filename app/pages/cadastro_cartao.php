<?php
// app/pages/cadastro_cartao.php
if (!defined('APP_PATH')) exit;

$uid = $_SESSION['usuarioid'];

// Mensagem Flash (Feedback de Sucesso/Erro)
$mensagem = "";
if (isset($_SESSION['mensagem_flash'])) {
    $msg = $_SESSION['mensagem_flash'];
    $tipo = $_SESSION['tipo_flash'] ?? 'success';
    $icone = ($tipo == 'success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    $mensagem = "<div class='alert alert-{$tipo} border-0 shadow-sm py-3 rounded-4 mb-4 d-flex align-items-center animate-fade-in'><i class='bi {$icone} fs-4 me-3'></i><div>{$msg}</div><button type='button' class='btn-close ms-auto' data-bs-dismiss='alert'></button></div>";
    unset($_SESSION['mensagem_flash']); unset($_SESSION['tipo_flash']);
}

// Lista Cartões do Banco de Dados
$stmt = $pdo->prepare("SELECT * FROM cartoes WHERE usuarioid = ? ORDER BY cartonome ASC");
$stmt->execute([$uid]);
$cartoes = $stmt->fetchAll();
?>

<style>
    .card-plastic { color: white; border-radius: 24px; padding: 25px; position: relative; overflow: hidden; min-height: 200px; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, box-shadow 0.2s; border: 1px solid rgba(255,255,255,0.2); cursor: pointer; box-shadow: 0 10px 30px -10px rgba(0,0,0,0.3); }
    .card-plastic:hover { transform: translateY(-5px); }
    .card-plastic::before { content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%); pointer-events: none; }
    .card-chip { width: 45px; height: 35px; background: linear-gradient(135deg, #ffd700 0%, #d4af37 100%); border-radius: 8px; margin-bottom: 20px; opacity: 0.9; position: relative; z-index: 2; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .card-actions { position: absolute; top: 20px; right: 20px; opacity: 0; transition: 0.2s; display: flex; gap: 8px; z-index: 5; }
    .card-plastic:hover .card-actions { opacity: 1; }
    .btn-card-action { width: 36px; height: 36px; border-radius: 50%; background: rgba(0,0,0,0.3); color: white; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); text-decoration: none; border: none; transition: 0.2s; }
    .btn-card-action:hover { background: white; color: #1e293b; transform: scale(1.1); }
    
    /* Cores e Seleção */
    .color-options-grid { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
    .color-radio { display: none; } 
    .color-circle { width: 40px; height: 40px; border-radius: 50%; cursor: pointer; border: 3px solid transparent; transition: all 0.2s; position: relative; }
    /* Efeito ao selecionar */
    .color-radio:checked + .color-circle { transform: scale(1.1); border-color: white; box-shadow: 0 0 0 2px #1e293b; }
    
    .empty-state { border: 2px dashed #e2e8f0; border-radius: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px; color: #94a3b8; cursor: pointer; transition: 0.2s; min-height: 200px; background: #f8fafc; }
    .empty-state:hover { border-color: #4361ee; color: #4361ee; background: #eef2ff; }
</style>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center">
            <a href="index.php?pg=cadastros" class="btn btn-light rounded-circle shadow-sm me-3 border"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h4 class="fw-bold mb-0">Meus Cartões</h4>
                <small class="text-muted">Gerencie seus limites e vencimentos</small>
            </div>
        </div>
        <button onclick="abrirModalNovo()" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
            <i class="bi bi-plus-lg me-2"></i> Novo Cartão
        </button>
    </div>

    <?= $mensagem ?>

    <div class="row g-4">
        <?php foreach($cartoes as $c): 
            $cor = $c['cartocor'] ?? '#1e293b'; 
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-plastic" style="background: <?= $cor ?>;" onclick='abrirModalEditar(<?= json_encode($c) ?>)'>
                    <div class="card-actions" onclick="event.stopPropagation()">
                        <button onclick='abrirModalEditar(<?= json_encode($c) ?>)' class="btn-card-action"><i class="bi bi-pencil-fill"></i></button>
                        <a href="index.php?pg=acoes_cartao&acao=excluir&id=<?= $c['cartoid'] ?>" class="btn-card-action" style="background: rgba(239, 68, 68, 0.8);" onclick="return confirm('Excluir este cartão?')"><i class="bi bi-trash-fill"></i></a>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="card-chip"></div>
                            <i class="bi bi-wifi fs-3 opacity-50"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-truncate text-shadow"><?= $c['cartonome'] ?></h5>
                    </div>
                    <div class="row mt-4 opacity-75 small">
                        <div class="col-4">
                            <span class="d-block" style="font-size: 0.65rem;">FECHA</span>
                            <span class="fw-bold fs-6"><?= $c['cartofechamento'] ?></span>
                        </div>
                        <div class="col-4">
                            <span class="d-block" style="font-size: 0.65rem;">VENCE</span>
                            <span class="fw-bold fs-6"><?= $c['cartovencimento'] ?></span>
                        </div>
                        <div class="col-4 text-end">
                            <span class="d-block" style="font-size: 0.65rem;">LIMITE</span>
                            <span class="fw-bold fs-6">R$ <?= number_format($c['cartolimite'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="col-md-6 col-lg-4">
            <div class="empty-state" onclick="abrirModalNovo()">
                <div class="bg-white p-3 rounded-circle shadow-sm mb-3 text-primary">
                    <i class="bi bi-plus-lg fs-3"></i>
                </div>
                <h6 class="fw-bold m-0">Adicionar Cartão</h6>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCartao" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 24px;">
            <form action="index.php?pg=salvar_cartao_engine" method="POST">
                <input type="hidden" name="id" id="cartao_id">
                
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="fw-bold m-0" id="modalTitle">Novo Cartão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    
                    <div class="mb-3 text-center">
                        <label class="form-label small fw-bold text-muted mb-3">COR DO CARTÃO</label>
                        <div class="color-options-grid">
                            <label title="Black / C6 / XP / Nomad">
                                <input type="radio" name="cor" value="#1e293b" class="color-radio" checked>
                                <div class="color-circle" style="background: #1e293b;"></div>
                            </label>
                            <label title="Nubank / Vivo">
                                <input type="radio" name="cor" value="#820ad1" class="color-radio">
                                <div class="color-circle" style="background: #820ad1;"></div>
                            </label>
                            <label title="Inter / BMG">
                                <input type="radio" name="cor" value="#ff7a00" class="color-radio">
                                <div class="color-circle" style="background: #ff7a00;"></div>
                            </label>
                            <label title="Santander / Bradesco">
                                <input type="radio" name="cor" value="#cc092f" class="color-radio">
                                <div class="color-circle" style="background: #cc092f;"></div>
                            </label>
                            <label title="Itaú / Caixa / Azul">
                                <input type="radio" name="cor" value="#005aa5" class="color-radio">
                                <div class="color-circle" style="background: #005aa5;"></div>
                            </label>
                            <label title="PicPay / Next / Sicredi">
                                <input type="radio" name="cor" value="#00b46e" class="color-radio">
                                <div class="color-circle" style="background: #00b46e;"></div>
                            </label>
                            <label title="Rappi / Outros">
                                <input type="radio" name="cor" value="#d63384" class="color-radio">
                                <div class="color-circle" style="background: #d63384;"></div>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">NOME / APELIDO</label>
                        <input type="text" name="nome" id="cartao_nome" 
                               class="form-control form-control-lg bg-light border-0" 
                               placeholder="Digite para buscar (Ex: Nubank)" 
                               list="lista_bancos"
                               oninput="detectarCorAutomatica(this.value)"
                               required>

                        <datalist id="lista_bancos">
                            <option value="Nubank">
                            <option value="Inter">
                            <option value="Santander">
                            <option value="Bradesco">
                            <option value="Itaú">
                            <option value="Caixa">
                            <option value="C6 Bank">
                            <option value="XP Investimentos">
                            <option value="PicPay">
                            <option value="PagBank">
                            <option value="Banco do Brasil">
                            <option value="BTG Pactual">
                            <option value="Nomad">
                            <option value="Wise">
                            <option value="Neon">
                            <option value="Next">
                            <option value="Sofisa">
                            <option value="Sicredi">
                            <option value="Sicoob">
                            <option value="Mercado Pago">
                            <option value="Porto Seguro">
                            <option value="Azul Infinite">
                            <option value="Latam Pass">
                        </datalist>
                    </div>

                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">FECHAMENTO (DIA)</label>
                            <input type="number" name="fechamento" id="cartao_fechamento" class="form-control form-control-lg bg-light border-0" placeholder="Ex: 5" min="1" max="31" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold text-muted">VENCIMENTO (DIA)</label>
                            <input type="number" name="vencimento" id="cartao_vencimento" class="form-control form-control-lg bg-light border-0" placeholder="Ex: 15" min="1" max="31" required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-bold text-muted">LIMITE TOTAL (R$)</label>
                        <input type="number" step="0.01" name="limite" id="cartao_limite" class="form-control form-control-lg bg-light border-0" placeholder="0,00">
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
    // --- LÓGICA DE CORES INTELIGENTE ---
    // Mapeia palavras-chave para as cores hexadecimais dos inputs radio
    const mapaCores = {
        // ROXO
        'nubank': '#820ad1', 'nu ': '#820ad1', 'vivo': '#820ad1', 'easynvest': '#820ad1',
        
        // LARANJA
        'inter': '#ff7a00', 'bmg': '#ff7a00', 'shopee': '#ff7a00',
        
        // VERMELHO
        'santander': '#cc092f', 'bradesco': '#cc092f', 'claro': '#cc092f', 'americanas': '#cc092f',
        
        // AZUL
        'itaú': '#005aa5', 'itau': '#005aa5', 'caixa': '#005aa5', 'azul': '#005aa5', 
        'porto': '#005aa5', 'credicard': '#005aa5', 'btg': '#005aa5', 'bb': '#005aa5', 'brasil': '#005aa5',
        
        // VERDE
        'picpay': '#00b46e', 'stone': '#00b46e', 'pagbank': '#00b46e', 'next': '#00b46e',
        'sicredi': '#00b46e', 'sicoob': '#00b46e', 'unicred': '#00b46e', 'neon': '#00b46e',
        
        // PRETO/CINZA (Padrão para Cartões Black/Infinite ou bancos digitais sóbrios)
        'c6': '#1e293b', 'xp': '#1e293b', 'black': '#1e293b', 'infinite': '#1e293b', 
        'nomad': '#1e293b', 'wise': '#1e293b', 'avenue': '#1e293b', 'carbon': '#1e293b', 'ultravioleta': '#1e293b',
        
        // ROSA
        'rappi': '#d63384'
    };

    function detectarCorAutomatica(texto) {
        if(!texto) return;
        const nome = texto.toLowerCase();

        // Verifica cada chave do mapa
        for (const [chave, corHex] of Object.entries(mapaCores)) {
            if (nome.includes(chave)) {
                const radio = document.querySelector(`input[name="cor"][value="${corHex}"]`);
                if (radio) {
                    radio.checked = true;
                    // Pequena animação para mostrar que o sistema escolheu
                    const circulo = radio.parentElement.querySelector('.color-circle');
                    circulo.style.transform = "scale(0.9)";
                    setTimeout(() => circulo.style.transform = "", 150);
                }
                break; // Para na primeira correspondência
            }
        }
    }

    // --- FUNÇÕES DA MODAL ---
    function getModal() {
        return bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCartao'));
    }

    function abrirModalNovo() {
        document.getElementById('modalTitle').innerText = "Novo Cartão";
        document.getElementById('cartao_id').value = "";
        document.getElementById('cartao_nome').value = "";
        document.getElementById('cartao_fechamento').value = "";
        document.getElementById('cartao_vencimento').value = "";
        document.getElementById('cartao_limite').value = "";
        
        // Reseta cor para o padrão (primeiro radio)
        const radios = document.querySelectorAll('.color-radio');
        if(radios.length > 0) radios[0].checked = true;
        
        getModal().show();
    }

    function abrirModalEditar(cartao) {
        document.getElementById('modalTitle').innerText = "Editar Cartão";
        document.getElementById('cartao_id').value = cartao.cartoid;
        document.getElementById('cartao_nome').value = cartao.cartonome;
        document.getElementById('cartao_fechamento').value = cartao.cartofechamento;
        document.getElementById('cartao_vencimento').value = cartao.cartovencimento;
        document.getElementById('cartao_limite').value = cartao.cartolimite;

        // Seleciona a cor salva
        const corSalva = cartao.cartocor || '#1e293b';
        const radio = document.querySelector(`input[name="cor"][value="${corSalva}"]`);
        if (radio) {
            radio.checked = true;
        }

        getModal().show();
    }
</script>