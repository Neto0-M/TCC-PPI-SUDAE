<?php
include '../conexao.php';
function data_br($data) {
    return date('d/m/Y H:i:s', strtotime($data));
}

// === LISTAR ALUNOS ===
$alunos = [];
$res = $conexao->query("SELECT idUSUARIO, nome, matricula FROM USUARIO WHERE tipo = 3 ORDER BY nome ASC");
if ($res) while ($r = $res->fetch_assoc()) $alunos[] = $r;

// === LISTAR PROFESSORES ===
$professores = [];
$res = $conexao->query("SELECT idUSUARIO, nome, email FROM USUARIO WHERE tipo = 2 ORDER BY nome ASC");
if ($res) while ($r = $res->fetch_assoc()) $professores[] = $r;

// === PROCESSAMENTO DO FORMULÁRIO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $matricula = $_POST['matricula'];
    $data = $_POST['data'];
    $motivo = $_POST['motivo'];
    $motivo_extra = trim($_POST['motivo_extra']);
    $idProfessor = $_POST['idProfessor'] ?? 0;

    if ($motivo_extra) $motivo .= " - " . $motivo_extra;

    $stmt = $conexao->prepare("SELECT idUSUARIO, nome FROM USUARIO WHERE matricula = ?");
    $stmt->bind_param("s", $matricula);
    $stmt->execute();
    $aluno = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$aluno) die("Aluno não encontrado.");
    $idAluno = $aluno['idUSUARIO'];
    $idServidor = 1; // exemplo

    if ($id > 0) {
        $stmt = $conexao->prepare("UPDATE ATRASO SET idAluno=?, data=?, notificado='N', motivo=? WHERE idATRASO=?");
        $stmt->bind_param("issi", $idAluno, $data, $motivo, $id);
    } else {
        $stmt = $conexao->prepare("INSERT INTO ATRASO (idAluno, idServidor, data, notificado, motivo) VALUES (?, ?, ?, 'N', ?)");
        $stmt->bind_param("iiss", $idAluno, $idServidor, $data, $motivo);
    }
    $stmt->execute();
    $stmt->close();

    // notificação ao professor
    if ($idProfessor) {
        $stmt = $conexao->prepare("SELECT nome, email FROM USUARIO WHERE idUSUARIO = ?");
        $stmt->bind_param("i", $idProfessor);
        $stmt->execute();
        $professor = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($professor) {
            $to = $professor['email'];
            $subject = "Registro de atraso - " . $aluno['nome'];
            $message = "Prezado(a) {$professor['nome']},\n\nFoi registrado um atraso para o aluno {$aluno['nome']}.\nMatrícula: $matricula\nData: " . date('d/m/Y H:i:s', strtotime($data)) . "\nMotivo: $motivo\n\nAtenciosamente,\nSistema SUDAE";
            $headers = "From: no-reply@sudae.com.br\r\n";
            mail($to, $subject, $message, $headers);
        }
    }

    header("Location: atrasos.php");
    exit;
}

// === EXCLUSÃO ===
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conexao->prepare("DELETE FROM ATRASO WHERE idATRASO=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: atrasos.php");
    exit;
}

