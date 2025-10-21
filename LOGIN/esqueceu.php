<?php
include '../conexao.php';

$etapa = 1; // 1 = pedir matrícula, 2 = redefinir senha
$mensagem = '';

if (isset($_POST['verificar'])) {
  $matricula = trim($_POST['matricula']);
  $sql = "SELECT * FROM usuario WHERE matricula = ?";
  $stmt = $conexao->prepare($sql);
  $stmt->bind_param("s", $matricula);
  $stmt->execute();
  $res = $stmt->get_result();

  if ($res->num_rows === 1) {
    $etapa = 2; // matrícula encontrada, pode redefinir
  } else {
    $mensagem = "<div class='alert alert-danger mt-3'>Matrícula não encontrada!</div>";
  }
}

if (isset($_POST['redefinir'])) {
  $nova_senha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
  $matricula = $_POST['matricula'];

  $sql = "UPDATE usuario SET senha=? WHERE matricula=?";
  $stmt = $conexao->prepare($sql);
  $stmt->bind_param("ss", $nova_senha, $matricula);

  if ($stmt->execute()) {
    $mensagem = "<div class='alert alert-success mt-3'>Senha redefinida com sucesso! <a href='login.php'>Fazer login</a></div>";
    $etapa = 0; // finalizado
  } else {
    $mensagem = "<div class='alert alert-danger mt-3'>Erro ao redefinir senha.</div>";
  }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Esqueci a Senha - SUDAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #e6f4ec;
      font-family: 'Segoe UI', sans-serif;
    }

    .container-reset {
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .reset-box {
      background: #fff;
      padding: 2rem;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
      width: 100%;
      max-width: 500px;
    }

    .reset-box h2 {
      color: #198754;
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .form-control:focus {
      border-color: #198754;
      box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
    }
  </style>
</head>

<body>

  <div class="container-reset">
    <div class="reset-box">
      <h2>Redefinir Senha</h2>

      <?php if ($etapa === 1): ?>
        <form method="POST">
          <div class="mb-3">
            <label for="matricula" class="form-label">Informe sua matrícula</label>
            <input type="text" class="form-control" name="matricula" id="matricula" required>
          </div>
          <button type="submit" name="verificar" class="btn btn-success w-100">Continuar</button>
        </form>

      <?php elseif ($etapa === 2): ?>
        <form method="POST">
          <div class="mb-3">
            <label for="nova_senha" class="form-label">Nova senha</label>
            <input type="password" class="form-control" name="nova_senha" id="nova_senha" required pattern=".{8,}"
              title="A senha deve ter no mínimo 8 caracteres.">
          </div>
          <input type="hidden" name="matricula" value="<?= htmlspecialchars($matricula) ?>">
          <button type="submit" name="redefinir" class="btn btn-success w-100">Redefinir Senha</button>
        </form>
      <?php endif; ?>

      <?= $mensagem ?>

      <div class="mt-3 text-center">
        <a href="login.php" class="text-decoration-none">Voltar ao login</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>