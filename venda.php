<?php
include_once __DIR__ . '/inc/verificarSession.php';
include_once __DIR__ . '/inc/DBConn.php';
// Cadastrar venda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrarVenda'])) {
    $idFuncionario = (int) $_POST['id_funcionario'];
    $dataSql = date('Y-m-d H:i:s', strtotime($_POST['dt_venda']));
    $idsProdutos = $_POST['produtos'];
    $quantidades = $_POST['quantidades'];
    $total = 0;
    $itensVenda = [];
    $stmtProduto = $conn->prepare('SELECT nm_produto, qt_estoque, vl_preco FROM tb_produtos WHERE cd_produto = ?');

    foreach ($idsProdutos as $indice => $idProduto) {
        $idProduto = (int) $idProduto;
        $quantidade = (int) $quantidades[$indice];
        $stmtProduto->bind_param('i', $idProduto);
        $stmtProduto->execute();
        $produto = $stmtProduto->get_result()->fetch_assoc();
        $total += $produto['vl_preco'] * $quantidade;
        $itensVenda[] = [$idProduto, $quantidade];
    }
    $stmtVenda = $conn->prepare('INSERT INTO tb_vendas (dt_venda, vl_total, id_funcionario) VALUES (?, ?, ?)');
    $stmtVenda->bind_param('sdi', $dataSql, $total, $idFuncionario);
    $stmtVenda->execute();
    $idVenda = $conn->insert_id;

    $stmtProdutoVenda = $conn->prepare('INSERT INTO tb_produtos_vendas (id_produto, id_venda, qt_produto) VALUES (?, ?, ?)');
    $stmtEstoque = $conn->prepare('UPDATE tb_produtos SET qt_estoque = qt_estoque - ? WHERE cd_produto = ?');
    foreach ($itensVenda as [$idProduto, $quantidade]) {
        $stmtProdutoVenda->bind_param('iii', $idProduto, $idVenda, $quantidade);
        $stmtProdutoVenda->execute();
        $stmtEstoque->bind_param('ii', $quantidade, $idProduto);
        $stmtEstoque->execute();
    }

    header('Location: venda.php');
    exit;
}

// excluir venda
if (isset($_POST['excluirVenda'])) {
    $idVenda = (int) $_POST['id_venda'];
    $stmtProdutoVenda = $conn->prepare('DELETE FROM tb_produtos_vendas WHERE id_venda = ?');
    $stmtProdutoVenda->bind_param('i', $idVenda);
    $stmtProdutoVenda->execute();
    $stmtVenda = $conn->prepare('DELETE FROM tb_vendas WHERE cd_venda = ?');
    $stmtVenda->bind_param('i', $idVenda);
    $stmtVenda->execute();
    header('Location: venda.php');
    exit;
}

$produtos = $conn->query('SELECT cd_produto, nm_produto, qt_estoque, vl_preco FROM tb_produtos ORDER BY nm_produto');
$funcionarios = $conn->query('SELECT cd_funcionario, nm_funcionario FROM tb_funcionarios ORDER BY nm_funcionario');
$vendas = $conn->query('SELECT v.cd_venda, v.dt_venda, v.vl_total, f.nm_funcionario FROM tb_vendas v LEFT JOIN tb_funcionarios f ON f.cd_funcionario = v.id_funcionario ORDER BY v.dt_venda DESC');
$detalhes = [];
$itens = $conn->query('SELECT pv.id_venda, p.nm_produto, pv.qt_produto, p.vl_preco FROM tb_produtos_vendas pv INNER JOIN tb_produtos p ON p.cd_produto = pv.id_produto ORDER BY pv.id_venda DESC, p.nm_produto');