// === EDIÇÃO ===
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conexao->prepare("SELECT a.idATRASO, a.data, u.matricula, u.nome, a.motivo
        FROM ATRASO a INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO WHERE a.idATRASO = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// === LISTAR ATRASOS ===
$atrasos = [];
$res = $conexao->query("SELECT a.idATRASO, a.data, u.matricula, u.nome, a.motivo
                        FROM ATRASO a
                        INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO
                        ORDER BY a.data DESC");
if ($res) while ($r = $res->fetch_assoc()) $atrasos[] = $r;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Registro de Atrasos - SUDAE</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body {
  background-color: #e6f4ec;
  font-family: 'Segoe UI', sans-serif;
  min-height: 100vh;
  position: relative;
}
header {
  background-color: #fff;
  padding: 15px 40px;
  border-bottom: 2px solid #dceee2;
  display: flex;
  align-items: center;
  position: relative;
}
.logo {
  position: absolute;
  left: 25px;
  width: 50px;
  height: auto;
}
header h1 {
  position: absolute;
  left: 100px;
  font-size: 1.2rem;
  color: #198754;
  font-weight: bold;
}
.card {
  border-radius: 12px;
  box-shadow: 0 0 10px rgba(0,0,0,0.05);
}
footer {
  text-align: center;
  padding: 10px;
  color: #666;
  font-size: 0.9rem;
  margin-top: 50px;
}
.fade-form {
  opacity: 0;
  transform: translateY(10px);
  animation: fadeIn 0.6s ease forwards;
}
@keyframes fadeIn {
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
</head>
<body>
<header class="d-flex align-items-center justify-content-between px-4 py-3 bg-white border-bottom">
  <div class="d-flex align-items-center">
    <img src="../assets/img/SUDAE.svg" alt="Logo SUDAE" class="logo me-3">
    <h1 class="h5 text-success m-0">Registro de Atrasos</h1>
  </div>
  
  <nav>
    <a href="../LOGIN/logout.php" class="btn btn-danger btn-sm">Sair</a>
  </nav>
</header>

<div class="container mt-4">
  <div class="card p-4 fade-form">
    <h3 class="text-success mb-3"><?= $edit ? 'Editar Atraso' : 'Registrar Atraso' ?></h3>

    <form method="post">
      <input type="hidden" name="id" value="<?= $edit['idATRASO'] ?? '' ?>">

      <div class="mb-3">
        <label class="form-label">Matrícula</label>
        <input type="text" name="matricula" class="form-control" required minlength="10"
               value="<?= htmlspecialchars($edit['matricula'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label class="form-label">Data</label>
        <input type="datetime-local" name="data" class="form-control"
               value="<?= isset($edit['data']) ? date('Y-m-d\TH:i', strtotime($edit['data'])) : date('Y-m-d\TH:i') ?>" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Professor</label>
        <select name="idProfessor" class="form-select" required>
          <option value="">Selecione um professor</option>
          <?php foreach ($professores as $p): ?>
            <option value="<?= $p['idUSUARIO'] ?>"><?= htmlspecialchars($p['nome']) ?> (<?= htmlspecialchars($p['email']) ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Motivo</label>
        <select name="motivo" class="form-select" required>
          <?php
          $motivos = ["Sem justificativa", "Motivos de Saúde", "Chuva Forte", "Atraso do Ônibus", "Consulta de Exames", "Outros"];
          $motivoAtual = $edit['motivo'] ?? '';
          $motivoBase = explode(" - ", $motivoAtual)[0] ?? '';
          foreach ($motivos as $m) {
            $sel = ($motivoBase === $m) ? 'selected' : '';
            echo "<option value='$m' $sel>$m</option>";
          }
          ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Observação (Opcional)</label>
        <textarea name="motivo_extra" class="form-control"><?= htmlspecialchars($edit ? (explode(" - ", $edit['motivo'])[1] ?? '') : '') ?></textarea>
      </div>

      <div class="d-flex justify-content-center gap-5 flex-wrap">
        <button type="submit" class="btn btn-success btn-lg px-4"><?= $edit ? 'Salvar' : 'Registrar' ?></button>
        <a href="../ATRASOS_SCAN/scanner.php" class="btn btn-warning btn-lg px-4 text-white">Registrar via QR Code</a>
        <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary btn-lg px-4">Voltar</a>
        <?php if ($edit): ?>
          <a href="atrasos.php" class="btn btn-outline-danger btn-lg px-4">Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="card mt-5 p-4 fade-form">
    <h4 class="text-success mb-3">Atrasos Registrados</h4>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-success">
          <tr>
            <th>Matrícula</th>
            <th>Data</th>
            <th>Motivo</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($atrasos): foreach ($atrasos as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['matricula']) ?></td>
              <td><?= data_br($a['data']) ?></td>
              <td><?= htmlspecialchars($a['motivo']) ?></td>
              <td>
                <a href="?edit=<?= $a['idATRASO'] ?>" class="btn btn-sm btn-outline-success">Editar</a>
                <a href="?delete=<?= $a['idATRASO'] ?>" class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Deseja excluir este registro?')">Excluir</a>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="4" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer>
  <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
