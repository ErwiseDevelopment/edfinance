<?php
// app/pages/salvar_conta_engine.php

// 1. Limpeza de Buffer (Essencial para evitar erro 'headers already sent')
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
        // --- A. TRATAMENTO INTELIGENTE DE VALOR ---
        // Resolve o problema de 4.073,70 virar 407370.00
        $valor_raw = $_POST['contavalor']; 
        
        if (empty($valor_raw)) {
            $contavalor = 0.00;
        } else {
            // Remove R$ e espaços
            $temp_val = str_replace(['R$', ' '], '', $valor_raw);
            
            // Se tiver vírgula, assume formato BR (1.000,00)
            if (strpos($temp_val, ',') !== false) {
                $temp_val = str_replace('.', '', $temp_val); // Remove ponto de milhar
                $temp_val = str_replace(',', '.', $temp_val); // Troca vírgula por ponto
            } 
            // Se não tiver vírgula, assume formato US ou limpo (1000.00 ou 1000)
            
            $contavalor = (float)$temp_val;
        }

        // --- B. COLETA DE DADOS ---
        $contadescricao = trim($_POST['contadescricao']);
        $contatipo = $_POST['contatipo']; // Entrada ou Saída
        $categoriaid = $_POST['categoriaid'];
        $contavencimento = $_POST['contavencimento'];
        
        $contafixa = isset($_POST['contafixa']) ? 1 : 0;
        $manter_dados = $_POST['manter_dados'] ?? '0';

        // Lógica: Receita (Entrada) NUNCA tem cartão
        $cartoid = ($contatipo === 'Entrada') ? null : ($_POST['cartoid'] ?? null);
        if (empty($cartoid)) $cartoid = null;

        // Lógica: Se é fixa, parcelas = 1 (pois é recorrente, não parcelada)
        $parcelas_total = (int)($_POST['contaparcela_total'] ?? 1);
        if ($contafixa == 1 || $parcelas_total < 1) $parcelas_total = 1;

        // --- C. PREPARAÇÃO DO BANCO ---
        $pdo->beginTransaction();

        // Busca dados do cartão se necessário
        $dia_fechamento = 1; // Padrão
        if ($cartoid) {
            $stmt_c = $pdo->prepare("SELECT cartofechamento FROM cartoes WHERE cartoid = ?");
            $stmt_c->execute([$cartoid]);
            $res = $stmt_c->fetch();
            if ($res) $dia_fechamento = (int)$res['cartofechamento'];
        }

        // ID de Agrupamento (para identificar parcelas da mesma compra)
        $grupo_id = ($parcelas_total > 1) ? uniqid('grp_') : null;
        
        // Se for parcelado, dividimos o valor. Se for fixa, mantém o valor cheio.
        $valor_final = ($parcelas_total > 1) ? ($contavalor / $parcelas_total) : $contavalor;

        // --- D. LOOP DE INSERÇÃO ---
        // Cria 1 registro (se fixa ou à vista) ou N registros (se parcelado)
        
        // Objeto de data base para manipular
        $data_base = new DateTime($contavencimento);

        for ($i = 1; $i <= $parcelas_total; $i++) {
            
            // 1. Cálculo da Data de Vencimento/Competência Normal
            // (Na parcela 1 é a data original, nas seguintes soma meses)
            $data_iteracao = clone $data_base;
            if ($i > 1) {
                $data_iteracao->modify("+" . ($i - 1) . " months");
            }
            
            $vencimento_db = $data_iteracao->format('Y-m-d');
            $competencia_db = $data_iteracao->format('Y-m');

            // 2. Cálculo da Fatura do Cartão (Se houver)
            $fatura_db = null;
            if ($cartoid) {
                $dia_compra = (int)$data_iteracao->format('d');
                $data_fatura_calc = clone $data_iteracao;
                
                // Se a data da parcela caiu depois do fechamento, joga para o próximo mês
                if ($dia_compra >= $dia_fechamento) {
                    $data_fatura_calc->modify('first day of next month');
                }
                $fatura_db = $data_fatura_calc->format('Y-m');
            }

            // 3. Monta Descrição (Ex: "Compra (1/3)")
            $descricao_final = $contadescricao;
            if ($parcelas_total > 1) {
                $descricao_final .= " ($i/$parcelas_total)";
            }

            // 4. Inserção
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
                $i, $parcelas_total, $grupo_id
            ]);
        }

        $pdo->commit();

        $_SESSION['mensagem_flash'] = "Lançamento salvo com sucesso!";
        $_SESSION['tipo_flash'] = "success";

        // --- E. REDIRECIONAMENTO ---
        if ($manter_dados == '1') {
            // Volta para o formulário mantendo alguns campos
            $query = http_build_query([
                'tipo' => $contatipo,
                'cat' => $categoriaid,
                'car' => $cartoid ?? '',
                'fixa' => $contafixa
            ]);
            header("Location: index.php?pg=cadastro_conta&" . $query);
        } else {
            // Vai para o Dashboard
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