<?php
require_once '../conexao.php';
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

$idRedator = $_SESSION['usuario']['idUSUARIO'];

// ====== Cadastrar nova ATA ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $data = date("Y-m-d H:i:s", strtotime($_POST['data']));
    $assunto = $_POST['assunto'];
    $anotacoes = $_POST['anotacoes'];
    $encaminhamentos = $_POST['encaminhamentos'];
    $participantes = $_POST['participantes'] ?? [];

    $sql = "INSERT INTO ATA (data, assunto, anotacoes, encaminhamentos, idRedator)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $data, $assunto, $anotacoes, $encaminhamentos, $idRedator);

    if ($stmt->execute()) {
        $idAta = $stmt->insert_id;

        if (!empty($participantes)) {
            $sql_part = "INSERT INTO PARTICIPANTES (idAta, idUSUARIO, dataRegistro, assinatura)
                         VALUES (?, ?, NOW(), 'N')";
            $stmt_part = $conn->prepare($sql_part);
            foreach ($participantes as $idUsuario) {
                if (is_numeric($idUsuario)) {
                    $stmt_part->bind_param("ii", $idAta, $idUsuario);
                    $stmt_part->execute();
                }
            }
            $msg = "<div class='alert alert-success'>ATA cadastrada com sucesso com " . count($participantes) . " participante(s)!</div>";
        } else {
            $msg = "<div class='alert alert-warning'>ATA cadastrada, mas sem participantes vinculados.</div>";
        }
    } else {
        $msg = "<div class='alert alert-danger'>Erro ao cadastrar ATA: " . $stmt->error . "</div>";
    }
}

// ====== Excluir ATA ======
if (isset($_GET['delete'])) {
    $idATA = $_GET['delete'];
    $sql = "DELETE FROM ATA WHERE idATA=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idATA);
    $stmt->execute();
}

// ====== Buscar participantes por tipo ======
$alunos = $conn->query("SELECT idUSUARIO, nome FROM USUARIO WHERE tipo = 3 ORDER BY nome ASC");
$professores = $conn->query("SELECT idUSUARIO, nome FROM USUARIO WHERE tipo = 2 ORDER BY nome ASC");
$servidores = $conn->query("SELECT idUSUARIO, nome FROM USUARIO WHERE tipo = 1 ORDER BY nome ASC");

// ====== Listar ATAs ======
$sql_listar = "SELECT A.idATA, A.data, A.assunto, U.nome AS redator,
                      GROUP_CONCAT(PU.nome SEPARATOR ', ') AS participantes
               FROM ATA A
               INNER JOIN USUARIO U ON A.idRedator = U.idUSUARIO
               LEFT JOIN PARTICIPANTES P ON A.idATA = P.idAta
               LEFT JOIN USUARIO PU ON P.idUSUARIO = PU.idUSUARIO
               GROUP BY A.idATA
               ORDER BY A.data DESC";
$result = $conn->query($sql_listar);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastro de ATAs - SUDAE</title>
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
    footer {
      text-align: center;
      padding: 10px;
      color: #666;
      font-size: 0.9rem;
      margin-top: auto;

    }
  </style>
</head>
<body>

<header>
  <img src="../assets/img/SUDAE.svg" alt="Logo SUDAE" class="logo">
  <h1>Sistema Unificado da Assistência Estudantil</h1>
  <nav>
    <a href="../LOGIN/cadastro.php" class="btn btn-outline-success btn-sm">Cadastrar</a>
    <a href="../DASHBOARD/dados.php" class="btn btn-outline-secondary btn-sm me-2">Meus Dados</a>
    <a href="../LOGIN/logout.php" class="btn btn-danger btn-sm">Sair</a>
  </nav>
</header>

<main>
  <h3 class="text-success mb-4">Cadastrar nova ATA</h3>

  <?php if (!empty($msg)) echo $msg; ?>

  <form method="POST" class="mb-5">
    <input type="hidden" name="acao" value="cadastrar">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label fw-semibold">Data</label>
        <input type="datetime-local" name="data" class="form-control" required>
      </div>

      <div class="col-md-6">
        <label class="form-label fw-semibold">Assunto</label>
        <input type="text" name="assunto" class="form-control" required>
      </div>
    </div>

    <div class="mt-3">
      <label class="form-label fw-semibold">Conteúdo da ATA</label>
      <textarea name="anotacoes" class="form-control" rows="3" required></textarea>
    </div>

    <div class="mt-3">
      <label class="form-label fw-semibold">Encaminhamentos</label>
      <textarea name="encaminhamentos" class="form-control" rows="3"></textarea>
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
              <input class="form-check-input" type="checkbox" name="participantes[]" value="<?= $a['idUSUARIO'] ?>">
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
              <input class="form-check-input" type="checkbox" name="participantes[]" value="<?= $p['idUSUARIO'] ?>">
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
              <input class="form-check-input" type="checkbox" name="participantes[]" value="<?= $s['idUSUARIO'] ?>">
              <label class="form-check-label"><?= htmlspecialchars($s['nome']) ?></label>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <div class="d-flex justify-content-end gap-3 mt-4">
      <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary px-4">Voltar</a>
      <button type="submit" class="btn btn-success px-4">Salvar ATA</button>
    </div>
  </form>

  <h4 class="text-success mb-3">ATAs Registradas</h4>
  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-success">
        <tr>
          <th>ID</th>
          <th>Data</th>
          <th>Assunto</th>
          <th>Redator</th>
          <th>Participantes</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($ata = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $ata['idATA']; ?></td>
              <td><?= $ata['data']; ?></td>
              <td><?= htmlspecialchars($ata['assunto']); ?></td>
              <td><?= htmlspecialchars($ata['redator']); ?></td>
              <td><?= htmlspecialchars($ata['participantes'] ?: 'Nenhum'); ?></td>
              <td>
                <a href="?delete=<?= $ata['idATA']; ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Deseja excluir esta ATA?')">
                   <i class="bi bi-trash"></i> Excluir
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="6" class="text-center text-muted">Nenhuma ATA encontrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>

  <footer>
    <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
  </footer>

<script>
  // Filtro de busca
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
