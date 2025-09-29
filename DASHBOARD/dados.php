<?php
session_start();
require_once '../conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../LOGIN/login.php');
    exit;
}

// Captura os dados da sessão

$matricula = $_SESSION['usuario']['matricula'] ;

// Verifica se a sessão tem os campos essenciais


$stmt = $conexao->prepare("SELECT nome, matricula, tipo FROM USUARIO WHERE matricula = ?");
$stmt->bind_param("s", $matricula);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();




$qrcode = "https://quickchart.io/qr?text=" . urlencode($matricula);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Meu Perfil - SUDAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #e6f4ec;
      font-family: 'Segoe UI', sans-serif;
    }
    .perfil-container {
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .perfil-box {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      max-width: 500px;
      width: 100%;
    }
    .perfil-box h2 {
      color: #198754;
      font-weight: bold;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    .perfil-info p {
      font-size: 18px;
      margin: 0.5rem 0;
    }
  </style>
</head>
<body>

<div class="perfil-container">
  <div class="perfil-box">
    <h2>Meu Perfil</h2>
    <?php if ($usuario): ?>
      <div class="perfil-info">
        <p><strong>Nome:</strong> <?= htmlspecialchars($usuario['nome']) ?></p>
        <p><strong>Matrícula:</strong> <?= htmlspecialchars($usuario['matricula']) ?></p>
        <p><strong>Tipo de Usuário:</strong> <?= ucfirst($usuario['tipo']) ?></p>
        <p><strong>QRCode da matrícula: </strong> </p>
        <img src=<?= $qrcode?> >
      </div>
    <?php else: ?>
      <div class="alert alert-danger">Usuário não encontrado.</div>
    <?php endif; ?>

    <div class="mt-4 d-flex justify-content-between">
      <a href="dashboard.php" class="btn btn-success">Voltar ao Dashboard</a>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>