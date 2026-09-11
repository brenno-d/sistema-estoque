<?php
include_once __DIR__ . '/inc/verificarSession.php';
include_once __DIR__ . '/inc/DBConn.php';

$periodos = [
    'hoje' => ['label' => 'Hoje', 'intervalo' => 'today'],
    '7' => ['label' => 'Últimos 7 dias', 'intervalo' => '-7 days'],
    '30' => ['label' => 'Últimos 30 dias', 'intervalo' => '-30 days'],
    '60' => ['label' => 'Bimestre', 'intervalo' => '-2 months'],
    '180' => ['label' => 'Semestre', 'intervalo' => '-6 months'],
];

$periodoSelecionado = $_GET['periodo'] ?? 'hoje';
if (!isset($periodos[$periodoSelecionado])) {
    $periodoSelecionado = 'hoje';
}

$dataInicial = $periodoSelecionado === 'hoje'
    ? date('Y-m-d 00:00:00')
    : date('Y-m-d H:i:s', strtotime($periodos[$periodoSelecionado]['intervalo']));
$dataFinal = date('Y-m-d H:i:s');

$stmtResumo = $conn->prepare(
    'SELECT
        (SELECT COUNT(*) FROM tb_vendas WHERE dt_venda >= ? AND dt_venda <= ?) AS total_vendas,
        (SELECT COALESCE(SUM(pv.qt_produto), 0)
         FROM tb_produtos_vendas pv
         INNER JOIN tb_vendas v ON v.cd_venda = pv.id_venda
         WHERE v.dt_venda >= ? AND v.dt_venda <= ?) AS total_produtos,
        (SELECT COALESCE(SUM(vl_total), 0)
         FROM tb_vendas WHERE dt_venda >= ? AND dt_venda <= ?) AS faturamento'
);
$stmtResumo->bind_param('ssssss', $dataInicial, $dataFinal, $dataInicial, $dataFinal, $dataInicial, $dataFinal);
$stmtResumo->execute();
$resumo = $stmtResumo->get_result()->fetch_assoc();

$stmtProdutos = $conn->prepare(
    'SELECT p.nm_produto, SUM(pv.qt_produto) AS quantidade
     FROM tb_produtos_vendas pv
     INNER JOIN tb_produtos p ON p.cd_produto = pv.id_produto
     INNER JOIN tb_vendas v ON v.cd_venda = pv.id_venda
    WHERE v.dt_venda >= ? AND v.dt_venda <= ?
     GROUP BY p.cd_produto, p.nm_produto
     ORDER BY quantidade DESC, p.nm_produto
     LIMIT 5'
);
$stmtProdutos->bind_param('ss', $dataInicial, $dataFinal);
$stmtProdutos->execute();
$produtosMaisVendidos = $stmtProdutos->get_result()->fetch_all(MYSQLI_ASSOC);

$stmtProdutosMenos = $conn->prepare(
    'SELECT p.nm_produto,
            COALESCE(SUM(CASE WHEN v.cd_venda IS NOT NULL THEN pv.qt_produto ELSE 0 END), 0) AS quantidade
     FROM tb_produtos p
     LEFT JOIN tb_produtos_vendas pv ON pv.id_produto = p.cd_produto
     LEFT JOIN tb_vendas v ON v.cd_venda = pv.id_venda
         AND v.dt_venda >= ? AND v.dt_venda <= ?
     GROUP BY p.cd_produto, p.nm_produto
     ORDER BY quantidade ASC, p.nm_produto
     LIMIT 5'
);
$stmtProdutosMenos->bind_param('ss', $dataInicial, $dataFinal);
$stmtProdutosMenos->execute();
$produtosMenosVendidos = $stmtProdutosMenos->get_result()->fetch_all(MYSQLI_ASSOC);

$stmtFuncionarios = $conn->prepare(
    'SELECT COALESCE(f.nm_funcionario, "Não identificado") AS nm_funcionario,
            COALESCE(SUM(pv.qt_produto), 0) AS quantidade
     FROM tb_vendas v
     LEFT JOIN tb_funcionarios f ON f.cd_funcionario = v.id_funcionario
     INNER JOIN tb_produtos_vendas pv ON pv.id_venda = v.cd_venda
     WHERE v.dt_venda >= ? AND v.dt_venda <= ?
     GROUP BY f.cd_funcionario, f.nm_funcionario
     ORDER BY quantidade DESC, nm_funcionario
     LIMIT 5'
);
$stmtFuncionarios->bind_param('ss', $dataInicial, $dataFinal);
$stmtFuncionarios->execute();
$funcionariosMaisVendas = $stmtFuncionarios->get_result()->fetch_all(MYSQLI_ASSOC);

