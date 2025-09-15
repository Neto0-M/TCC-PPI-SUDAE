<?php
session_start();
include '../conexao.php';

$mensagem = '';
$sucesso = false;

if (isset($_POST['login'])) {
    $matricula = $_POST['matricula'];
    $senha = md5($_POST['senha']);

    $sql = "SELECT * FROM usuario WHERE matricula='$matricula' AND senha='$senha'";
    $res = $conexao->query($sql);

    if ($res->num_rows === 1) {
        $usuario = $res->fetch_assoc();

        // salva na sessão
        $_SESSION['usuario'] = [
            'idUSUARIO' => $usuario['idUSUARIO'],
            'nome'      => $usuario['nome'],
            'tipo'      => $usuario['tipo'],
            'matricula' => $usuario['matricula']
        ];

        header("Location: dashboard.php");
        exit;
    } else {
        $mensagem = "Login inválido!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Login - SUDAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-color: #e6f4ec;
      font-family: 'Segoe UI', sans-serif;
    }

    .login-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-box {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 400px;
    }

    .login-title {
      color: #198754;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }

    .btn-login {
      background-color: #198754;
      border: none;
    }

    .btn-login:hover {
      background-color: #146c43;
    }

    .form-control:focus {
      box-shadow: none;
      border-color: #198754;
    }

    .login-links a {
      color: #198754;
      text-decoration: none;
    }

    .login-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-box">
    <h3 class="text-center login-title">Login - SUDAE</h3>

    <?php if ($mensagem): ?>
      <div class="alert <?= $sucesso ? 'alert-success' : 'alert-danger' ?> text-center">
        <?= $mensagem ?>
      </div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
      <div class="text-center mt-3">
        <a href="dashboard.php" class="btn btn-success">Ir para o painel</a>
      </div>
    <?php else: ?>
      <form method="POST">
        <div class="mb-3">
          <label for="matricula" class="form-label">Matricula</label>
          <input type="matricula" name="matricula" class="form-control" required>
        </div>

        <div class="mb-3">
          <label for="senha" class="form-label">Senha</label>
          <input type="password" name="senha" class="form-control" required>
        </div>

        <div class="d-grid mb-3">
          <button type="submit" name="login" class="btn btn-login text-white">Entrar</button>
        </div>
      </form>

      <div class="login-links text-center">
        <a href="cadastro.php">Cadastrar-se</a> | 
        <a href="esqueceu.php">Esqueci minha senha</a>
      </div>
    <?php endif; ?>
  </div>
</div>

 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
