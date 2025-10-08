<?php
session_start();
require_once '../conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../LOGIN/login.php');
    exit;
}

$matricula = $_SESSION['usuario']['matricula'];

// Busca dados do usuário
$stmt = $conexao->prepare("SELECT nome, matricula, email, tipo, curso, turma FROM USUARIO WHERE matricula = ?");
$stmt->bind_param("s", $matricula);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();

$tipo = $usuario['tipo']; 

// Atualização de dados
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $curso = trim($_POST['curso']);
    $turma = trim($_POST['turma']);
    $novaSenha = $_POST['senha'];

    if (!empty($novaSenha)) {
        $stmt = $conexao->prepare("UPDATE USUARIO SET nome=?, email=?, curso=?, turma=?, senha=? WHERE matricula=?");
        $stmt->bind_param("ssssss", $nome, $email, $curso, $turma, $novaSenha, $matricula);
    } else {
        $stmt = $conexao->prepare("UPDATE USUARIO SET nome=?, email=?, curso=?, turma=? WHERE matricula=?");
        $stmt->bind_param("sssss", $nome, $email, $curso, $turma, $matricula);
    }

    if ($stmt->execute()) {
        $msg = "Dados atualizados com sucesso!";
        $_SESSION['usuario']['nome'] = $nome;
        $_SESSION['usuario']['email'] = $email;
    } else {
        $msg = "Erro ao atualizar: " . $conexao->error;
    }

    // Atualiza os dados exibidos
    $stmt = $conexao->prepare("SELECT nome, matricula, email, tipo, curso, turma FROM USUARIO WHERE matricula = ?");
    $stmt->bind_param("s", $matricula);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
}

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
      position: relative;
      min-height: 100vh;
    }

    .logo {
      position: absolute;
      left: 25px;
      width: 50px;
      height: auto;
    }

    header {
      background-color: #fff;
      padding: 15px 40px;
      border-bottom: 2px solid #dceee2;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      position: relative;
    }

    header h1 {
      position: absolute;
      left: 140px;
      font-size: 1.2rem;
      color: #198754;
      font-weight: bold;
      margin: 0;
    }

    .perfil-container {
      min-height: 10vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .perfil-box {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      max-width: 550px;
      width: 100%;
      margin-top: 50px;
    }

    .perfil-box h2 {
      color: #198754;
      font-weight: bold;
      text-align: center;
      margin-bottom: 1.5rem;
    }

    footer {
      position: absolute;
      width: 100%;
      text-align: center;
      color: #666;
      font-size: 0.9rem;
      padding-top: 5px;
      padding-bottom: 5px;
    }
  </style>
</head>
<body>

<header>
  <img src="../assets/img/SUDAE.svg" alt="Logo SUDAE" class="logo">
  <h1>Sistema Unificado da Assistência Estudantil</h1>
  <nav>
    <?php if ($tipo == 1): ?>
      <a href="../LOGIN/cadastro.php" class="btn btn-outline-success btn-sm">Cadastrar</a>
    <?php endif; ?>
    <a href="../LOGIN/logout.php" class="btn btn-danger btn-sm">Sair</a>
  </nav>
</header>

<div class="perfil-container">
  <div class="perfil-box">
    <h2>Meu Perfil</h2>

    <?php if (isset($msg)): ?>
      <div class="alert alert-info text-center"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Nome</label>
        <input type="text" name="nome" class="form-control" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($usuario['email']) ?>" required>
      </div>

      <?php if ($tipo == 3): ?>
                    
      <div class="mb-3">
        <label class="form-label">Curso</label>
        <input type="text" name="curso" class="form-control" value="<?= htmlspecialchars($usuario['curso'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Turma</label>
        <input type="text" name="turma" class="form-control" value="<?= htmlspecialchars($usuario['turma'] ?? '') ?>">
      </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">Nova senha (opcional)</label>
        <input type="password" name="senha" class="form-control" placeholder="Deixe em branco para manter a atual">
      </div>

      <div class="mb-3">
        <label class="form-label">Tipo de Usuário:</label>
        <input type="text" class="form-control" value="<?= ($usuario['tipo'] == 1) ? 'Servidor AE' : (($usuario['tipo'] == 2) ? 'Professor' : 'Aluno') ?>" disabled>
      </div>

      <div class="text-center mb-3">
        <p><strong>QRCode da matrícula:</strong></p>
        <img src="<?= $qrcode ?>" alt="QR Code da matrícula">
      </div>

      <div class="d-flex justify-content-between">
        <a href="dashboard.php" class="btn btn-secondary">Voltar</a>
        <button type="submit" class="btn btn-success">Salvar Alterações</button>
      </div>
    </form>
  </div>
</div>

<footer>
  <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
