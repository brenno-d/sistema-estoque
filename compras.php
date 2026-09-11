<?php
include_once __DIR__ . '/inc/verificarSession.php';
include_once __DIR__ . '/inc/DBConn.php';

// Cadastrar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrarCompra'])) {
    $dataSql = date('Y-m-d H:i:s', strtotime($_POST['dt_compra']));
    $idsProdutos = $_POST['produtos'];
    $quantidades = $_POST['quantidades'];
    $total = 0;
    $itensCompra = [];
    $stmtProduto = $conn->prepare('SELECT nm_produto, qt_estoque, vl_preco FROM tb_produtos WHERE cd_produto = ?');

    foreach ($idsProdutos as $indice => $idProduto) {
        $idProduto = (int) $idProduto;
        $quantidade = (int) $quantidades[$indice];
        $stmtProduto->bind_param('i', $idProduto);
        $stmtProduto->execute();
        $produto = $stmtProduto->get_result()->fetch_assoc();
        $total += $produto['vl_preco'] * $quantidade;
        $itensCompra[] = [$idProduto, $quantidade];
    }
    $stmtCompra = $conn->prepare('INSERT INTO tb_compras (dt_compra, vl_total_compra) VALUES (?, ?)');
    $stmtCompra->bind_param('sd', $dataSql, $total);
    $stmtCompra->execute();
    $idCompra = $conn->insert_id;

    $stmtProdutoCompra = $conn->prepare('INSERT INTO tb_produtos_compras (id_produto, id_compra, qt_produto) VALUES (?, ?, ?)');
    $stmtEstoque = $conn->prepare('UPDATE tb_produtos SET qt_estoque = qt_estoque + ? WHERE cd_produto = ?');
    foreach ($itensCompra as [$idProduto, $quantidade]) {
        $stmtProdutoCompra->bind_param('iii', $idProduto, $idCompra, $quantidade);
        $stmtProdutoCompra->execute();
        $stmtEstoque->bind_param('ii', $quantidade, $idProduto);
        $stmtEstoque->execute();
    }

    header('Location: compras.php');
    exit;
}

// Excluir compra
if (isset($_POST['excluirCompra'])) {
    $idCompra = (int) $_POST['id_compra'];
    $stmtItens = $conn->prepare('SELECT id_produto, qt_produto FROM tb_produtos_compras WHERE id_compra = ?');
    $stmtItens->bind_param('i', $idCompra);
    $stmtItens->execute();
    $itensCompra = $stmtItens->get_result();

    $stmtEstoque = $conn->prepare('UPDATE tb_produtos SET qt_estoque = qt_estoque - ? WHERE cd_produto = ?');
    while ($item = $itensCompra->fetch_assoc()) {
        $stmtEstoque->bind_param('ii', $item['qt_produto'], $item['id_produto']);
        $stmtEstoque->execute();
    }

    $stmtProdutoCompra = $conn->prepare('DELETE FROM tb_produtos_compras WHERE id_compra = ?');
    $stmtProdutoCompra->bind_param('i', $idCompra);
    $stmtProdutoCompra->execute();
    $stmtCompra = $conn->prepare('DELETE FROM tb_compras WHERE cd_compra = ?');
    $stmtCompra->bind_param('i', $idCompra);
    $stmtCompra->execute();
    header('Location: compras.php');
    exit;
}

$produtos = $conn->query('SELECT cd_produto, nm_produto, qt_estoque, vl_preco FROM tb_produtos ORDER BY nm_produto');
$compras = $conn->query('SELECT cd_compra, dt_compra, vl_total_compra FROM tb_compras ORDER BY dt_compra DESC');
$detalhes = [];
$itens = $conn->query('SELECT pc.id_compra, p.nm_produto, pc.qt_produto, p.vl_preco FROM tb_produtos_compras pc INNER JOIN tb_produtos p ON p.cd_produto = pc.id_produto ORDER BY pc.id_compra DESC, p.nm_produto');

