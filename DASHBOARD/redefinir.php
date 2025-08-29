<?php include 'conexao.php'; ?>
<?php $matricula = $_GET['matricula'] ?? ''; ?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Redefinir Senha - SUDAE</title>
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
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
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
    <form method="POST">
      <div class="mb-3">
        <label for="nova_senha" class="form-label">Nova senha</label>
        <input type="password" class="form-control" name="nova_senha" id="nova_senha" required>
      </div>
      <input type="hidden" name="matricula" value="<?= htmlspecialchars($matricula) ?>">
      <button type="submit" name="redefinir" class="btn btn-success w-100">Redefinir</button>
    </form>
    <div class="mt-3 text-center">
      <a href="login.php" class="text-decoration-none">Voltar ao login</a>
    </div>

    <?php
    if (isset($_POST['redefinir'])) {
        $nova = $_POST['nova_senha'];
        $matricula = $_POST['matricula'];

        $sql = "UPDATE usuarios SET senha='$nova' WHERE matricula='$matricula'";
        if ($conexao->query($sql)) {
            echo "<div class='alert alert-success mt-3'>Senha redefinida com sucesso! <a href='login.php'>Fazer login</a></div>";
        } else {
            echo "<div class='alert alert-danger mt-3'>Erro ao redefinir senha.</div>";
        }
    }
    ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

