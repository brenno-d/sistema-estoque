<?php
include_once __DIR__ . '/inc/verificarSession.php';
include_once __DIR__ . '/inc/DBConn.php';

if (($_SESSION['nivel'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit();
}

$mensagem = '';
$tipoMensagem = '';

if (isset($_POST['cadastrarFuncionario'])) {
    $nome = trim($_POST['nomeFuncionario'] ?? '');
    $senha = $_POST['senhaFuncionario'] ?? '';
    $email = trim($_POST['emailFuncionario'] ?? '');
    $telefone = trim($_POST['telefoneFuncionario'] ?? '');
    $nivel = $_POST['nivelFuncionario'] ?? 'usuario';

    if ($nome === '' || $senha === '' || !in_array($nivel, ['admin', 'usuario'], true)) {
        $mensagem = 'Preencha o nome, a senha e um nível válido.';
        $tipoMensagem = 'danger';
    } else {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "INSERT INTO tb_funcionarios
                (nm_funcionario, ds_senha, ds_email_funcionario, ds_tel_funcionario, ds_nivel_funcionario)
             VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), ?)"
        );
        $stmt->bind_param('sssss', $nome, $senhaHash, $email, $telefone, $nivel);

        if ($stmt->execute()) {
            $mensagem = 'Funcionário cadastrado com sucesso!';
            $tipoMensagem = 'success';
        } else {
            $mensagem = 'Erro ao cadastrar funcionário: ' . $stmt->error;
            $tipoMensagem = 'danger';
        }
    }
}

if (isset($_POST['editarFuncionario'])) {
    $codigo = (int) ($_POST['idFuncionario'] ?? 0);
    $nome = trim($_POST['nomeFuncionario'] ?? '');
    $senha = $_POST['senhaFuncionario'] ?? '';
    $email = trim($_POST['emailFuncionario'] ?? '');
    $telefone = trim($_POST['telefoneFuncionario'] ?? '');
    $nivel = $_POST['nivelFuncionario'] ?? 'usuario';

    if ($codigo <= 0 || $nome === '' || !in_array($nivel, ['admin', 'usuario'], true)) {
        $mensagem = 'Preencha os dados obrigatórios corretamente.';
        $tipoMensagem = 'danger';
    } elseif ($senha !== '') {
        $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            "UPDATE tb_funcionarios
             SET nm_funcionario = ?, ds_senha = ?, ds_email_funcionario = NULLIF(?, ''),
                 ds_tel_funcionario = NULLIF(?, ''), ds_nivel_funcionario = ?
             WHERE cd_funcionario = ?"
        );
        $stmt->bind_param('sssssi', $nome, $senhaHash, $email, $telefone, $nivel, $codigo);
        $stmt->execute();
        $mensagem = 'Funcionário atualizado com sucesso!';
        $tipoMensagem = 'success';
    } else {
        $stmt = $conn->prepare(
            "UPDATE tb_funcionarios
             SET nm_funcionario = ?, ds_email_funcionario = NULLIF(?, ''),
                 ds_tel_funcionario = NULLIF(?, ''), ds_nivel_funcionario = ?
             WHERE cd_funcionario = ?"
        );
        $stmt->bind_param('ssssi', $nome, $email, $telefone, $nivel, $codigo);
        $stmt->execute();
        $mensagem = 'Funcionário atualizado com sucesso!';
        $tipoMensagem = 'success';
    }
}

if (isset($_POST['excluirFuncionario'])) {
    $codigo = (int) ($_POST['idFuncionarioExcluir'] ?? 0);

    if ($codigo === (int) $_SESSION['id']) {
        $mensagem = 'Você não pode excluir o próprio usuário administrador.';
        $tipoMensagem = 'warning';
    } else {
        $stmt = $conn->prepare(
            'SELECT
                (SELECT COUNT(*) FROM tb_vendas WHERE id_funcionario = ?) AS vendas,
                (SELECT COUNT(*) FROM tb_entradas WHERE id_usuario = ?) AS entradas'
        );
        $stmt->bind_param('ii', $codigo, $codigo);
        $stmt->execute();
        $vinculos = $stmt->get_result()->fetch_assoc();

        if ((int) $vinculos['vendas'] > 0 || (int) $vinculos['entradas'] > 0) {
            $mensagem = 'Este funcionário não pode ser excluído porque possui registros relacionados.';
            $tipoMensagem = 'warning';
        } else {
            $stmt = $conn->prepare('DELETE FROM tb_funcionarios WHERE cd_funcionario = ?');
            $stmt->bind_param('i', $codigo);
            if ($stmt->execute()) {
                $mensagem = 'Funcionário excluído com sucesso!';
                $tipoMensagem = 'success';
            } else {
                $mensagem = 'Erro ao excluir funcionário: ' . $stmt->error;
                $tipoMensagem = 'danger';
            }
        }
    }
}

