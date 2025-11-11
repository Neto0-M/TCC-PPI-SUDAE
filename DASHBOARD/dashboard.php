<?php session_start();
include '../conexao.php'; // Verifica login 
if (!isset($_SESSION['usuario'])) {
  header('Location: ../LOGIN/login.php');
  exit;
}
$usuario = $_SESSION['usuario'];
$tipo = $usuario['tipo'];
$idUsuario = $usuario['idUSUARIO']; // ===== FILTRO DE DIAS ===== 
$diasAtas = isset($_GET['diasAtas']) ? (string) $_GET['diasAtas'] : '30';
$diasAtrasos = isset($_GET['diasAtrasos']) ? (string) $_GET['diasAtrasos'] : '30'; // ===== PAGINAÇÃO ===== 
$limiteAtas = 5;
$limiteAtrasos = 5;
$paginaAtas = isset($_GET['paginaAtas']) ? max(1, intval($_GET['paginaAtas'])) : 1;
$paginaAtrasos = isset($_GET['paginaAtrasos']) ? max(1, intval($_GET['paginaAtrasos'])) : 1;
$offsetAtas = ($paginaAtas - 1) * $limiteAtas;
$offsetAtrasos = ($paginaAtrasos - 1) * $limiteAtrasos;

/* ------------------ CONTAGEM DE ATRASOS ------------------ */
$qtdAtrasos = 0;
if ($tipo == 3) {
  $sqlAtrasos = "SELECT COUNT(*) AS total FROM ATRASO WHERE idAluno = ? AND data >= ?";
  $stmt = $conexao->prepare($sqlAtrasos);
  if ($diasAtrasos === 'ano') {
    $anoAtual = date('Y');
    $dataLimite = $anoAtual . '-01-01';
    $stmt->bind_param("is", $idUsuario, $dataLimite);
  } else {
    $dataLimite = date('Y-m-d', strtotime("-$diasAtrasos days"));
    $stmt->bind_param("is", $idUsuario, $dataLimite);
  }
  $stmt->execute();
  $qtdAtrasos = (int) $stmt->get_result()->fetch_assoc()['total'];
} elseif ($tipo == 2) {
  $sqlAtrasos = "SELECT COUNT(*) AS total FROM ATRASO WHERE idProfessor = ? AND data >= ?";
  $stmt = $conexao->prepare($sqlAtrasos);
  if ($diasAtrasos === 'ano') {
    $anoAtual = date('Y');
    $dataLimite = $anoAtual . '-01-01';
    $stmt->bind_param("is", $idUsuario, $dataLimite);
  } else {
    $dataLimite = date('Y-m-d', strtotime("-$diasAtrasos days"));
    $stmt->bind_param("is", $idUsuario, $dataLimite);
  }
  $stmt->execute();
  $qtdAtrasos = (int) $stmt->get_result()->fetch_assoc()['total'];
} else {
  if ($diasAtrasos === 'ano') {
    $anoAtual = date('Y');
    $row = $conexao->query("SELECT COUNT(*) AS total FROM ATRASO WHERE YEAR(data) = '$anoAtual'")->fetch_assoc();
  } else {
    $dataLimite = date('Y-m-d', strtotime("-$diasAtrasos days"));
    $row = $conexao->query("SELECT COUNT(*) AS total FROM ATRASO WHERE data >= '$dataLimite'")->fetch_assoc();
  }
  $qtdAtrasos = (int) $row['total'];
}

