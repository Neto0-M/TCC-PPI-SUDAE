<?php
session_start();
include '../conexao.php';

// Verifica login
if (!isset($_SESSION['usuario'])) {
    header('Location: ../LOGIN/login.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$tipo = $usuario['tipo'];
$idUsuario = $usuario['idUSUARIO'];

// Dias para filtros
$diasAtrasos = isset($_GET['diasAtrasos']) ? (int)$_GET['diasAtrasos'] : 30;
$diasAtas = isset($_GET['diasAtas']) ? (int)$_GET['diasAtas'] : 30;

/* ------------------ CONTAGEM DE ATRASOS ------------------ */
$qtdAtrasos = 0;

if ($tipo == 3) {
    // aluno
    $sqlAtrasos = "SELECT COUNT(*) AS total FROM ATRASO WHERE idAluno = ? AND data >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $stmtAtrasos = $conexao->prepare($sqlAtrasos);
    $stmtAtrasos->bind_param("ii", $idUsuario, $diasAtrasos);
    $stmtAtrasos->execute();
    $rowAtrasos = $stmtAtrasos->get_result()->fetch_assoc();
    $qtdAtrasos = (int)$rowAtrasos['total'];
} elseif ($tipo == 2) {
    // professor
    $sqlAtrasos = "SELECT COUNT(*) AS total FROM ATRASO WHERE idProfessor = ? AND data >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $stmtAtrasos = $conexao->prepare($sqlAtrasos);
    $stmtAtrasos->bind_param("ii", $idUsuario, $diasAtrasos);
    $stmtAtrasos->execute();
    $rowAtrasos = $stmtAtrasos->get_result()->fetch_assoc();
    $qtdAtrasos = (int)$rowAtrasos['total'];
} else {
    // servidor / outro
    $rowAtrasos = $conexao->query("SELECT COUNT(*) AS total FROM ATRASO WHERE data >= DATE_SUB(NOW(), INTERVAL $diasAtrasos DAY)")->fetch_assoc();
    $qtdAtrasos = (int)$rowAtrasos['total'];
}

/* ------------------ ATAS ------------------ */
if ($tipo == 1) {
    $sqlAtas = "
        SELECT a.idATA, a.assunto, a.`data`, a.anotacoes,
               GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS participantes,
               COUNT(u.idUSUARIO) AS qtd_participantes
        FROM ATA a
        LEFT JOIN PARTICIPANTES p ON a.idATA = p.idAta
        LEFT JOIN USUARIO u ON p.idUSUARIO = u.idUSUARIO
        WHERE a.data >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY a.idATA
        ORDER BY a.`data` DESC
    ";
    $stmtAtas = $conexao->prepare($sqlAtas);
    $stmtAtas->bind_param("i", $diasAtas);
    $stmtAtas->execute();
    $resAtas = $stmtAtas->get_result();

    $resQtd = $conexao->prepare("SELECT COUNT(*) AS total FROM ATA WHERE data >= DATE_SUB(NOW(), INTERVAL ? DAY)");
    $resQtd->bind_param("i", $diasAtas);
    $resQtd->execute();
    $resQtd = $resQtd->get_result();
} elseif ($tipo == 2 || $tipo == 3) {
    $sqlAtas = "
        SELECT a.idATA, a.assunto, a.`data`, a.anotacoes,
               GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS participantes,
               COUNT(u.idUSUARIO) AS qtd_participantes
        FROM ATA a
        LEFT JOIN PARTICIPANTES p ON a.idATA = p.idAta
        LEFT JOIN USUARIO u ON p.idUSUARIO = u.idUSUARIO
        WHERE a.idATA IN (
            SELECT idAta FROM PARTICIPANTES WHERE idUSUARIO = ?
        ) AND a.data >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY a.idATA
        ORDER BY a.`data` DESC
    ";
    $stmtAtas = $conexao->prepare($sqlAtas);
    $stmtAtas->bind_param("ii", $idUsuario, $diasAtas);
    $stmtAtas->execute();
    $resAtas = $stmtAtas->get_result();

    $stmtQtd = $conexao->prepare("
        SELECT COUNT(DISTINCT a.idATA) AS total
        FROM ATA a
        INNER JOIN PARTICIPANTES p ON a.idATA = p.idAta
        WHERE p.idUSUARIO = ? AND a.data >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmtQtd->bind_param("ii", $idUsuario, $diasAtas);
    $stmtQtd->execute();
    $resQtd = $stmtQtd->get_result();
}

$qtdAtas = $resQtd->fetch_assoc()['total'] ?? 0;

/* ------------------ ATRASOS RECENTES ------------------ */
$searchAluno = isset($_GET['searchAluno']) ? trim($_GET['searchAluno']) : '';

if ($searchAluno !== '') {
    $sqlAtrasosRecentes = "
        SELECT a.idATRASO, u.nome AS aluno, a.data, a.motivo
        FROM ATRASO a
        INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO
        WHERE a.data >= DATE_SUB(NOW(), INTERVAL ? DAY)
          AND u.nome LIKE CONCAT('%', ?, '%')
        ORDER BY a.data DESC
    ";
    $stmtRecentes = $conexao->prepare($sqlAtrasosRecentes);
    $stmtRecentes->bind_param("is", $diasAtrasos, $searchAluno);
} else {
    $sqlAtrasosRecentes = "
        SELECT a.idATRASO, u.nome AS aluno, a.data, a.motivo
        FROM ATRASO a
        INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO
        WHERE a.data >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY a.data DESC
    ";
    $stmtRecentes = $conexao->prepare($sqlAtrasosRecentes);
    $stmtRecentes->bind_param("i", $diasAtrasos);
}


$stmtRecentes->execute();
$resAtrasosRecentes = $stmtRecentes->get_result();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard - SUDAE</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background-color: #e6f4ec; font-family: 'Segoe UI', sans-serif; position: relative; min-height: 100vh; }
.logo { position: absolute; left: 25px; width: 50px; height: auto; }
header { background-color: #fff; padding: 15px 40px; border-bottom: 2px solid #dceee2; display: flex; justify-content: flex-end; align-items: center; position: relative; }
header h1 { position: absolute; left: 140px; font-size: 1.2rem; color: #198754; font-weight: bold; margin: 0; }
.container { margin-top: 10px; }
.card { border-radius: 12px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
.ata, .atraso { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 15px; box-shadow: 0 0 8px rgba(0,0,0,0.05); }
.ata h5, .atraso h5 { color: #198754; font-weight: bold; margin-bottom: 5px; }
.botao-grande-gap { gap: 27rem !important; }
.img-btn { width: 24px; padding-bottom: 3px; }
footer { position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    text-align: center;
    color: #666;
    font-size: 0.9rem;
    padding: 10px 0; }
.filtro-container { display: flex; align-items: center; gap: 10px; float: right; }
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
    <a href="dados.php" class="btn btn-outline-secondary btn-sm">Meus Dados</a>
    <a href="../LOGIN/logout.php" class="btn btn-danger btn-sm">Sair</a>
  </nav>
</header>

<div class="container">
  <div class="text-center mb-4">
    <h2 class="fw-bold text-success">Bem-vindo, <?= htmlspecialchars($usuario['nome']); ?>!</h2>
    <?php if ($tipo == 1): ?>
      <p class="text-muted">Painel do Servidor AE — Gerencie atrasos e atas</p>
    <?php elseif ($tipo == 2): ?>
      <p class="text-muted">Painel do Professor — Visualize atas e consulte atrasos.</p>
    <?php else: ?>
      <p class="text-muted">Painel do Aluno — Visualize seus atrasos e atas.</p>
    <?php endif; ?>
  </div>

  <!-- Cards superiores -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card text-center p-3">
        <h5>Total de ATAs</h5>
        <p class="display-6 fw-bold text-success"><?= $qtdAtas ?></p>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card text-center p-3">
        <h5>Total de Atrasos</h5>
        <p class="display-6 fw-bold text-danger"><?= $qtdAtrasos ?></p>
      </div>
    </div>
  </div>

  <!-- Botões lado a lado centralizados -->
  <?php if ($tipo == 1): ?>
  <div class="d-flex justify-content-center mb-5 flex-wrap botao-grande-gap">
    <a href="../ATAS/cadastrar_Ata.php" class="btn btn-success btn-lg px-4">
      <img src="../assets/img/ata.svg" alt="imagem ata" class="img-btn"> Registrar ATA
    </a>
    <a href="../ATRASOS/atrasos.php" class="btn btn-warning btn-lg px-4 text-white">
      <img src="../assets/img/atraso.svg" alt="imagem atraso" class="img-btn"> Registrar Atraso
    </a>
  </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- ATAs Recentes -->
    <div class="col-md-6">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="fw-bold text-success">ATAs Recentes</h4>
        <div class="filtro-container">
          <form method="get" class="m-0 p-0">
            <input type="hidden" name="diasAtrasos" value="<?= $diasAtrasos ?>">
            <select name="diasAtas" class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="30" <?= $diasAtas==30?'selected':'' ?>>Últimos 30 dias</option>
              <option value="15" <?= $diasAtas==15?'selected':'' ?>>Últimos 15 dias</option>
              <option value="7" <?= $diasAtas==7?'selected':'' ?>>Últimos 7 dias</option>
              <option value="1" <?= $diasAtas==1?'selected':'' ?>>Último dia</option>
            </select>
          </form>
        </div>
      </div>
      <?php if ($resAtas && $resAtas->num_rows > 0): ?>
        <?php while ($ata = $resAtas->fetch_assoc()): ?>
          <div class="ata mb-3">
            <h5><?= htmlspecialchars($ata['assunto']) ?></h5>
            <p><?= nl2br(htmlspecialchars($ata['anotacoes'])) ?></p>
            <small class="text-muted">
              <img src="../assets/img/data.svg" alt="data" class="img-btn">
              <?= date('d/m/Y H:i', strtotime($ata['data'])) ?><br>
              <img src="../assets/img/participantes.svg" alt="participantes" class="img-btn">
              <?= $ata['qtd_participantes'] ?> participantes<br>
              <em>Participantes:</em> <?= htmlspecialchars($ata['participantes']) ?>
            </small>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="alert alert-info text-center">Nenhuma ATA registrada ainda.</div>
      <?php endif; ?>
    </div>

<!-- Atrasos Recentes -->
<div class="col-md-6">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h4 class="fw-bold text-success">Atrasos Recentes</h4>
    <!-- Campo de busca e filtro de dias (lado a lado) -->
    <form method="get" class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <input type="hidden" name="diasAtas" value="<?= $diasAtas ?>">
      <div class="input-group input-group-sm" style="width: 250px;">
        <input type="text" name="searchAluno" class="form-control" placeholder="Pesquisar aluno..."
               value="<?= htmlspecialchars($_GET['searchAluno'] ?? '') ?>">
        <button class="btn btn-success" type="submit">Buscar</button>
      </div>
      <select name="diasAtrasos" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 160px;">
        <option value="30" <?= $diasAtrasos==30?'selected':'' ?>>Últimos 30 dias</option>
        <option value="15" <?= $diasAtrasos==15?'selected':'' ?>>Últimos 15 dias</option>
        <option value="7" <?= $diasAtrasos==7?'selected':'' ?>>Últimos 7 dias</option>
        <option value="1" <?= $diasAtrasos==1?'selected':'' ?>>Último dia</option>
      </select>
    </form>
  </div>


      <?php if ($resAtrasosRecentes && $resAtrasosRecentes->num_rows > 0): ?>
        <?php while ($a = $resAtrasosRecentes->fetch_assoc()): ?>
          <div class="atraso mb-3">
            <h5><?= htmlspecialchars($a['aluno']) ?></h5>
            <p class="mb-1">
              <img src="../assets/img/data.svg" alt="data" class="img-btn">
              <strong>Data:</strong> <?= date('d/m/Y', strtotime($a['data'])) ?>
            </p>
            <p><strong>Motivo:</strong> <?= htmlspecialchars($a['motivo'] ?? '—') ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="alert alert-info text-center">Nenhum atraso registrado nos últimos <?= $diasAtrasos ?> dias.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<footer>
  <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
