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
        $contatipo      = $_POST['contatipo'];
        $categoriaid    = $_POST['categoriaid'];
        $contavencimento = $_POST['contavencimento'];
        
        $contafixa      = isset($_POST['contafixa']) ? 1 : 0;
        $manter_dados   = $_POST['manter_dados'] ?? '0';

        // Captura Robusta do Cartão
        $cartoid = null;
        if ($contatipo === 'Saída') {
            if (isset($_POST['cartoid']) && $_POST['cartoid'] !== '') {
                $cartoid = $_POST['cartoid'];
            }
        }

        // Parcelas
        $parcelas_input = (int)($_POST['contaparcela_total'] ?? 1);
        if ($parcelas_input < 1) $parcelas_input = 1;

        // --- C. LÓGICA DE REPETIÇÃO ---
        $qtd_lancamentos = 1;
        $grupo_id = null; 
        $valor_final = $contavalor; 

        if ($contafixa == 1) {
            $qtd_lancamentos = 12; 
            $grupo_id = uniqid('fix_');
        } elseif ($parcelas_input > 1) {
            $qtd_lancamentos = $parcelas_input;
            $valor_final = $contavalor; 
            $grupo_id = uniqid('parc_');
        }

        // --- D. PREPARAÇÃO DO BANCO ---
        $pdo->beginTransaction();

        // 1. BUSCA DADOS DO CARTÃO (FECHAMENTO E VENCIMENTO)
        $dia_fechamento = 30; // Padrão seguro
        $dia_vencimento = 10; // Padrão seguro
        
        if (!empty($cartoid)) {
            $stmt_c = $pdo->prepare("SELECT cartofechamento, cartovencimento FROM cartoes WHERE cartoid = ?");
            $stmt_c->execute([$cartoid]);
            $res = $stmt_c->fetch();
            if ($res) {
                $dia_fechamento = (int)$res['cartofechamento'];
                $dia_vencimento = (int)$res['cartovencimento'];
            }
        }

        // --- E. LOOP DE INSERÇÃO ---
        $data_base = new DateTime($contavencimento);

        for ($i = 1; $i <= $qtd_lancamentos; $i++) {
            
            // Data da Parcela/Ocorrência
            $data_iteracao = clone $data_base;
            if ($i > 1) {
                $data_iteracao->modify("+" . ($i - 1) . " months");
            }
            
            $vencimento_db = $data_iteracao->format('Y-m-d');
            $competencia_db = $data_iteracao->format('Y-m');

            // --- LÓGICA DE COMPETÊNCIA DA FATURA (CORRIGIDA) ---
            $fatura_db = null;
            
            if (!empty($cartoid)) {
                $dia_compra = (int)$data_iteracao->format('d');
                
                // Objeto para manipular a data da fatura
                $data_fatura_calc = clone $data_iteracao;
                
                // REGRA 1: Compra após o fechamento? Joga pro próximo mês.
                if ($dia_compra >= $dia_fechamento) {
                    $data_fatura_calc->modify('first day of next month');
                }

                // REGRA 2 (NOVA): O cartão "vira" o mês? 
                // Se o dia do vencimento for menor que o dia do fechamento (Ex: Vence dia 05, Fecha dia 25),
                // significa que a fatura desse ciclo só é paga no mês seguinte.
                if ($dia_vencimento < $dia_fechamento) {
                    $data_fatura_calc->modify('first day of next month');
                }

                $fatura_db = $data_fatura_calc->format('Y-m');
            } else {
                // Sem cartão, a competência é o próprio mês
                $fatura_db = $competencia_db; 
            }

            // Descrição
            $descricao_final = $contadescricao;
            if ($contafixa == 0 && $qtd_lancamentos > 1) {
                $descricao_final .= " ($i/$qtd_lancamentos)";
            }

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