if ($itens) {
    while ($item = $itens->fetch_assoc()) {
        $detalhes[$item['id_venda']][] = $item;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas</title>
    <style>
        body {
            background: #f4f7fb;
            color: var(--preto);
        }
    </style>
</head>

<body>
    <?php
    include_once __DIR__ . '/inc/sidebar.php';
    sidebar('vendas');
    ?>

    <div class="container-fluid pt-3">
        <div class="row">
            <div class="col-md-12">
                <h1 class="ms-3">Vendas</h1>
            </div>

            <div class="col-md-3 mb-3">
                <button class="btn btn-success w-100" type="button" data-bs-toggle="modal" data-bs-target="#modalCadastrarVenda">
                    <i class="bi bi-plus-lg"></i> Cadastrar venda
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tabelaVendas">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary">Código</th>
                            <th class="text-secondary">Data</th>
                            <th class="text-secondary">Funcionário</th>
                            <th class="text-secondary">Valor Total</th>
                            <th class="text-secondary text-end">Opções</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($vendas->num_rows > 0): ?>
                            <?php while ($venda = $vendas->fetch_assoc()): ?>
                                <?php
                                $dados = [
                                    'codigo' => $venda['cd_venda'],
                                    'data' => date('d/m/Y H:i', strtotime($venda['dt_venda'])),
                                    'funcionario' => $venda['nm_funcionario'],
                                    'valor' => (float) $venda['vl_total'],
                                    'itens' => $detalhes[$venda['cd_venda']]
                                ];
                                ?>
                                <tr>
                                    <td><span class="text-muted"><?= $venda['cd_venda'] ?></span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($venda['dt_venda'])) ?></td>
                                    <td><strong><?= $venda['nm_funcionario'] ?></strong></td>
                                    <td>R$ <?= number_format($venda['vl_total'], 2, ',', '.') ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-primary" onclick='openModalDetalhes(<?= json_encode($dados) ?>)'>
                                                <i class="bi bi-eye"></i> Detalhes
                                            </button>
                                            <button
                                                class="btn btn-sm btn-danger"
                                                onclick='openModalExcluir(<?= json_encode($dados) ?>)'>
                                                <i class="bi bi-trash"></i>
                                                Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="semVendas">
                                <td colspan="5" class="text-center text-muted">Nenhuma venda encontrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCadastrarVenda" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formCadastrarVenda" method="POST">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Cadastrar venda</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="idFuncionario" class="form-label">Funcionário</label>
                                <select class="form-select" id="idFuncionario" name="id_funcionario" required>
                                    <option value="">Selecione o funcionário</option>
                                    <?php if ($funcionarios): ?>
                                        <?php while ($funcionario = $funcionarios->fetch_assoc()): ?>
                                            <option value="<?= $funcionario['cd_funcionario'] ?>">
                                                <?= $funcionario['nm_funcionario'] ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="dataVenda" class="form-label">Data da venda</label>
                                <input class="form-control" type="datetime-local" id="dataVenda" name="dt_venda" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Produtos vendidos</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="adicionarProduto">
                                <i class="bi bi-plus-lg"></i> Adicionar produto
                            </button>
                        </div>

                        <div id="listaProdutos"></div>
                        <div class="text-end mt-3 fs-5">Total: <strong id="totalVenda">R$ 0,00</strong></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" name="cadastrarVenda" class="btn btn-success">Cadastrar venda</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal de detalhes -->
    <div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Detalhes da Venda</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-3"><small class="text-muted">Código</small>
                            <div><strong id="detalheCodigo"></strong></div>
                        </div>
                        <div class="col-md-3"><small class="text-muted">Data</small>
                            <div><strong id="detalheData"></strong></div>
                        </div>
                        <div class="col-md-3"><small class="text-muted">Funcionário</small>
                            <div><strong id="detalheFuncionario"></strong></div>
                        </div>
                        <div class="col-md-3"><small class="text-muted">Valor Total</small>
                            <div><strong class="text-success" id="detalheValor"></strong></div>
                        </div>
                    </div>

                    <h5 class="mb-3">Produtos</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-end">Preço</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="produtosVenda"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button></div>
            </div>
        </div>
    </div>
    <!-- Modal de excluir -->
    <div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title text-danger fs-5">Excluir Venda</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-3"><small class="text-muted">Código</small>
                            <div><strong id="excluirCodigo"></strong></div>
                        </div>
                        <div class="col-md-3"><small class="text-muted">Data</small>
                            <div><strong id="excluirData"></strong></div>
                        </div>
                        <div class="col-md-3"><small class="text-muted">Funcionário</small>
                            <div><strong id="excluirFuncionario"></strong></div>
                        </div>
                        <div class="col-md-3"><small class="text-muted">Valor Total</small>
                            <div><strong class="text-success" id="excluirValor"></strong></div>
                        </div>
                    </div>

                    <h5 class="mb-3">Produtos</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-center">Quantidade</th>
                                    <th class="text-end">Preço</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody id="produtosVendaExcluir"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="id_venda" id="idVendaExcluir">
                        <button type="submit" name="excluirVenda" class="btn btn-danger">Excluir</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const produtosDisponiveis = <?= json_encode($produtos ? $produtos->fetch_all(MYSQLI_ASSOC) : []) ?>;
        const listaProdutos = document.getElementById('listaProdutos');
        const totalVenda = document.getElementById('totalVenda');

        function moeda(valor) {
            return Number(valor).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        }

        function atualizarTotal() {
            let total = 0;

            listaProdutos.querySelectorAll('.produto-linha').forEach((linha) => {
                const idProduto = Number(linha.querySelector('.produto-select').value);
                const quantidade = Number(linha.querySelector('.quantidade-produto').value) || 0;
                const produto = produtosDisponiveis.find((item) => Number(item.cd_produto) === idProduto);

                if (produto) {
                    total += Number(produto.vl_preco) * quantidade;
                }
            });

            totalVenda.textContent = moeda(total);
        }

        function adicionarLinhaProduto() {
            const linha = document.createElement('div');
            linha.className = 'produto-linha row g-2 align-items-end mb-2';
            linha.innerHTML = `
            <div class="col-md-7">
                <label class="form-label">Produto</label>
                <select class="form-select produto-select" name="produtos[]" required>
                    <option value="">Selecione um produto</option>
                    ${produtosDisponiveis.map((produto) => `
                        <option value="${produto.cd_produto}">
                            ${produto.nm_produto} (estoque: ${produto.qt_estoque}) - ${moeda(produto.vl_preco)}
                        </option>
                    `).join('')}
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Quantidade</label>
                <input class="form-control quantidade-produto" name="quantidades[]" type="number" min="1" value="1" required>
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger w-100 remover-produto">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;

            linha.querySelector('.produto-select').onchange = atualizarTotal;
            linha.querySelector('.quantidade-produto').oninput = atualizarTotal;
            linha.querySelector('.remover-produto').addEventListener('click', () => {
                linha.remove();
                atualizarTotal();
            });
            listaProdutos.appendChild(linha);
        }

        document.getElementById('adicionarProduto').addEventListener('click', adicionarLinhaProduto);
        document.getElementById('modalCadastrarVenda').addEventListener('show.bs.modal', () => {
            document.getElementById('dataVenda').value = new Date(
                Date.now() - new Date().getTimezoneOffset() * 60000
            ).toISOString().slice(0, 16);
        });

        function openModalDetalhes(venda) {
            document.getElementById('detalheCodigo').textContent = venda.codigo;
            document.getElementById('detalheData').textContent = venda.data;
            document.getElementById('detalheFuncionario').textContent = venda.funcionario;
            document.getElementById('detalheValor').textContent = moeda(venda.valor);

            const corpo = document.getElementById('produtosVenda');
            corpo.innerHTML = venda.itens.map((item) => {
                const subtotal = Number(item.vl_preco) * Number(item.qt_produto);

                return `
                <tr>
                    <td>${item.nm_produto}</td>
                    <td class="text-center">${item.qt_produto}</td>
                    <td class="text-end">${moeda(item.vl_preco)}</td>
                    <td class="text-end">${moeda(subtotal)}</td>
                </tr>
            `;
            }).join('');
            corpo.insertAdjacentHTML('beforeend', `
            <tr>
                <td colspan="3" class="text-end"><strong>Total</strong></td>
                <td class="text-end"><strong class="text-success">${moeda(venda.valor)}</strong></td>
            </tr>
        `);
            new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
        }

        function openModalExcluir(venda) {
            document.getElementById('idVendaExcluir').value = venda.codigo;
            document.getElementById('excluirCodigo').textContent = venda.codigo;
            document.getElementById('excluirData').textContent = venda.data;
            document.getElementById('excluirFuncionario').textContent = venda.funcionario;
            document.getElementById('excluirValor').textContent = moeda(venda.valor);

            const corpo = document.getElementById('produtosVendaExcluir');
            corpo.innerHTML = venda.itens.map((item) => {
                const subtotal = Number(item.vl_preco) * Number(item.qt_produto);

                return `
                <tr>
                    <td>${item.nm_produto}</td>
                    <td class="text-center">${item.qt_produto}</td>
                    <td class="text-end">${moeda(item.vl_preco)}</td>
                    <td class="text-end">${moeda(subtotal)}</td>
                </tr>
            `;
            }).join('');
            corpo.insertAdjacentHTML('beforeend', `
            <tr>
                <td colspan="3" class="text-end"><strong>Total</strong></td>
                <td class="text-end"><strong class="text-success">${moeda(venda.valor)}</strong></td>
            </tr>
        `);
            new bootstrap.Modal(document.getElementById('modalExcluir')).show();
        }
    </script>
</body>

</html>