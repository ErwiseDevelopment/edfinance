<?php
// app/pages/salvar_cartao_engine.php

if (!defined('APP_PATH')) exit; // Garante segurança se incluído via index

$uid = $_SESSION['usuarioid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Pega os dados com os nomes corretos do formulário HTML
    $id = $_POST['id'] ?? null; // Campo oculto
    $nome = $_POST['nome'];
    $fechamento = $_POST['fechamento'];
    $vencimento = $_POST['vencimento'];
    $limite = $_POST['limite'] ?? 0;
    $cor = $_POST['cor'] ?? '#1e293b';

    // Se o limite vier vazio, define como 0
    if($limite == "") $limite = 0;

    try {
        if (!empty($id)) {
            // --- EDITAR (UPDATE) ---
            $sql = "UPDATE cartoes SET cartonome=?, cartofechamento=?, cartovencimento=?, cartolimite=?, cartocor=? WHERE cartoid=? AND usuarioid=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $fechamento, $vencimento, $limite, $cor, $id, $uid]);
            
            $_SESSION['mensagem_flash'] = "Cartão atualizado com sucesso!";
            $_SESSION['tipo_flash'] = "success";
        } else {
            // --- NOVO (INSERT) ---
            $sql = "INSERT INTO cartoes (usuarioid, cartonome, cartofechamento, cartovencimento, cartolimite, cartocor) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$uid, $nome, $fechamento, $vencimento, $limite, $cor]);

            $_SESSION['mensagem_flash'] = "Cartão criado com sucesso!";
            $_SESSION['tipo_flash'] = "success";
        }

    } catch (PDOException $e) {
        $_SESSION['mensagem_flash'] = "Erro ao salvar: " . $e->getMessage();
        $_SESSION['tipo_flash'] = "danger";
    }

    // Redireciona de volta para a lista
    header("Location: index.php?pg=cadastro_cartao");
    exit;
}
?>