/* ------------------ ATAS ------------------ */
if ($tipo == 1) { // servidor 
  if ($diasAtas === 'ano') {
    $anoAtual = date('Y');
    $resAtas = $conexao->query("SELECT a.*, GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS participantes, COUNT(u.idUSUARIO) AS qtd_participantes FROM ATA a LEFT JOIN PARTICIPANTES p ON a.idATA = p.idAta LEFT JOIN USUARIO u ON p.idUSUARIO = u.idUSUARIO WHERE YEAR(a.data) = '$anoAtual' GROUP BY a.idATA ORDER BY a.data DESC LIMIT $limiteAtas OFFSET $offsetAtas");
    $qtdAtas = $conexao->query("SELECT COUNT(*) AS total FROM ATA WHERE YEAR(data) = '$anoAtual'")->fetch_assoc()['total'];
  } else {
    $dataLimite = date('Y-m-d', strtotime("-$diasAtas days"));
    $resAtas = $conexao->query("SELECT a.*, GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS participantes, COUNT(u.idUSUARIO) AS qtd_participantes FROM ATA a LEFT JOIN PARTICIPANTES p ON a.idATA = p.idAta LEFT JOIN USUARIO u ON p.idUSUARIO = u.idUSUARIO WHERE a.data >= '$dataLimite' GROUP BY a.idATA ORDER BY a.data DESC LIMIT $limiteAtas OFFSET $offsetAtas");
    $qtdAtas = $conexao->query("SELECT COUNT(*) AS total FROM ATA WHERE data >= '$dataLimite'")->fetch_assoc()['total'];
  }
} else {
  // professor ou aluno 
  if ($diasAtas === 'ano') {
    $anoAtual = date('Y');
    $stmt = $conexao->prepare("SELECT a.*, GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS participantes, COUNT(u.idUSUARIO) AS qtd_participantes FROM ATA a LEFT JOIN PARTICIPANTES p ON a.idATA = p.idAta LEFT JOIN USUARIO u ON p.idUSUARIO = u.idUSUARIO WHERE p.idUSUARIO = ? AND YEAR(a.data) = ? GROUP BY a.idATA ORDER BY a.data DESC LIMIT $limiteAtas OFFSET $offsetAtas");
    $stmt->bind_param("ii", $idUsuario, $anoAtual);
    $qtdAtas = $conexao->query("SELECT COUNT(*) AS total FROM PARTICIPANTES p JOIN ATA a ON p.idAta = a.idATA WHERE p.idUSUARIO = $idUsuario AND YEAR(a.data) = '$anoAtual'")->fetch_assoc()['total'];
  } else {
    $dataLimite = date('Y-m-d', strtotime("-$diasAtas days"));
    $stmt = $conexao->prepare("SELECT a.*, GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS participantes, COUNT(u.idUSUARIO) AS qtd_participantes FROM ATA a LEFT JOIN PARTICIPANTES p ON a.idATA = p.idAta LEFT JOIN USUARIO u ON p.idUSUARIO = u.idUSUARIO WHERE p.idUSUARIO = ? AND a.data >= ? GROUP BY a.idATA ORDER BY a.data DESC LIMIT $limiteAtas OFFSET $offsetAtas");
    $stmt->bind_param("is", $idUsuario, $dataLimite);
    $qtdAtas = $conexao->query("SELECT COUNT(*) AS total FROM PARTICIPANTES p JOIN ATA a ON p.idAta = a.idATA WHERE p.idUSUARIO = $idUsuario AND a.data >= '$dataLimite'")->fetch_assoc()['total'];
  }
  $stmt->execute();
  $resAtas = $stmt->get_result();
}

