<?php include 'conexao.php';

$mensagem = '';
$linkReset = '';

if (isset($_POST['recuperar'])) {
    $matricula = $_POST['matricula'];

    $sql = "SELECT * FROM usuarios WHERE matricula='$matricula'";
    $res = $conexao->query($sql);

    if ($res->num_rows === 1) {
        $linkReset = "redefinir.php?matricula=$matricula";
        $mensagem = "<strong>Matricula encontrada.</strong> <br><a href='$linkReset' class='btn btn-success mt-2'>Clique aqui para redefinir sua senha</a>";
    } else {
        $mensagem = "<span class='text-danger'>Matricula não encontrada.</span>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Recuperar Senha - SUDAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #e6f4ec;
      font-family: 'Segoe UI', sans-serif;
    }
    .recuperar-container {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .recuperar-box {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 500px;
    }
    .recuperar-title {
      color: #198754;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }
    .btn-recuperar {
      background-color: #198754;
      border: none;
    }
    .btn-recuperar:hover {
      background-color: #146c43;
    }
    .form-control:focus {
      box-shadow: none;
      border-color: #198754;
    }
    .recuperar-links a {
      color: #198754;
      text-decoration: none;
    }
    .recuperar-links a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="recuperar-container">
  <div class="recuperar-box">
    <h3 class="text-center recuperar-title">Recuperar Senha</h3>

    <?php if ($mensagem): ?>
      <div class="alert alert-info text-center"><?= $mensagem ?></div>
    <?php endif; ?>

    <?php if (!$linkReset): ?>
      <form method="POST">
        <div class="mb-3">
          <label for="matricula" class="form-label">Digite sua matricula</label>
          <input type="matricula" name="matricula" class="form-control" required>
        </div>
        <div class="d-grid mb-3">
          <button type="submit" name="recuperar" class="btn btn-recuperar text-white">Recuperar</button>
        </div>
      </form>
    <?php endif; ?>

    <div class="recuperar-links text-center mt-3">
      <a href="login.php">Voltar ao login</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
