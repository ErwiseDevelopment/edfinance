<?php
// app/pages/salvar_conta_engine.php

// 1. Limpeza de Buffer
if (ob_get_length()) ob_clean();

// 2. Proteção de Acesso
if (!defined('APP_PATH')) exit;

if (!isset($_SESSION['usuarioid'])) {
    header("Location: index.php?pg=login");
    exit;
}

$uid = $_SESSION['usuarioid'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        // --- A. TRATAMENTO DE VALOR ---
        $valor_raw = $_POST['contavalor']; 
        
        if (empty($valor_raw)) {
            $contavalor = 0.00;
        } else {
            $temp_val = str_replace(['R$', ' '], '', $valor_raw);
            if (strpos($temp_val, ',') !== false) {
                $temp_val = str_replace('.', '', $temp_val); 
                $temp_val = str_replace(',', '.', $temp_val); 
            } 
            $contavalor = (float)$temp_val;
        }

        // --- B. COLETA DE DADOS ---
        $contadescricao = trim($_POST['contadescricao']);
        $contatipo = $_POST['contatipo'];
        $categoriaid = $_POST['categoriaid'];
        $contavencimento = $_POST['contavencimento'];
        
        $contafixa = isset($_POST['contafixa']) ? 1 : 0;
        $manter_dados = $_POST['manter_dados'] ?? '0';

        // Cartão (Receita não tem cartão)
        $cartoid = ($contatipo === 'Entrada') ? null : ($_POST['cartoid'] ?? null);
        if (empty($cartoid)) $cartoid = null;

        $parcelas_input = (int)($_POST['contaparcela_total'] ?? 1);
        if ($parcelas_input < 1) $parcelas_input = 1;

        // --- C. LÓGICA DE REPETIÇÃO E VALOR ---
        
        $qtd_lancamentos = 1;
        $grupo_id = null; 

        // O valor base é o digitado (se for parcela, já é o valor da parcela)
        $valor_final = $contavalor; 

        if ($contafixa == 1) {
            // CENÁRIO 1: CONTA FIXA (Recorrente)
            // Gera 12 meses com o mesmo valor
            $qtd_lancamentos = 12; 
            $grupo_id = uniqid('fix_');

        } elseif ($parcelas_input > 1) {
            // CENÁRIO 2: PARCELADO (Cartão)
            // Gera N parcelas. O valor digitado JÁ É O VALOR DA PARCELA.
            $qtd_lancamentos = $parcelas_input;
            
            // CORREÇÃO AQUI: NÃO DIVIDE MAIS O VALOR
            // Antes: $valor_final = $contavalor / $parcelas_input;
            // Agora: Mantém o valor digitado, pois ele representa "1x de..."
            $valor_final = $contavalor; 
            
            $grupo_id = uniqid('parc_');
        }

        // --- D. PREPARAÇÃO DO BANCO ---
        $pdo->beginTransaction();

        $dia_fechamento = 1;
        if ($cartoid) {
            $stmt_c = $pdo->prepare("SELECT cartofechamento FROM cartoes WHERE cartoid = ?");
            $stmt_c->execute([$cartoid]);
            $res = $stmt_c->fetch();
            if ($res) $dia_fechamento = (int)$res['cartofechamento'];
        }

        // --- E. LOOP DE INSERÇÃO ---
        $data_base = new DateTime($contavencimento);

        for ($i = 1; $i <= $qtd_lancamentos; $i++) {
            
            // 1. Data Vencimento
            $data_iteracao = clone $data_base;
            if ($i > 1) {
                $data_iteracao->modify("+" . ($i - 1) . " months");
            }
            
            $vencimento_db = $data_iteracao->format('Y-m-d');
            $competencia_db = $data_iteracao->format('Y-m');

            // 2. Data Fatura
            $fatura_db = null;
            if ($cartoid) {
                $dia_compra = (int)$data_iteracao->format('d');
                $data_fatura_calc = clone $data_iteracao;
                
                if ($dia_compra >= $dia_fechamento) {
                    $data_fatura_calc->modify('first day of next month');
                }
                $fatura_db = $data_fatura_calc->format('Y-m');
            }

            // 3. Descrição
            $descricao_final = $contadescricao;
            if ($contafixa == 0 && $qtd_lancamentos > 1) {
                $descricao_final .= " ($i/$qtd_lancamentos)";
            }

            // 4. Inserção
            $p_num   = ($contafixa == 1) ? 1 : $i;
            $p_total = ($contafixa == 1) ? 1 : $qtd_lancamentos;

            $sql = "INSERT INTO contas (
                usuarioid, categoriaid, contadescricao, contavalor, 
                contavencimento, contacompetencia, competenciafatura, 
                contatipo, contafixa, cartoid, 
                contaparcela_num, contaparcela_total, contagrupoid, contasituacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pendente')";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $uid, $categoriaid, $descricao_final, $valor_final,
                $vencimento_db, $competencia_db, $fatura_db,
                $contatipo, $contafixa, $cartoid,
                $p_num, $p_total, $grupo_id
            ]);
        }

        $pdo->commit();

        $_SESSION['mensagem_flash'] = "Lançamento salvo com sucesso!";
        $_SESSION['tipo_flash'] = "success";

        if ($manter_dados == '1') {
            $query = http_build_query([
                'tipo' => $contatipo,
                'cat' => $categoriaid,
                'car' => $cartoid ?? '',
                'fixa' => $contafixa
            ]);
            header("Location: index.php?pg=cadastro_conta&" . $query);
        } else {
            header("Location: index.php?pg=dashboard");
        }
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['mensagem_flash'] = "Erro ao salvar: " . $e->getMessage();
        $_SESSION['tipo_flash'] = "danger";
        header("Location: index.php?pg=cadastro_conta");
        exit;
    }
}
?>