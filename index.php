<?php
include_once __DIR__ . '/inc/verificarSession.php';
include_once __DIR__ . '/inc/DBConn.php';
$mensagem = '';
$tipoMensagem = '';

if (isset($_POST['nomeCadastrar']) && isset($_POST['estoqueCadastrar']) && isset($_POST['precoCadastrar'])) {
    $nome = $_POST['nomeCadastrar'];
    $estoque = $_POST['estoqueCadastrar'];
    $preco = $_POST['precoCadastrar'];

    $sqlInsert = "INSERT INTO tb_produtos (nm_produto, qt_estoque, vl_preco, st_ativo) VALUES (?, ?, ?, 1)";
    $stmt = $conn->prepare($sqlInsert);
    $stmt->bind_param("sid", $nome, $estoque, $preco);
    if ($stmt->execute()) {
        $mensagem = 'Produto cadastrado com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao cadastrar produto: ' . $stmt->error;
        $tipoMensagem = 'danger';
    }
}
if (isset($_POST['nomeEditar']) && isset($_POST['estoqueEditar']) && isset($_POST['precoEditar'])) {
    $codigo = $_POST['idProduto'];
    $nome = $_POST['nomeEditar'];
    $estoque = $_POST['estoqueEditar'];
    $preco = $_POST['precoEditar'];

    $sqlUpdate = "UPDATE tb_produtos SET nm_produto = ?, qt_estoque = ?, vl_preco = ? WHERE cd_produto = ?";
    $stmt = $conn->prepare($sqlUpdate);
    $stmt->bind_param("sidi", $nome, $estoque, $preco, $codigo);
    if ($stmt->execute()) {
        $mensagem = 'Produto atualizado com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao atualizar produto: ' . $stmt->error;
        $tipoMensagem = 'danger';
    }
}
if (isset($_POST['idProdutoExcluir'])) {
    $codigo = (int) $_POST['idProdutoExcluir'];

    $sqlDelete = "UPDATE tb_produtos SET st_ativo = 0 WHERE cd_produto = ?";
    $stmt = $conn->prepare($sqlDelete);
    $stmt->bind_param("i", $codigo);
    if ($stmt->execute()) {
        $mensagem = 'Produto desativado com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $mensagem = 'Erro ao excluir produto: ' . $stmt->error;
        $tipoMensagem = 'danger';
    }
}