/* ------------------ ATRASOS RECENTES ------------------ */
$searchAluno = trim($_GET['searchAluno'] ?? '');
if ($tipo != 3) {
  if ($searchAluno !== '') {
    if ($diasAtrasos === 'ano') {
      $anoAtual = date('Y');
      $stmt = $conexao->prepare("SELECT a.idATRASO, u.nome AS aluno, a.data, a.motivo FROM ATRASO a INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO WHERE YEAR(a.data) = ? AND u.nome LIKE CONCAT('%', ?, '%') ORDER BY a.data DESC LIMIT $limiteAtrasos OFFSET $offsetAtrasos");
      $stmt->bind_param("is", $anoAtual, $searchAluno);
    } else {
      $dataLimite = date('Y-m-d', strtotime("-$diasAtrasos days"));
      $stmt = $conexao->prepare("SELECT a.idATRASO, u.nome AS aluno, a.data, a.motivo FROM ATRASO a INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO WHERE a.data >= ? AND u.nome LIKE CONCAT('%', ?, '%') ORDER BY a.data DESC LIMIT $limiteAtrasos OFFSET $offsetAtrasos");
      $stmt->bind_param("ss", $dataLimite, $searchAluno);
    }
  } else {
    if ($diasAtrasos === 'ano') {
      $anoAtual = date('Y');
      $stmt = $conexao->prepare("SELECT a.idATRASO, u.nome AS aluno, a.data, a.motivo FROM ATRASO a INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO WHERE YEAR(a.data) = ? ORDER BY a.data DESC LIMIT $limiteAtrasos OFFSET $offsetAtrasos");
      $stmt->bind_param("i", $anoAtual);
    } else {
      $dataLimite = date('Y-m-d', strtotime("-$diasAtrasos days"));
      $stmt = $conexao->prepare("SELECT a.idATRASO, u.nome AS aluno, a.data, a.motivo FROM ATRASO a INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO WHERE a.data >= ? ORDER BY a.data DESC LIMIT $limiteAtrasos OFFSET $offsetAtrasos");
      $stmt->bind_param("s", $dataLimite);
    }
  }
  $stmt->execute();
  $resAtrasosRecentes = $stmt->get_result();
} else {
  if ($diasAtrasos === 'ano') {
    $anoAtual = date('Y');
    $stmt = $conexao->prepare("SELECT idATRASO, data, motivo FROM ATRASO WHERE idAluno = ? AND YEAR(data) = ? ORDER BY data DESC LIMIT $limiteAtrasos OFFSET $offsetAtrasos");
    $stmt->bind_param("ii", $idUsuario, $anoAtual);
  } else {
    $dataLimite = date('Y-m-d', strtotime("-$diasAtrasos days"));
    $stmt = $conexao->prepare("SELECT idATRASO, data, motivo FROM ATRASO WHERE idAluno = ? AND data >= ? ORDER BY data DESC LIMIT $limiteAtrasos OFFSET $offsetAtrasos");
    $stmt->bind_param("is", $idUsuario, $dataLimite);
  }
  $stmt->execute();
  $resAtrasosRecentes = $stmt->get_result();
}

/* ===== PAGINAÇÃO TOTAL ===== */
$totalPaginasAtas = ceil($qtdAtas / $limiteAtas);
$totalPaginasAtrasos = ceil($qtdAtrasos / $limiteAtrasos); ?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <title>Dashboard - SUDAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #e6f4ec;
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      overflow-x: hidden;
      position: relative;
      padding-bottom: 100px;
    }

    header {
      background-color: #fff;
      padding: 15px 40px;
      border-bottom: 2px solid #dceee2;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      position: relative;
      z-index: 10;
    }

    header h1 {
      position: absolute;
      left: 140px;
      font-size: 1.2rem;
      color: #198754;
      font-weight: bold;
      margin: 0;
    }

    .logo {
      position: absolute;
      left: 25px;
      width: 50px;
    }

    .container {
      margin-top: 10px;
      position: relative;
      z-index: 1;
    }

    .card,
    .ata,
    .atraso {
      border-radius: 12px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
      background: #fff;
      padding: 20px;
      margin-bottom: 15px;
    }

    .ata h5,
    .atraso h5,
    .card h5 {
      color: #198754;
      font-weight: bold;
    }

    .botao-grande-gap {
      gap: 27rem !important;
    }

    .img-btn {
      width: 24px;
      padding-bottom: 3px;
    }

    .pagination .page-item.active .page-link {
      background-color: #198754;
      border-color: #198754;
      color: #fff;
    }

    .pagination .page-link:hover {
      background-color: #157347;
      border-color: #157347;
      color: #fff;
    }

    .pagination .page-link {
      color: #198754;
      border: 1px solid #198754;
    }

    footer {
      background-color: rgba(255, 255, 255, 0.96);
      backdrop-filter: blur(8px);
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      text-align: center;
      color: #555;
      font-size: 0.9rem;
      padding: 10px 0;
      border-top: 2px solid #dceee2;
      box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
      z-index: 9999;
    }

    .container,
    .row,
    .card,
    .ata,
    .atraso {
      position: relative;
      z-index: 1;
    }

    .pagination {
      position: relative;
      z-index: 0;
      margin-bottom: 80px;
    }

    .hover-ata {
      transition: background-color 0.2s, box-shadow 0.2s;
      cursor: pointer;
    }

    .hover-ata:hover {
      background-color: #f5fdf8;
      box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
    }

    .filtro-container {
      display: flex;
      align-items: center;
      gap: 10px;
      float: right;
    }
  </style>
