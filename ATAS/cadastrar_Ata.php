<?php
require_once '../conexao.php';
session_start();

// Verifica login
if (!isset($_SESSION['usuario'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

$idRedator = $_SESSION['usuario']['idUSUARIO'];
$tipoUsuario = $_SESSION['usuario']['tipo'];
$modoVisualizacao = in_array($tipoUsuario, [2, 3]); // Professores e alunos só visualizam
$msg = "";
$ataEdit = null;
$participantesEdit = [];

// ====== Buscar dados para edição ======
if (isset($_GET['editar'])) {
    $idEditar = intval($_GET['editar']);

    $sql = "SELECT * FROM ATA WHERE idATA = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idEditar);
    $stmt->execute();
    $ataEdit = $stmt->get_result()->fetch_assoc();

    if ($ataEdit) {
        $sql_part = "SELECT idUSUARIO FROM PARTICIPANTES WHERE idAta = ?";
        $stmt_part = $conn->prepare($sql_part);
        $stmt_part->bind_param("i", $idEditar);
        $stmt_part->execute();
        $res_part = $stmt_part->get_result();
        while ($row = $res_part->fetch_assoc()) {
            $participantesEdit[] = $row['idUSUARIO'];
        }
    }
}

// ====== Bloquear envio se for visualização ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $modoVisualizacao) {
    die("<div class='alert alert-danger text-center mt-3'>Você não tem permissão para alterar esta ATA.</div>");
}

// ====== Cadastrar nova ou editar ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $data = date("Y-m-d H:i:s", strtotime($_POST['data']));
    $assunto = trim($_POST['assunto']);
    $anotacoes = $_POST['anotacoes'];
    $encaminhamentos = $_POST['encaminhamentos'];
    $participantes = $_POST['participantes'] ?? [];

    if ($acao === 'cadastrar') {
        $sql = "INSERT INTO ATA (data, assunto, anotacoes, encaminhamentos, idRedator)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $data, $assunto, $anotacoes, $encaminhamentos, $idRedator);

        if ($stmt->execute()) {
            $idAta = $stmt->insert_id;

            if (!empty($participantes)) {
                $sql_part = "INSERT INTO PARTICIPANTES (idAta, idUSUARIO, dataRegistro, assinatura) VALUES (?, ?, NOW(), 'N')";
                $stmt_part = $conn->prepare($sql_part);

                foreach ($participantes as $idUsuario) {
                    $stmt_part->bind_param("ii", $idAta, $idUsuario);
                    $stmt_part->execute();
                }
            }

            $msg = "<div class='alert alert-success'>ATA cadastrada com sucesso!</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Erro ao cadastrar ATA: " . $conn->error . "</div>";
        }
    }

    if ($acao === 'editar' && isset($_POST['idATA'])) {
        $idATA = intval($_POST['idATA']);
        $sql = "UPDATE ATA SET data=?, assunto=?, anotacoes=?, encaminhamentos=? WHERE idATA=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $data, $assunto, $anotacoes, $encaminhamentos, $idATA);

        if ($stmt->execute()) {
            // Apaga participantes antigos
            $stmt_del = $conn->prepare("DELETE FROM PARTICIPANTES WHERE idAta = ?");
            $stmt_del->bind_param("i", $idATA);
            $stmt_del->execute();

            if (!empty($participantes)) {
                $sql_part = "INSERT INTO PARTICIPANTES (idAta, idUSUARIO, dataRegistro, assinatura) VALUES (?, ?, NOW(), 'N')";
                $stmt_part = $conn->prepare($sql_part);

                foreach ($participantes as $idUsuario) {
                    $stmt_part->bind_param("ii", $idATA, $idUsuario);
                    $stmt_part->execute();
                }
            }

            $msg = "<div class='alert alert-success'>ATA atualizada com sucesso!</div>";
        } else {
            $msg = "<div class='alert alert-danger'>Erro ao atualizar ATA: " . $conn->error . "</div>";
        }
    }
}

// ====== Excluir ATA ======
if (isset($_GET['delete']) && !$modoVisualizacao) {
    $idATA = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM ATA WHERE idATA=?");
    $stmt->bind_param("i", $idATA);
    $stmt->execute();
}

// ====== Buscar participantes ======
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
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <style>
    body {
      background-color: #e6f4ec;
      font-family: 'Segoe UI', sans-serif;
      padding-bottom: 60px;
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
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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
    background-color: #fff;
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      text-align: center;
      color: #666;
      font-size: 0.9rem;
      padding: 5px 0;
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
    <h3 class="text-success mb-4">
      <?= $ataEdit ? 'Visualizar ATA #' . $ataEdit['idATA'] : 'Cadastrar nova ATA' ?>
    </h3>

    <?php if (!empty($msg))
      echo $msg; ?>

    <form method="POST" class="mb-5">
      <input type="hidden" name="acao" value="<?= $ataEdit ? 'editar' : 'cadastrar' ?>">
      <?php if ($ataEdit): ?>
        <input type="hidden" name="idATA" value="<?= $ataEdit['idATA'] ?>">
      <?php endif; ?>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Data</label>
          <input type="datetime-local" name="data" class="form-control" required
            value="<?= $ataEdit ? date('Y-m-d\TH:i', strtotime($ataEdit['data'])) : '' ?>" <?= $modoVisualizacao ? 'disabled' : '' ?>>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-semibold">Assunto</label>
          <input type="text" name="assunto" class="form-control" required
            value="<?= htmlspecialchars($ataEdit['assunto'] ?? '') ?>" <?= $modoVisualizacao ? 'disabled' : '' ?>>
        </div>
      </div>

      <!-- Conteúdo da ATA -->
      <div class="mt-3">
        <label class="form-label fw-semibold">Conteúdo da ATA</label>
        <div id="editor"
          style="min-height: 300px; background-color: #fff; border: 1px solid #ced4da; border-radius: 4px;">
          <?= $ataEdit['anotacoes'] ?? '' ?>
        </div>
        <input type="hidden" name="anotacoes" id="anotacoes">
      </div>

      <div class="mt-3">
        <label class="form-label fw-semibold">Encaminhamentos</label>
        <textarea name="encaminhamentos" class="form-control" rows="3" <?= $modoVisualizacao ? 'disabled' : '' ?>><?= htmlspecialchars($ataEdit['encaminhamentos'] ?? '') ?></textarea>
      </div>

      <h5 class="text-success mb-3 mt-3">Selecionar Participantes</h5>
      <div class="row g-4">
        <?php
        function renderParticipantes($lista, $titulo, $idDiv, $selecionados = [], $modoVisualizacao)
        {
          echo "<div class='col-md-4'>
                  <h6 class='text-center text-success'>$titulo</h6>
                  <input type='text' class='form-control form-control-sm mb-2' placeholder='Pesquisar...' onkeyup=\"filtrar(this, '$idDiv')\">
                  <div class='coluna-participantes' id='$idDiv'>";
          while ($p = $lista->fetch_assoc()) {
            $checked = in_array($p['idUSUARIO'], $selecionados) ? 'checked' : '';
            echo "<div class='form-check'>
                      <input class='form-check-input' type='checkbox' name='participantes[]' value='{$p['idUSUARIO']}' $checked " . ($modoVisualizacao ? 'disabled' : '') . ">
                      <label class='form-check-label'>" . htmlspecialchars($p['nome']) . "</label>
                    </div>";
          }
          echo "</div></div>";
        }

        renderParticipantes($alunos, "Alunos", "aluno-lista", $participantesEdit, $modoVisualizacao);
        renderParticipantes($professores, "Professores", "prof-lista", $participantesEdit, $modoVisualizacao);
        renderParticipantes($servidores, "Servidores", "serv-lista", $participantesEdit, $modoVisualizacao);
        ?>
      </div>

      <div class="d-flex justify-content-end gap-3 mt-4">
        <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary px-4">Voltar</a>
        <?php if (!$modoVisualizacao): ?>
          <?php if ($ataEdit): ?>
            <a href="cadastrar_ata.php" class="btn btn-outline-secondary px-4">Cancelar edição</a>
          <?php endif; ?>
          <button type="submit" class="btn btn-success px-4">
            <?= $ataEdit ? 'Atualizar ATA' : 'Salvar ATA' ?>
          </button>
        <?php endif; ?>
      </div>
    </form>

    <div class="card mt-5 p-4">
    <h4 class="fw-bold text-success mb-3">ATAs Registradas</h4>
    <table class="table table-hover table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Data</th>
                <th>Assunto</th>
                <th>Redator</th>
                <th>Participantes</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($ata = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($ata['data'])) ?></td>
                        <td><?= htmlspecialchars($ata['assunto']) ?></td>
                        <td><?= htmlspecialchars($ata['redator']) ?></td>
                        <td><?= htmlspecialchars($ata['participantes'] ?: '-') ?></td>
                        <td class="text-center">
                            <?php if (!$modoVisualizacao): ?>
                                <a href="?editar=<?= $ata['idATA'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                <a href="?delete=<?= $ata['idATA'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Excluir esta ATA?')">Excluir</a>
                            <?php else: ?>
                                <span class="text-muted">Sem ações</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">Nenhuma ATA registrada.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

    <footer>
      <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
    </footer>

    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
      function filtrar(input, listaId) {
        const filtro = input.value.toLowerCase();
        document.querySelectorAll(`#${listaId} .form-check`).forEach(item => {
          const nome = item.textContent.toLowerCase();
          item.style.display = nome.includes(filtro) ? '' : 'none';
        });
      }

      var quill = new Quill('#editor', {
        theme: 'snow',
        placeholder: 'Escreva aqui as anotações da ATA...',
        modules: {
          toolbar: [
            [{ header: [1, 2, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'image'],
            ['clean']
          ]
        }
      });

      <?php if ($ataEdit): ?>
        quill.root.innerHTML = <?= json_encode($ataEdit['anotacoes'] ?? '') ?>;
      <?php endif; ?>

      if (<?= json_encode($modoVisualizacao) ?>) {
        quill.disable();
      }

      document.querySelector('form').addEventListener('submit', function (e) {
        document.getElementById('anotacoes').value = quill.root.innerHTML;
      });
    </script>

</body>

</html>