$pesquisa = trim($_GET['pesquisa'] ?? '');
if ($pesquisa !== '') {
    $stmt = $conn->prepare(
        'SELECT cd_funcionario, nm_funcionario, ds_email_funcionario, ds_tel_funcionario, ds_nivel_funcionario
         FROM tb_funcionarios
         WHERE nm_funcionario LIKE ? OR ds_email_funcionario LIKE ?
         ORDER BY nm_funcionario'
    );
    $termo = "%$pesquisa%";
    $stmt->bind_param('ss', $termo, $termo);
    $stmt->execute();
    $funcionarios = $stmt->get_result();
} else {
    $funcionarios = $conn->query(
        'SELECT cd_funcionario, nm_funcionario, ds_email_funcionario, ds_tel_funcionario, ds_nivel_funcionario
         FROM tb_funcionarios ORDER BY nm_funcionario'
    );
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração</title>
    <style>
        body {
            background: #f4f7fb;
            color: 'black';
        }
    </style>
</head>

<body>
    <div class="container-fluid pt-3 ">
        <?php if ($mensagem !== ''): ?>
            <div class="alert alert-<?= $tipoMensagem ?> alert-dismissible fade show" role="alert">
                <?= $mensagem ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <div>
                <h1 class="mb-1">Funcionários</h1>
            </div>
            <button class="btn btn-success" type="button" data-bs-toggle="modal" data-bs-target="#modalCadastrar">
                <i class="bi bi-plus-lg"></i> Cadastrar funcionário
            </button>
        </div>

        <form method="GET" class="mb-3">
            <div class="input-group">
                <label class="visually-hidden" for="pesquisa">Pesquisar funcionários</label>
                <input class="form-control" id="pesquisa" name="pesquisa" value="<?= $pesquisa ?>" placeholder="Pesquisar por nome ou e-mail">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>

        <div class="table-responsive bg-white rounded shadow-sm">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Nível</th>
                        <th class="text-end">Opções</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($funcionario = $funcionarios->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int) $funcionario['cd_funcionario'] ?></td>
                            <td><strong><?= $funcionario['nm_funcionario'] ?></strong></td>
                            <td><?= $funcionario['ds_email_funcionario'] ?? '-' ?></td>
                            <td><?= $funcionario['ds_tel_funcionario'] ?? '-' ?></td>
                            <td><span class="badge text-bg-<?= $funcionario['ds_nivel_funcionario'] == 'admin' ? 'primary' : 'secondary' ?>"><?= $funcionario['ds_nivel_funcionario'] ?></span></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-warning" type="button" onclick='abrirEdicao(<?= json_encode($funcionario) ?>)'><i class="bi bi-pencil"></i> Editar</button>
                                <button class="btn btn-sm btn-danger" type="button" onclick='abrirExclusao(<?= (int) $funcionario['cd_funcionario'] ?>, <?= json_encode($funcionario['nm_funcionario']) ?>)'><i class="bi bi-trash"></i> Excluir</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <a href="index.php" class="btn btn-secondary w-100">
            página principal
        </a>
    </div>
    <!-- Modal Cadastrar -->
    <div class="modal fade" id="modalCadastrar" tabindex="-1" aria-labelledby="tituloCadastrar" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="tituloCadastrar">Cadastrar funcionário</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="cadastrarNome">Nome *</label><input class="form-control mb-3" id="cadastrarNome" name="nomeFuncionario" required>
                        <label class="form-label" for="cadastrarSenha">Senha *</label><input class="form-control mb-3" type="password" id="cadastrarSenha" name="senhaFuncionario" required minlength="6">
                        <label class="form-label" for="cadastrarEmail">E-mail</label><input class="form-control mb-3" type="email" id="cadastrarEmail" name="emailFuncionario">
                        <label class="form-label" for="cadastrarTelefone">Telefone</label><input class="form-control mb-3" id="cadastrarTelefone" name="telefoneFuncionario">
                        <label class="form-label" for="cadastrarNivel">Nível *</label><select class="form-select" id="cadastrarNivel" name="nivelFuncionario" required>
                            <option value="usuario">Funcionário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="modal-footer"><button class="btn btn-success" name="cadastrarFuncionario" type="submit">Cadastrar</button></div>
                </form>
            </div>
        </div>
    </div>
    <!-- Modal de edição e exclusão -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="tituloEditar" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="idFuncionario" id="editarId">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="tituloEditar">Editar funcionário</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="editarNome">Nome *</label><input class="form-control mb-3" id="editarNome" name="nomeFuncionario" required>
                        <label class="form-label" for="editarSenha">Nova senha</label><input class="form-control mb-3" type="password" id="editarSenha" name="senhaFuncionario" minlength="6"><small class="text-muted d-block mb-3">Deixe vazia para manter a senha atual.</small>
                        <label class="form-label" for="editarEmail">E-mail</label><input class="form-control mb-3" type="email" id="editarEmail" name="emailFuncionario">
                        <label class="form-label" for="editarTelefone">Telefone</label><input class="form-control mb-3" id="editarTelefone" name="telefoneFuncionario">
                        <label class="form-label" for="editarNivel">Nível *</label><select class="form-select" id="editarNivel" name="nivelFuncionario" required>
                            <option value="usuario">Funcionário</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="modal-footer"><button class="btn btn-warning" name="editarFuncionario" type="submit">Salvar alterações</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalExcluir" tabindex="-1" aria-labelledby="tituloExcluir" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="tituloExcluir">Excluir funcionário</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body"><input type="hidden" name="idFuncionarioExcluir" id="excluirId">
                        <p>Deseja excluir <strong id="excluirNome"></strong>?</p>
                    </div>
                    <div class="modal-footer"><button class="btn btn-danger" name="excluirFuncionario" type="submit">Excluir</button></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirEdicao(funcionario) {
            document.getElementById('editarId').value = funcionario.cd_funcionario;
            document.getElementById('editarNome').value = funcionario.nm_funcionario;
            document.getElementById('editarSenha').value = '';
            document.getElementById('editarEmail').value = funcionario.ds_email_funcionario || '';
            document.getElementById('editarTelefone').value = funcionario.ds_tel_funcionario || '';
            document.getElementById('editarNivel').value = funcionario.ds_nivel_funcionario;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditar')).show();
        }

        function abrirExclusao(id, nome) {
            document.getElementById('excluirId').value = id;
            document.getElementById('excluirNome').textContent = nome;
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExcluir')).show();
        }
    </script>
</body>

</html>