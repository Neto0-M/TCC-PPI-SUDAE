<?php
session_start();


if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$nome = $_SESSION['usuario']['nome'];
$tipo = $_SESSION['usuario']['tipo'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Painel - SUDAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #e6f4ec;
      font-family: 'Segoe UI', sans-serif;
    }
    .painel-container {
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .painel-box {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      max-width: 600px;
      width: 100%;
      text-align: center;
    }
    .painel-box h2 {
      color: #198754;
      font-weight: bold;
      margin-bottom: 1.5rem;
    }
    .painel-info {
      font-size: 18px;
      margin-bottom: 1rem;
    }
    .btn-sair {
      background-color: #dc3545;
      border: none;
    }
    .btn-sair:hover {
      background-color: #c82333;
    }
  </style>
</head>
<body>

<div class="painel-container">
  <div class="painel-box">
    <h2>Painel SUDAE</h2>
    <p class="painel-info">Bem-vindo, <strong><?= htmlspecialchars($nome) ?></strong>!<br>
    Tipo de usuário: <strong><?= ucfirst($tipo) ?></strong></p>

    <?php if ($tipo === 'servidor_ae'): ?>
      <a href="gerenciar_atas.php" class="btn btn-success me-2">Gerenciar Atas</a>
      <a href="gerenciar_faltas.php" class="btn btn-warning">Registrar Faltas</a>
    <?php elseif ($tipo === 'professor' || $tipo === 'aluno'): ?>
      <a href="consultar.php" class="btn btn-success">Consultar Faltas e Atas</a>
    <?php endif; ?>

    <div class="mt-4">
      <a href="logout.php" class="btn btn-sair text-white">Sair</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