</head>

<body>
  <header> <img src="../assets/img/SUDAE.svg" alt="Logo SUDAE" class="logo">
    <h1>Sistema Unificado da Assistência Estudantil</h1>
    <nav> <?php if ($tipo == 1): ?> <a href="../LOGIN/cadastro.php" class="btn btn-outline-success btn-sm">Cadastrar</a>
      <?php endif; ?> <a href="dados.php" class="btn btn-outline-secondary btn-sm">Meus Dados</a> <a
        href="../LOGIN/logout.php" class="btn btn-danger btn-sm">Sair</a> </nav>
  </header>
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="fw-bold text-success">Bem-vindo, <?= htmlspecialchars($usuario['nome']); ?>!</h2>
      <?php if ($tipo == 1): ?>
        <p class="text-muted">Painel do Servidor AE — Gerencie atrasos e atas</p> <?php elseif ($tipo == 2): ?>
        <p class="text-muted">Painel do Professor — Visualize atas e consulte atrasos.</p> <?php else: ?>
        <p class="text-muted">Painel do Aluno — Visualize seus atrasos e atas.</p> <?php endif; ?>
    </div>
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
    </div> <?php if ($tipo == 1): ?>
      <div class="d-flex justify-content-center mb-5 flex-wrap botao-grande-gap"> 
        <a href="../ATAS/cadastrar_Ata.php" class="btn btn-success btn-lg px-4">
          <img src="../assets/img/ata.svg" class="img-btn"> Registrar ATA</a> 
        <a href="../ATRASOS/atrasos.php" class="btn btn-warning btn-lg px-4 text-white">
          <img src="../assets/img/atraso.svg" class="img-btn"> Registrar Atraso
        </a> 
      </div> <?php endif; ?>
    <div class="row g-4">
      <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="fw-bold text-success">ATAs Recentes</h4>
          <div class="filtro-container">
            <form method="get" class="m-0 p-0"> <input type="hidden" name="diasAtrasos" value="<?= $diasAtrasos ?>">
              <select name="diasAtas" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="30" <?= $diasAtas == 30 ? 'selected' : '' ?>>Últimos 30 dias</option>
                <option value="15" <?= $diasAtas == 15 ? 'selected' : '' ?>>Últimos 15 dias</option>
                <option value="7" <?= $diasAtas == 7 ? 'selected' : '' ?>>Últimos 7 dias</option>
                <option value="ano" <?= $diasAtas === 'ano' ? 'selected' : '' ?>>Ano todo</option>
              </select>
            </form>
          </div>
        </div> <?php if ($resAtas && $resAtas->num_rows > 0): ?>   <?php while ($ata = $resAtas->fetch_assoc()): ?> <a
              href="../ATAS/cadastrar_ata.php?editar=<?= $ata['idATA'] ?>" class="text-decoration-none text-dark">
              <div class="ata mb-3 p-3 hover-ata">
                <h5><?= htmlspecialchars($ata['assunto'] ?? $ata['titulo']) ?></h5>
                <p class="text-truncate"><?= strip_tags($ata['anotacoes']) ?></p> <small class="text-muted"> <img
                    src="../assets/img/data.svg" class="img-btn"> <?= date('d/m/Y H:i', strtotime($ata['data'])) ?><br>
                  <?php if (isset($ata['qtd_participantes'])): ?> <img src="../assets/img/participantes.svg"
                      class="img-btn"> <?= $ata['qtd_participantes'] ?> participantes<br> <em>Participantes:</em>
                    <?= htmlspecialchars($ata['participantes']) ?>     <?php endif; ?> </small>
              </div>
            </a> <?php endwhile; ?> <?php else: ?>
          <div class="alert alert-info text-center">Nenhuma ATA registrada ainda.</div> <?php endif; ?>
        <nav>
          <ul class="pagination justify-content-center"> <?php for ($i = 1; $i <= $totalPaginasAtas; $i++): ?>
              <li class="page-item <?= ($i == $paginaAtas) ? 'active' : '' ?>"> <a class="page-link"
                  href="?paginaAtas=<?= $i ?>&paginaAtrasos=<?= $paginaAtrasos ?>"><?= $i ?></a> </li> <?php endfor; ?>
          </ul>
        </nav>
      </div>
      <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h4 class="fw-bold text-success">Atrasos Recentes</h4>
          <form method="get" class="d-flex align-items-center gap-2 mb-3"> <input type="hidden" name="diasAtas"
              value="<?= $diasAtas ?>"> <?php if ($tipo != 3): ?> <input type="text" name="searchAluno"
                class="form-control form-control-sm" placeholder="Pesquisar aluno..."
                value="<?= htmlspecialchars($_GET['searchAluno'] ?? '') ?>"> <button class="btn btn-success btn-sm"
                type="submit">Buscar</button> <?php endif; ?> <select name="diasAtrasos"
              class="form-select form-select-sm" onchange="this.form.submit()">
              <option value="30" <?= $diasAtrasos == 30 ? 'selected' : '' ?>>Últimos 30 dias</option>
              <option value="15" <?= $diasAtrasos == 15 ? 'selected' : '' ?>>Últimos 15 dias</option>
              <option value="7" <?= $diasAtrasos == 7 ? 'selected' : '' ?>>Últimos 7 dias</option>
              <option value="ano" <?= $diasAtrasos === 'ano' ? 'selected' : '' ?>>Ano todo</option>
            </select> </form>
        </div> <?php if ($resAtrasosRecentes && $resAtrasosRecentes->num_rows > 0): ?>
          <?php while ($a = $resAtrasosRecentes->fetch_assoc()): ?>
            <div class="atraso mb-3">
              <h5><?= htmlspecialchars($a['aluno'] ?? $usuario['nome']) ?></h5>
              <p class="mb-1"><img src="../assets/img/data.svg" class="img-btn"> <strong>Data:</strong>
                <?= date('d/m/Y', strtotime($a['data'])) ?></p>
              <p><strong>Motivo:</strong> <?= htmlspecialchars($a['motivo'] ?? '—') ?></p>
            </div> <?php endwhile; ?> <?php else: ?>
          <div class="alert alert-info text-center">Nenhum atraso registrado nos últimos
            <?= htmlspecialchars($diasAtrasos) ?> dias.
          </div> <?php endif; ?>
        <nav>
          <ul class="pagination justify-content-center"> <?php for ($i = 1; $i <= $totalPaginasAtrasos; $i++): ?>
              <li class="page-item <?= ($i == $paginaAtrasos) ? 'active' : '' ?>"> <a class="page-link"
                  href="?paginaAtrasos=<?= $i ?>&paginaAtas=<?= $paginaAtas ?>"><?= $i ?></a> </li> <?php endfor; ?>
          </ul>
        </nav>
      </div>
    </div>
  </div>
  <footer>
    <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>