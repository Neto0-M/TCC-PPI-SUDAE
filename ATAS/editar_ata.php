<?php
require_once '../conexao.php';
session_start();

// Verifica login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

$idUsuario = $_SESSION['usuario']['idUSUARIO'];

// Verifica se o ID foi enviado
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ata.php");
    exit;
}

$idAta = intval($_GET['id']);
$msg = "";

// ====== Atualizar ATA ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = date("Y-m-d H:i:s", strtotime($_POST['data']));
    $assunto = $_POST['assunto'];
    $anotacoes = $_POST['anotacoes'];
    $encaminhamentos = $_POST['encaminhamentos'];
    $participantes = $_POST['participantes'] ?? [];

    // Atualiza dados principais
    $sql = "UPDATE ATA SET data=?, assunto=?, anotacoes=?, encaminhamentos=? WHERE idATA=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $data, $assunto, $anotacoes, $encaminhamentos, $idAta);

    if ($stmt->execute()) {
        // Remove os antigos participantes
        $conn->query("DELETE FROM PARTICIPANTES WHERE idAta = $idAta");

        // Adiciona novamente os participantes selecionados
        if (!empty($participantes)) {
            $sql_part = "INSERT INTO PARTICIPANTES (idAta, idUSUARIO, dataRegistro, assinatura)
                         VALUES (?, ?, NOW(), 'N')";
            $stmt_part = $conn->prepare($sql_part);
            foreach ($participantes as $idPart) {
                if (is_numeric($idPart)) {
                    $stmt_part->bind_param("ii", $idAta, $idPart);
                    $stmt_part->execute();
                }
            }
        }

        $msg = "<div class='alert alert-success'>ATA atualizada com sucesso!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Erro ao atualizar ATA: " . $stmt->error . "</div>";
    }
}

// ====== Buscar dados da ATA ======
$sql_ata = "SELECT * FROM ATA WHERE idATA=?";
$stmt = $conn->prepare($sql_ata);
$stmt->bind_param("i", $idAta);
$stmt->execute();
$ata = $stmt->get_result()->fetch_assoc();

// ====== Buscar participantes ======
$participantes_atuais = [];
$res_part = $conn->query("SELECT idUSUARIO FROM PARTICIPANTES WHERE idAta=$idAta");
while ($row = $res_part->fetch_assoc()) {
    $participantes_atuais[] = $row['idUSUARIO'];
}

// ====== Buscar usuários ======
$alunos = $conn->query("SELECT idUSUARIO, nome FROM USUARIO WHERE tipo = 3 ORDER BY nome ASC");
$professores = $conn->query("SELECT idUSUARIO, nome FROM USUARIO WHERE tipo = 2 ORDER BY nome ASC");
$servidores = $conn->query("SELECT idUSUARIO, nome FROM USUARIO WHERE tipo = 1 ORDER BY nome ASC");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Editar ATA - SUDAE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
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
    main {
      max-width: 1200px;
      margin: 40px auto;
      background: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .coluna-participantes {
      max-height: 300px;
      overflow-y: auto;
      border: 1px solid #dceee2;
      border-radius: 8px;
      padding: 10px;
      background-color: #f8fdf9;
    }
  </style>
</head>
<body>

<header>
  <img src="../assets/img/SUDAE.svg" alt="Logo SUDAE" class="logo">
  <h1>Editar ATA - Sistema Unificado da Assistência Estudantil</h1>
  <nav>
    <a href="../DASHBOARD/dashboard.php" class="btn btn-outline-secondary btn-sm me-2">Dashboard</a>
    <a href="../LOGIN/logout.php" class="btn btn-danger btn-sm">Sair</a>
  </nav>
</header>

<main>
  <h3 class="text-success mb-4">Editar ATA #<?= $idAta ?></h3>

  <?= $msg ?>

  <form method="POST">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Data</label>
        <input type="datetime-local" name="data" class="form-control"
               value="<?= date('Y-m-d\TH:i', strtotime($ata['data'])) ?>" required>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Assunto</label>
        <input type="text" name="assunto" class="form-control" value="<?= htmlspecialchars($ata['assunto']) ?>" required>
      </div>
    </div>

    <div class="mt-3">
      <label class="form-label fw-semibold">Conteúdo da ATA</label>
      <textarea name="anotacoes" class="form-control" rows="3" required><?= htmlspecialchars($ata['anotacoes']) ?></textarea>
    </div>

    <div class="mt-3">
      <label class="form-label fw-semibold">Encaminhamentos</label>
      <textarea name="encaminhamentos" class="form-control" rows="3"><?= htmlspecialchars($ata['encaminhamentos']) ?></textarea>
    </div>

    <h5 class="text-success mb-3 mt-3">Selecionar Participantes</h5>
    <div class="row g-4">
      <!-- Alunos -->
      <div class="col-md-4">
        <h6 class="text-center text-success">Alunos</h6>
        <input type="text" class="form-control form-control-sm mb-2" placeholder="Pesquisar aluno..." onkeyup="filtrar(this, 'aluno-lista')">
        <div class="coluna-participantes" id="aluno-lista">
          <?php while ($a = $alunos->fetch_assoc()): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="participantes[]" value="<?= $a['idUSUARIO'] ?>"
                     <?= in_array($a['idUSUARIO'], $participantes_atuais) ? 'checked' : '' ?>>
              <label class="form-check-label"><?= htmlspecialchars($a['nome']) ?></label>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Professores -->
      <div class="col-md-4">
        <h6 class="text-center text-success">Professores</h6>
        <input type="text" class="form-control form-control-sm mb-2" placeholder="Pesquisar professor..." onkeyup="filtrar(this, 'prof-lista')">
        <div class="coluna-participantes" id="prof-lista">
          <?php while ($p = $professores->fetch_assoc()): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="participantes[]" value="<?= $p['idUSUARIO'] ?>"
                     <?= in_array($p['idUSUARIO'], $participantes_atuais) ? 'checked' : '' ?>>
              <label class="form-check-label"><?= htmlspecialchars($p['nome']) ?></label>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <!-- Servidores -->
      <div class="col-md-4">
        <h6 class="text-center text-success">Servidores</h6>
        <input type="text" class="form-control form-control-sm mb-2" placeholder="Pesquisar servidor..." onkeyup="filtrar(this, 'serv-lista')">
        <div class="coluna-participantes" id="serv-lista">
          <?php while ($s = $servidores->fetch_assoc()): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="participantes[]" value="<?= $s['idUSUARIO'] ?>"
                     <?= in_array($s['idUSUARIO'], $participantes_atuais) ? 'checked' : '' ?>>
              <label class="form-check-label"><?= htmlspecialchars($s['nome']) ?></label>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-3 mt-4">
      <a href="ata.php" class="btn btn-secondary px-4">Cancelar</a>
      <button type="submit" class="btn btn-success px-4">Salvar Alterações</button>
    </div>
  </form>
</main>

<script>
  function filtrar(input, listaId) {
    const filtro = input.value.toLowerCase();
    document.querySelectorAll(`#${listaId} .form-check`).forEach(item => {
      const nome = item.textContent.toLowerCase();
      item.style.display = nome.includes(filtro) ? '' : 'none';
    });
  }
</script>
</body>
</html>