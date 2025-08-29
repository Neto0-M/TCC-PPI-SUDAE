<?php 
include 'conexao.php';

$mensagem = '';
$sucesso = false;


if (isset($_POST['cadastrar'])) {
    $nome = $_POST['nome'];
    $matricula = $_POST['matricula'];
    $senha = md5($_POST['senha']);
    $tipo = $_POST['tipo'];
    $login = $matricula;

    $sql = "INSERT INTO usuario (nome, matricula, login, senha, tipo) 
        VALUES ('$nome', '$matricula', '$login', '$senha', '$tipo')";
    if ($conexao->query($sql)) {
        $mensagem = "Cadastro realizado com sucesso!";
        $sucesso = true;
    } else {
        $mensagem = "Erro: " . $conexao->error;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastro - SUDAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-color: #e6f4ec;
      font-family: 'Segoe UI', sans-serif;
    }
    .cadastro-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .cadastro-box {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 500px;
    }
    .cadastro-title {
      color: #198754;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }
    .btn-cadastrar {
      background-color: #198754;
      border: none;
    }
    .btn-cadastrar:hover {
      background-color: #146c43;
    }
    .form-control:focus {
      box-shadow: none;
      border-color: #198754;
    }
    .cadastro-links a {
      color: #198754;
      text-decoration: none;
    }
    .cadastro-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="cadastro-container">
  <div class="cadastro-box">
    <h3 class="text-center cadastro-title">Cadastro - SUDAE</h3>

    <?php if ($mensagem): ?>
      <div class="alert <?= $sucesso ? 'alert-success' : 'alert-danger' ?> text-center">
        <?= $mensagem ?>
      </div>
    <?php endif; ?>

    <?php if (!$sucesso): ?>
      <form method="POST">
        <div class="mb-3">
          <label for="nome" class="form-label">Nome</label>
          <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="matricula" class="form-label">Matricula</label>
          <input type="matricula" name="matricula" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="senha" class="form-label">Senha</label>
          <input type="password" name="senha" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="tipo" class="form-label">Tipo de Usuário</label>
          <select name="tipo" class="form-select" required>
            <option value="3">Aluno</option>
            <option value="2">Professor</option>
            <option value="1">Servidor AE</option>
          </select>
        </div>
        <div class="d-grid mb-3">
          <button type="submit" name="cadastrar" class="btn btn-cadastrar text-white">Cadastrar</button>
        </div>
      </form>
    <?php else: ?>
      <div class="text-center mt-3">
        <a href="login.php" name="login" class="btn btn-success">Ir para login</a>
      </div>
    <?php endif; ?>

    <div class="cadastro-links text-center mt-3">
      <a href="login.php">Já tem conta? Faça login</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