$produtoMaisLabels = array_column($produtosMaisVendidos, 'nm_produto');
$produtoMaisValores = array_map('intval', array_column($produtosMaisVendidos, 'quantidade'));
$produtoMenosLabels = array_column($produtosMenosVendidos, 'nm_produto');
$produtoMenosValores = array_map('intval', array_column($produtosMenosVendidos, 'quantidade'));
$funcionarioLabels = array_column($funcionariosMaisVendas, 'nm_funcionario');
$funcionarioValores = array_map('intval', array_column($funcionariosMaisVendas, 'quantidade'));
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --preto: #17212b;
            --cinza: #708090;
            --verde-claro: #16a085;
            --fundo: #e4ebf2;
        }

        body {
            background: #f4f7fb;
            color: var(--preto);
        }
        .card-resumo,
        .card-dados {
            border: 1px solid var(--fundo);
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(23, 33, 43, .05) !important;
        }

        .card-grafico {
            position: relative;
            height: 320px;
        }
        .card-resumo {
            min-height: 128px;
            overflow: hidden;
            position: relative;
        }
        .valores-card {
            color: var(--preto);
            font-size: 1.75rem;
            font-weight: 700;
        }
        .card-dados .card-body {
            padding: 1.5rem;
        }
        .card-dados h2 {
            font-weight: bold;
        }
        .tabela-relatorio thead th {
            color: var(--cinza);
            font-size: .75rem;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <?php
    include_once __DIR__ . '/inc/sidebar.php';
    sidebar('relatorios');
    ?>

    <main class="container-fluid p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h1 class="titulo-relatorio mb-2">Relatório de vendas</h1>
                <p class="text-muted mb-0">Acompanhe o desempenho do estoque e da equipe.</p>
            </div>
            <form method="GET" class="d-flex align-items-end gap-2">
                <div>
                    <label for="periodo" class="form-label mb-1">Período</label>
                    <select class="form-select periodo-relatorio" id="periodo" name="periodo" onchange="this.form.submit()">
                        <?php foreach ($periodos as $valor => $periodo): ?>
                            <option value="<?= $valor ?>" <?= $periodoSelecionado == $valor ? 'selected' : '' ?>>
                                <?= $periodo['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-4">
                <div class="card card-resumo">
                    <div class="card-body">
                        <span class="text-muted small">Faturamento</span>
                        <h2 class="valores-card mt-2 mb-0">R$ <?= number_format((float) $resumo['faturamento'], 2, ',', '.') ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card card-resumo">
                    <div class="card-body">
                        <span class="text-muted small">Vendas realizadas</span>
                        <h2 class="valores-card mt-2 mb-0"><?= (int) $resumo['total_vendas'] ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card card-resumo">
                    <div class="card-body">
                        <span class="text-muted small">Produtos vendidos</span>
                        <h2 class="valores-card mt-2 mb-0"><?= (int) $resumo['total_produtos'] ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="card card-dados h-100">
                    <div class="card-body">
                        <h2 class="h5">Produtos mais vendidos</h2>
                        <div class="card-grafico"><canvas id="graficoMaisVendidos"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="card card-dados h-100">
                    <div class="card-body">
                        <h2 class="h5">Produtos menos vendidos</h2>
                        <div class="card-grafico"><canvas id="graficoMenosVendidos"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-8">
                <div class="card card-dados h-100">
                    <div class="card-body">
                        <h2 class="h5">Vendedores com mais produtos vendidos</h2>
                        <div class="card-grafico"><canvas id="graficoFuncionarios"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="card card-dados h-100">
                    <div class="card-body">
                        <h2 class="h5">Resumo por vendedor</h2>
                        <div class="table-responsive">
                            <table class="table tabela-relatorio align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>VENDEDOR</th>
                                        <th class="text-end">PRODUTOS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($funcionariosMaisVendas): ?>
                                        <?php foreach ($funcionariosMaisVendas as $funcionario): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($funcionario['nm_funcionario']) ?></td>
                                                <td class="text-end fw-bold"><?= (int) $funcionario['quantidade'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center text-muted">Nenhuma venda no período.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        };

        function criarGrafico(id, labels, data, cor, tipo = 'bar') {
            new Chart(document.getElementById(id), {
                type: tipo,
                data: {
                    labels,
                    datasets: [{
                        data,
                        backgroundColor: cor,
                        borderRadius: 5
                    }]
                },
                options: chartOptions
            });
        }

        criarGrafico('graficoMaisVendidos', <?= json_encode($produtoMaisLabels, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($produtoMaisValores) ?>, '#198754');
        criarGrafico('graficoMenosVendidos', <?= json_encode($produtoMenosLabels, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($produtoMenosValores) ?>, '#dc3545');
        criarGrafico('graficoFuncionarios', <?= json_encode($funcionarioLabels, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($funcionarioValores) ?>, '#0d6efd');
    </script>
</body>

</html>
