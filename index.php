<?php
include_once __DIR__ . '/inc/DBConn.php';

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
</head>

<body>
    <?php
    include_once __DIR__ . '/inc/sidebar.php';
    sidebar('produtos');
    ?>
    <div class="container-fluid pt-3">
        <div class="row">
            <div class="col-md-12">
                <h1 class="ms-3">Produtos</h1>
            </div>

            <div class="col-md-2 mb-3">
                <a href="cadastrarProduto.php" class="btn btn-primary w-100">
                    Cadastrar Produto
                </a>
            </div>

            <div class="col-md-10 mb-3">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Pesquisar...">

                    <button class="btn btn-outline-secondary" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
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
                        <tr>
                            <td>
                                <span class="text-muted">001</span>
                            </td>

                            <td>
                                <strong>PRoduato</strong>
                            </td>

                            <td>
                                <span class="badge text-bg-success">12</span>
                            </td>

                            <td>
                                R$ 89,90
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                        Editar
                                    </button>

                                    <button class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <span class="text-muted">002</span>
                            </td>

                            <td>
                                <strong>PRoduato</strong>
                            </td>

                            <td>
                                <span class="badge text-bg-success">19</span>
                            </td>

                            <td>
                                R$ 49,90
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                        Editar
                                    </button>

                                    <button class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <span class="text-muted">003</span>
                            </td>

                            <td>
                                <strong>PRoduato</strong>
                            </td>

                            <td>
                                <span class="badge text-bg-success">17</span>
                            </td>

                            <td>
                                R$ 39,90
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                        Editar
                                    </button>

                                    <button class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td>
                                <span class="text-muted">004</span>
                            </td>

                            <td>
                                <strong>PRoduato</strong>
                            </td>

                            <td>
                                <span class="badge text-bg-danger">2</span>
                            </td>

                            <td>
                                R$ 199,90
                            </td>

                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                        Editar
                                    </button>
                                    <button class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash"></i>
                                        Excluir
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>