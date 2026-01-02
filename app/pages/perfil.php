<?php
// app/pages/perfil.php

if (!defined('APP_PATH')) exit; // Proteção se usar rota, se não, use o require abaixo
// require_once "../config/database.php"; 
// require_once "../includes/header.php";

$uid = $_SESSION['usuarioid'];
$erro = "";
$sucesso = "";

// 1. Busca os dados atuais do usuário para preencher o formulário
$stmt = $pdo->prepare("SELECT usuarionome, usuarioemail, usuariosenha FROM usuarios WHERE usuarioid = ?");
$stmt->execute([$uid]);
$dados_user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados_user) {
    echo "Erro ao carregar perfil.";
    exit;
}

// 2. Processa o Formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirma_senha = $_POST['confirma_senha'];

    // Verifica a SENHA ATUAL (Obrigatória para qualquer mudança)
    if (!password_verify($senha_atual, $dados_user['usuariosenha'])) {
        $erro = "A senha atual está incorreta. Nenhuma alteração foi salva.";
    } else {
        // Verifica se o E-MAIL já existe (caso tenha mudado)
        if ($email !== $dados_user['usuarioemail']) {
            $check = $pdo->prepare("SELECT usuarioid FROM usuarios WHERE usuarioemail = ? AND usuarioid != ?");
            $check->execute([$email, $uid]);
            if ($check->rowCount() > 0) {
                $erro = "Este e-mail já está em uso por outro usuário.";
            }
        }

        if (empty($erro)) {
            // Lógica para montar o UPDATE
            // Se o usuário preencheu "Nova Senha", validamos e incluímos no update
            if (!empty($nova_senha)) {
                if (strlen($nova_senha) < 8 || !preg_match("/[0-9]/", $nova_senha) || !preg_match("/[\W]/", $nova_senha)) {
                    $erro = "A nova senha deve ter 8 caracteres, números e símbolos.";
                } elseif ($nova_senha !== $confirma_senha) {
                    $erro = "A confirmação da nova senha não confere.";
                } else {
                    // Tudo certo com a senha, vamos hashear
                    $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    
                    // Update COM senha
                    $sql = "UPDATE usuarios SET usuarionome = ?, usuarioemail = ?, usuariosenha = ? WHERE usuarioid = ?";
                    $update = $pdo->prepare($sql);
                    $resultado = $update->execute([$nome, $email, $novo_hash, $uid]);
                }
            } else {
                // Update SEM senha (apenas dados cadastrais)
                $sql = "UPDATE usuarios SET usuarionome = ?, usuarioemail = ? WHERE usuarioid = ?";
                $update = $pdo->prepare($sql);
                $resultado = $update->execute([$nome, $email, $uid]);
            }

            // Finalização
            if (empty($erro) && isset($resultado) && $resultado) {
                $sucesso = "Perfil atualizado com sucesso!";
                
                // Atualiza a sessão para refletir o novo nome/email imediatamente
                $_SESSION['usuarionome'] = $nome;
                $_SESSION['usuarioemail'] = $email;
                
                // Atualiza os dados locais para exibir no form
                $dados_user['usuarionome'] = $nome;
                $dados_user['usuarioemail'] = $email;
            } elseif (empty($erro)) {
                $erro = "Erro ao atualizar banco de dados.";
            }
        }
    }
}
?>

<style>
    .card-profile { background: #fff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.03); }
    .section-title { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-bottom: 15px; }
    .form-control-lg { font-size: 0.95rem; border-radius: 12px; }
    .btn-save { border-radius: 12px; padding: 12px; font-weight: 700; letter-spacing: 0.5px; }
</style>

<div class="container py-5">
    
    <div class="d-flex align-items-center mb-4">
        <a href="index.php?pg=cadastros" class="btn btn-light rounded-circle shadow-sm me-3 border"><i class="bi bi-arrow-left"></i></a>
        <h4 class="fw-bold m-0">Meu Perfil</h4>
    </div>

    <div class="card card-profile border-0 p-4 mx-auto" style="max-width: 600px;">
        
        <?php if($erro): ?> 
            <div class="alert alert-danger border-0 shadow-sm rounded-3 d-flex align-items-center mb-4">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $erro ?>
            </div> 
        <?php endif; ?>
        
        <?php if($sucesso): ?> 
            <div class="alert alert-success border-0 shadow-sm rounded-3 d-flex align-items-center mb-4">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $sucesso ?>
            </div> 
        <?php endif; ?>

        <form method="POST">
            
            <div class="section-title">Dados Pessoais</div>
            
            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Nome Completo</label>
                <input type="text" name="nome" class="form-control form-control-lg bg-light border-0" 
                       value="<?= htmlspecialchars($dados_user['usuarionome']) ?>" required>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">E-mail de Acesso</label>
                <input type="email" name="email" class="form-control form-control-lg bg-light border-0" 
                       value="<?= htmlspecialchars($dados_user['usuarioemail']) ?>" required>
            </div>

            <hr class="my-4 opacity-10">

            <div class="section-title">Segurança (Opcional)</div>
            <div class="alert alert-light border small text-muted mb-3">
                <i class="bi bi-info-circle me-1"></i> Preencha abaixo apenas se desejar trocar sua senha.
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Nova Senha</label>
                    <input type="password" name="nova_senha" class="form-control bg-light border-0">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Confirmar Nova</label>
                    <input type="password" name="confirma_senha" class="form-control bg-light border-0">
                </div>
            </div>

            <hr class="my-4 opacity-10">

            <div class="bg-warning bg-opacity-10 p-3 rounded-3 mb-3 border border-warning border-opacity-25">
                <label class="form-label small fw-bold text-dark mb-1">
                    <i class="bi bi-lock-fill me-1"></i> Senha Atual (Obrigatório)
                </label>
                <div class="input-group">
                    <input type="password" name="senha_atual" id="senhaAtual" class="form-control border-0" placeholder="Digite sua senha atual para salvar" required>
                    <button class="btn btn-white border-0 bg-white" type="button" onclick="togglePass()"><i class="bi bi-eye"></i></button>
                </div>
                <div class="form-text text-muted small mt-1">Necessário para confirmar qualquer alteração.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-save shadow-sm">
                Salvar Alterações
            </button>

        </form>
    </div>
</div>

<script>
    function togglePass() {
        const input = document.getElementById('senhaAtual');
        input.type = input.type === 'password' ? 'text' : 'password';
    }
</script>