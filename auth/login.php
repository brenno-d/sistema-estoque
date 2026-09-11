<?php
include_once '../inc/DBConn.php';

session_start();

if (isset($_SESSION['id'])) {
    if (($_SESSION['nivel'] ?? '') === 'admin') {
        header('Location: ../admin.php');
    } else {
        header('Location: ../index.php');
    }
    exit();
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['name'] ?? '');
    $senhaForm = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT * FROM tb_funcionarios WHERE nm_funcionario = ?');
    $stmt->bind_param('s', $nome);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($funcionario = $resultado->fetch_assoc()) {
        if (password_verify($senhaForm, $funcionario['ds_senha'])) {
            session_regenerate_id(true);
            $_SESSION['nome'] = $funcionario['nm_funcionario'];
            $_SESSION['id'] = $funcionario['cd_funcionario'];
            $_SESSION['nivel'] = strtolower($funcionario['ds_nivel_funcionario']);

            header('Location: ' . ($_SESSION['nivel'] === 'admin' ? '../admin.php' : '../index.php'));
            exit();
        }

        $mensagem = 'Senha incorreta!';
    } else {
        $mensagem = 'Usuário não encontrado!';
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            background: #f4f7fb;
            color: 'black';
        }
    </style>
</head>

<body class="bg-light">
    <main class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
        <div class="card shadow-sm border-0 w-100" style="max-width: 420px;">
            <div class="card-body p-4">
                <h1 class="h3 text-center mb-4">Controle de estoque</h1>

            <?php if ($mensagem !== ''): ?>
                <div class="alert alert-danger" role="alert">
                    <?= $mensagem ?>
                </div>
            <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="name" class="form-label">Seu nome</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Sua senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" name="dados" class="btn btn-success w-100">Entrar</button>
                </form>
            </div>
        </div>
    </main>

</body>

</html>

