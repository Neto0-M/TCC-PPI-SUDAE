<?php
include '../conexao.php';

$mensagem = '';
$sucesso = false;

function validarSenha($senha)
{
  $erros = [];
  if (strlen($senha) < 8) {
    $erros[] = "A senha deve ter no mínimo 8 caracteres.";
  }
  return $erros;
}

if (isset($_POST['cadastrar'])) {
  $nome = $_POST['nome'];
  $matricula = $_POST['matricula'];
  $email = $_POST['email'];
  $senhaDigitada = $_POST['senha'];
  $tipo = $_POST['tipo'];
  $curso = $_POST['curso'] ?? null;
  $turma = $_POST['turma'] ?? null;
  $login = $matricula;

  $errosSenha = validarSenha($senhaDigitada);

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $mensagem = "Por favor, insira um e-mail válido.";
  } elseif (!empty($errosSenha)) {
    $mensagem = implode("<br>", $errosSenha);
  } else {
    $check = $conexao->prepare("SELECT matricula FROM usuario WHERE matricula = ?");
    $check->bind_param("s", $matricula);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      $mensagem = "Erro: Já existe um usuário cadastrado com esta matrícula.";
    } else {
      $senha = password_hash($senhaDigitada, PASSWORD_DEFAULT);

      $stmt = $conexao->prepare("INSERT INTO usuario (nome, matricula, login, senha, tipo, email, curso, turma) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssssisss", $nome, $matricula, $login, $senha, $tipo, $email, $curso, $turma);

      if ($stmt->execute()) {
        $mensagem = "Cadastro realizado com sucesso!";
        $sucesso = true;
      } else {
        $mensagem = "Erro: " . $conexao->error;
      }
    }
    $check->close();
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

    .cadastro-container {
      min-height: 90vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .cadastro-box {
      background: #fff;
      padding: 2.5rem;
      border-radius: 12px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
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

    #camposAluno {
      opacity: 0;
      max-height: 0;
      overflow: hidden;
      transition: all 0.5s ease;
    }

    #camposAluno.show {
      opacity: 1;
      max-height: 300px;
      margin-top: 10px;
    }

    footer {
      position: absolute;
      width: 100%;
      text-align: center;
      color: #666;
      font-size: 0.9rem;
      padding-bottom: 5px;
    }
  </style>
</head>

<body>

  <header>
    <img src="../assets/img/SUDAE.svg" alt="Logo SUDAE" class="logo">
    <h1>Sistema Unificado da Assistência Estudantil</h1>
    <nav>
      <a href="../DASHBOARD/dados.php" class="btn btn-outline-secondary btn-sm">Meus Dados</a>
      <a href="../LOGIN/logout.php" class="btn btn-danger btn-sm">Sair</a>
    </nav>
  </header>

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
            <label for="nome" class="form-label">Nome Completo</label>
            <input type="text" name="nome" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="matricula" class="form-label">Matrícula</label>
            <input type="text" name="matricula" class="form-control" required pattern=".{10,}"
              title="A matrícula deve ter no mínimo 10 caracteres.">
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">E-mail</label>
            <input type="email" name="email" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="senha" class="form-label">Senha</label>
            <input type="password" name="senha" class="form-control" required pattern=".{8,}"
              title="A senha deve ter no mínimo 8 caracteres.">
          </div>

          <div class="mb-3">
            <label for="tipo" class="form-label">Tipo de Usuário</label>
            <select name="tipo" id="tipo" class="form-select" required>
              <option value="">Selecione...</option>
              <option value="3">Aluno</option>
              <option value="2">Professor</option>
              <option value="1">Servidor AE</option>
            </select>
          </div>

          <div id="camposAluno">
            <div class="mb-3">
              <label for="curso" class="form-label">Curso</label>
              <input type="text" name="curso" class="form-control">
            </div>
            <div class="mb-3">
              <label for="turma" class="form-label">Turma</label>
              <input type="text" name="turma" class="form-control">
            </div>
          </div>

          <div class="d-grid mb-3">
            <button type="submit" name="cadastrar" class="btn btn-cadastrar text-white">Cadastrar</button>
          </div>
        </form>
      <?php else: ?>
        <div class="text-center mt-3">
          <a href="cadastro.php" class="btn btn-success">Cadastrar novo Usuário</a>
        </div>
      <?php endif; ?>

      <div class="text-center mt-3">
        <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary px-4">Voltar</a>
      </div>
    </div>
  </div>

  <footer>
    <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
  </footer>

  <script>
    const tipoSelect = document.getElementById('tipo');
    const camposAluno = document.getElementById('camposAluno');

    tipoSelect.addEventListener('change', function () {
      camposAluno.classList.remove('show');
      if (this.value === '3') {
        camposAluno.classList.add('show');
      }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>