if ($itens) {
    while ($item = $itens->fetch_assoc()) {
        $detalhes[$item['id_compra']][] = $item;
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras</title>
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
    sidebar('compras');
    ?>

    <div class="container-fluid pt-3">
        <div class="row">
            <div class="col-md-12">
                <h1 class="ms-3">Compras</h1>
            </div>

            <div class="col-md-3 mb-3">
                <button class="btn btn-success w-100" type="button" data-bs-toggle="modal" data-bs-target="#modalCadastrarCompra">
                    <i class="bi bi-plus-lg"></i> Cadastrar compra
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="tabelaCompras">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary">Código</th>
                            <th class="text-secondary">Data</th>
                            <th class="text-secondary">Valor Total</th>
                            <th class="text-secondary text-end">Opções</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($compras->num_rows > 0): ?>
                            <?php while ($compra = $compras->fetch_assoc()): ?>
                                <?php
                                $dados = [
                                    'codigo' => $compra['cd_compra'],
                                    'data' => date('d/m/Y H:i', strtotime($compra['dt_compra'])),
                                    'valor' => (float) $compra['vl_total_compra'],
                                    'itens' => $detalhes[$compra['cd_compra']]
                                ];
                                ?>
                                <tr>
                                    <td><span class="text-muted"><?= $compra['cd_compra'] ?></span></td>
                                    <td><?= date('d/m/Y H:i', strtotime($compra['dt_compra'])) ?></td>
                                    <td>R$ <?= number_format($compra['vl_total_compra'], 2, ',', '.') ?></td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button type="button" class="btn btn-sm btn-primary" onclick='openModalDetalhes(<?= json_encode($dados) ?>)'>
                                                <i class="bi bi-eye"></i> Detalhes
                                            </button>
                                            <button
                                                class="btn btn-sm btn-danger"
                                                onclick='openModalExcluir(<?= json_encode($dados) ?>)'>
                                                <i class="bi bi-trash"></i> Excluir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr id="semCompras">
                                <td colspan="4" class="text-center text-muted">Nenhuma compra encontrada.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCadastrarCompra" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="formCadastrarCompra" method="POST">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Cadastrar compra</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="dataCompra" class="form-label">Data da compra</label>
                                <input class="form-control" type="datetime-local" id="dataCompra" name="dt_compra" required>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="mb-0">Produtos comprados</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="adicionarProduto">
                                <i class="bi bi-plus-lg"></i> Adicionar produto
                            </button>
                        </div>

                        <div id="listaProdutos"></div>
                        <div class="text-end mt-3 fs-5">Total: <strong id="totalCompra">R$ 0,00</strong></div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" name="cadastrarCompra" class="btn btn-success">Cadastrar compra</button>
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
                        <h1 class="modal-title fs-5">Detalhes da Compra</h1>
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
                        <div class="col-md-6"><small class="text-muted">Valor Total</small>
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
                            <tbody id="produtosCompra"></tbody>
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
                    <h1 class="modal-title text-danger fs-5">Excluir Compra</h1>
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
                        <div class="col-md-6"><small class="text-muted">Valor Total</small>
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
                            <tbody id="produtosCompraExcluir"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="id_compra" id="idCompraExcluir">
                        <button type="submit" name="excluirCompra" class="btn btn-danger">Excluir</button>
                    </form>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const produtosDisponiveis = <?= json_encode($produtos ? $produtos->fetch_all(MYSQLI_ASSOC) : []) ?>;
        const listaProdutos = document.getElementById('listaProdutos');
        const totalCompra = document.getElementById('totalCompra');

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

            totalCompra.textContent = moeda(total);
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
        document.getElementById('modalCadastrarCompra').addEventListener('show.bs.modal', () => {
            document.getElementById('dataCompra').value = new Date(
                Date.now() - new Date().getTimezoneOffset() * 60000
            ).toISOString().slice(0, 16);
        });

        function openModalDetalhes(compra) {
            document.getElementById('detalheCodigo').textContent = compra.codigo;
            document.getElementById('detalheData').textContent = compra.data;
            document.getElementById('detalheValor').textContent = moeda(compra.valor);

            const corpo = document.getElementById('produtosCompra');
            corpo.innerHTML = compra.itens.map((item) => {
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
                <td class="text-end"><strong class="text-success">${moeda(compra.valor)}</strong></td>
            </tr>
        `);
            new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
        }

        function openModalExcluir(compra) {
            document.getElementById('idCompraExcluir').value = compra.codigo;
            document.getElementById('excluirCodigo').textContent = compra.codigo;
            document.getElementById('excluirData').textContent = compra.data;
            document.getElementById('excluirValor').textContent = moeda(compra.valor);

            const corpo = document.getElementById('produtosCompraExcluir');
            corpo.innerHTML = compra.itens.map((item) => {
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
                <td class="text-end"><strong class="text-success">${moeda(compra.valor)}</strong></td>
            </tr>
        `);
            new bootstrap.Modal(document.getElementById('modalExcluir')).show();
        }
    </script>
</body>

</html>