<?php
// app/pages/salvar_conta_engine.php

// 1. Proteção
if (!defined('APP_PATH')) exit;

// 2. Não precisa carregar banco nem session (o index.php já fez)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = $_SESSION['usuarioid'];
    $contadescricao = $_POST['contadescricao'];
    
    // Tratamento Valor (Remove R$ e troca vírgula por ponto)
    $contavalor = $_POST['contavalor']; 
    if (empty($contavalor)) {
        $contavalor = 0.00;
    } else {
        // Se vier formatado (1.200,50), limpa tudo
        $contavalor = str_replace(['R$', ' ', '.'], '', $contavalor);
        $contavalor = str_replace(',', '.', $contavalor);
    }

    $contatipo = $_POST['contatipo'];
    $categoriaid = $_POST['categoriaid'];
    $contavencimento = $_POST['contavencimento'];
    $cartoid = !empty($_POST['cartoid']) ? $_POST['cartoid'] : null;
    $contafixa = isset($_POST['contafixa']) ? 1 : 0;
    
    $parcelas_total = (int)($_POST['contaparcela_total'] ?? 1);
    if ($parcelas_total < 1) $parcelas_total = 1;

    try {
        $pdo->beginTransaction();

        // 1. BUSCAR FECHAMENTO E VENCIMENTO DO CARTÃO
        $dia_fechamento = 30; 
        $dia_vencimento = 30; 
        
        if ($cartoid) {
            $stmt_cartao = $pdo->prepare("SELECT cartofechamento, cartovencimento FROM cartoes WHERE cartoid = ? AND usuarioid = ?");
            $stmt_cartao->execute([$cartoid, $uid]);
            $res = $stmt_cartao->fetch();
            if ($res) {
                $dia_fechamento = (int)$res['cartofechamento'];
                $dia_vencimento = (int)$res['cartovencimento'];
            }
        }

        // Se for fixa, repete 12 meses (ou o que definir). Se parcelado, usa o total.
        $limite_repeticao = ($contafixa == 1 && $parcelas_total <= 1) ? 12 : $parcelas_total;
        
        // Gerar ID de Agrupamento Único para este lote
        // Isso permite apagar todas as parcelas juntas depois
        $grupo_id = uniqid('grp_');

        for ($i = 1; $i <= $limite_repeticao; $i++) {
            
            $data = new DateTime($contavencimento);
            
            // Avança os meses para cada parcela/repetição
            if ($i > 1) {
                $data->modify("+" . ($i - 1) . " months");
            }
            
            $vencimento_db = $data->format('Y-m-d');
            $conta_competencia = $data->format('Y-m'); 

            // --- 2. LÓGICA DE FATURA (Backend) ---
            $competencia_fatura = null;

            if ($cartoid) {
                $data_calc = clone $data;
                $dia_compra = (int)$data_calc->format('d');
                $meses_add = 0;

                // A. Regra do Ciclo (Comprou depois que fechou?)
                if ($dia_compra >= $dia_fechamento) {
                    $meses_add = 1;
                }

                // B. Regra do Vencimento (Se vence antes de fechar, joga pro próximo)
                if ($dia_vencimento < $dia_fechamento) {
                    $meses_add++;
                }

                if($meses_add > 0) {
                    $data_calc->modify("first day of +$meses_add months");
                }
                
                $competencia_fatura = $data_calc->format('Y-m');
            }

            // Descrição da Parcela
            $desc_final = ($parcelas_total > 1) ? "$contadescricao ($i/$parcelas_total)" : $contadescricao;

            $sql = "INSERT INTO contas (
                usuarioid, categoriaid, contadescricao, contavalor, 
                contavencimento, contacompetencia, competenciafatura, contatipo, 
                contafixa, cartoid, contaparcela_num, contaparcela_total, contasituacao, contagrupoid
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendente', ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $uid, $categoriaid, $desc_final, $contavalor, 
                $vencimento_db, $conta_competencia, $competencia_fatura, 
                $contatipo, $contafixa, $cartoid, $i, $parcelas_total, $grupo_id
            ]);
        }

        $pdo->commit();

        // Feedback via Sessão
        $_SESSION['mensagem_flash'] = "Lançamento realizado com sucesso!";
        $_SESSION['tipo_flash'] = "success";

        // Redirecionamento
        if (isset($_POST['manter_dados']) && $_POST['manter_dados'] == '1') {
            // Se clicou em "Salvar +", volta pro formulário com os dados pré-preenchidos
            $params = http_build_query([
                'msg'  => 'sucesso', 
                'tipo' => $contatipo, 
                'cat'  => $categoriaid,
                'car'  => $cartoid ?? '', 
                'venc' => $contavencimento, 
                'fixa' => $contafixa
            ]);
            header("Location: index.php?pg=cadastro_conta&" . $params);
        } else {
            // Se clicou em "Confirmar", vai pro Dashboard
            header("Location: index.php?pg=dashboard");
        }
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Erro ao salvar: " . $e->getMessage());
    }
}
?>