if (isset($_GET['pesquisa'])) {
    $pesquisa = $_GET['pesquisa'];
    $sql = "SELECT * FROM tb_produtos WHERE nm_produto LIKE ? AND st_ativo = 1";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%$pesquisa%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM tb_produtos WHERE st_ativo = 1";
    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
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
    sidebar('produtos');
    ?>
    <div class="container-fluid pt-3">
        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-<?= $tipoMensagem ?> alert-dismissible fade show mx-3" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>
        <div class="row">
            <div class="col-md-12">
                <h1 class="ms-3">Produtos</h1>
            </div>
            <div class="col-md-3 mb-3">
                <button class="btn btn-success w-100" onclick="openModalCadastrar()">
                    <i class="bi bi-plus-lg"></i> Cadastrar Produto
                </button>
            </div>
            <div class="col-md-9 mb-3">
                <form method="GET">
                <div class="input-group">
                    <label class="visually-hidden" for="pesquisa">Pesquisar produtos</label>
                    <input type="text" id="pesquisa" class="form-control" name="pesquisa" placeholder="Pesquisar..." value="<?=  isset($_GET['pesquisa']) ? $_GET['pesquisa'] : '' ?>">
                    <button class="btn btn-outline-secondary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary">Código</th>
                            <th class="text-secondary">Nome</th>
                            <th class="text-secondary">Estoque</th>
                            <th class="text-secondary">Preço</th>
                            <th class="text-secondary text-end">Opções</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php
                            $estoque = (int) $row['qt_estoque'];
                            if ($estoque < 4) {
                                $badgeClass = 'danger';
                            } elseif ($estoque < 8) {
                                $badgeClass = 'warning';
                            } else {
                                $badgeClass = 'success';
                            }
                            ?>
                            <tr>
                                <td>
                                    <span class="text-muted">
                                        <?= $row['cd_produto'] ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>
                                        <?= $row['nm_produto'] ?>
                                    </strong>
                                </td>
                                <td>
                                    <span class="badge text-bg-<?= $badgeClass ?>">
                                        <?= $row['qt_estoque'] ?>
                                    </span>
                                </td>
                                <td>
                                    R$ <?= number_format($row['vl_preco'], 2, ',', '.') ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button
                                            class="btn btn-sm btn-warning"
                                            onclick='openModalEditar(
                                                <?= $row["cd_produto"] ?>,
                                                <?= json_encode($row["nm_produto"]) ?>,
                                                <?= $row["qt_estoque"] ?>,
                                                <?= $row["vl_preco"] ?>
                                            )'>
                                            <i class="bi bi-pencil"></i>
                                            Editar
                                        </button>
                                        <button
                                            class="btn btn-sm btn-danger"
                                            onclick='openModalExcluir(
                                                <?= $row["cd_produto"] ?>,
                                                <?= json_encode($row["nm_produto"]) ?>,
                                                <?= $row["qt_estoque"] ?>,
                                                <?= $row["vl_preco"] ?>
                                            )'>
                                            <i class="bi bi-trash"></i>
                                            Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>

                        <?php endwhile; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL EDITAR-->
    <div
        class="modal fade"
        id="modalEditar"
        tabindex="-1"
        aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">
                        Produto
                    </h1>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="idProduto" class="col-form-label">
                                Id:
                            </label>
                            <input
                                type="text"
                                class="form-control text-muted bg-light"
                                id="idProduto"
                                name="idProduto"
                                readonly>
                        </div>

                        <div class="mb-3">
                            <label for="nomeEditar" class="col-form-label">
                                Nome:
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="nomeEditar"
                                name="nomeEditar">
                        </div>

                        <div class="mb-3">
                            <label for="estoqueEditar" class="col-form-label">
                                Estoque:
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="estoqueEditar"
                                name="estoqueEditar">
                        </div>

                        <div class="mb-3">
                            <label for="precoEditar" class="col-form-label">
                                Preço:
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="precoEditar"
                                name="precoEditar"
                                step="0.01">
                        </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Fechar
                    </button>
                    <button
                        type="submit"
                        class="btn btn-warning"
                        id="btnAcao">
                        Editar
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Excluir -->
    <div
        class="modal fade"
        id="modalExcluir"
        tabindex="-1"
        aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">
                        Excluir Produto
                    </h1>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <h5 class="text-danger">
                        Tem certeza que deseja excluir este produto?
                    </h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="idProdutoExcluir" class="col-form-label">
                                Id:
                            </label>
                            <input
                                type="text"
                                class="form-control text-muted bg-light"
                                id="idProdutoExcluir"
                                name="idProdutoExcluir"
                                readonly>
                        </div>

                        <div class="mb-3">
                            <label for="nomeExcluir" class="col-form-label">
                                Nome:
                            </label>

                            <input
                                type="text"
                                class="form-control text-muted bg-light"
                                name="nomeExcluir"
                                id="nomeExcluir" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="estoqueExcluir" class="col-form-label">
                                Estoque:
                            </label>

                            <input
                                type="number"
                                class="form-control text-muted bg-light"
                                name="estoqueExcluir"
                                id="estoqueExcluir" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="precoExcluir" class="col-form-label">
                                Preço:
                            </label>

                            <input
                                type="number"
                                class="form-control text-muted bg-light"
                                name="precoExcluir"
                                id="precoExcluir" readonly>
                        </div>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                        Fechar
                    </button>
                    <button
                        type="submit"
                        class="btn btn-danger">
                        Excluir
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal Cadastrar -->
    <div
        class="modal fade"
        id="modalCadastrar"
        tabindex="-1"
        aria-labelledby="exampleModalLabel"
        aria-hidden="true">

        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h1 class="modal-title fs-5">
                        Produto
                    </h1>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close">
                    </button>
                </div>

                <form action="index.php" method="POST">

                    <div class="modal-body">

                        <div class="mb-3">
                            <label for="nomeCadastrar" class="col-form-label">
                                Nome:
                            </label>

                            <input
                                type="text"
                                class="form-control "
                                id="nomeCadastrar"
                                name="nomeCadastrar"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="estoqueCadastrar" class="col-form-label">
                                Estoque:
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="estoqueCadastrar"
                                name="estoqueCadastrar"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="precoCadastrar" class="col-form-label">
                                Preço:
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                id="precoCadastrar"
                                name="precoCadastrar"
                                step="0.01"
                                required>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Fechar
                        </button>

                        <button
                            type="submit"
                            class="btn btn-success"
                            id="btnCadastrar">
                            Cadastrar
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>


    <script>
        function openModalEditar(codigo, nome, estoque, preco) {
            document.getElementById('idProduto').value = codigo;
            document.getElementById('nomeEditar').value = nome;
            document.getElementById('estoqueEditar').value = estoque;
            document.getElementById('precoEditar').value = preco;
            const modal = new bootstrap.Modal(
                document.getElementById('modalEditar')
            );
            modal.show();
        }

        function openModalExcluir(codigo, nome, estoque, preco) {
            document.getElementById('idProdutoExcluir').value = codigo;
            document.getElementById('nomeExcluir').value = nome;
            document.getElementById('estoqueExcluir').value = estoque;
            document.getElementById('precoExcluir').value = preco;
            const modal = new bootstrap.Modal(
                document.getElementById('modalExcluir')
            );
            modal.show();
        }

        function openModalCadastrar() {
            const modal = new bootstrap.Modal(
                document.getElementById('modalCadastrar')
            );
            modal.show();
        }
    </script>
</body>

</html>
