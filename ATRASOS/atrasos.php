<?php
include '../conexao.php'; // conexão com banco SUDAE

// === FUNÇÃO PARA FORMATAR DATA ===
function data_br($data) {
    return date('d/m/Y', strtotime($data));
}

// === LISTAR ALUNOS PARA O FORMULÁRIO ===
$alunos = [];
$result = $conn->query("SELECT idUSUARIO, nome, matricula FROM USUARIO WHERE tipo = 3 ORDER BY nome ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $alunos[] = $row;
    }
}

// === PROCESSAMENTO DO FORMULÁRIO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $matricula = $conn->real_escape_string($_POST['matricula']);
    $data = $conn->real_escape_string($_POST['data']);
    $motivo = $conn->real_escape_string($_POST['motivo']);
    $motivo_extra = trim($_POST['motivo_extra']);

    if (!empty($motivo_extra)) {
        $motivo .= " - " . $motivo_extra;
    }

    // Busca idAluno a partir da matrícula
    $stmt = $conn->prepare("SELECT idUSUARIO FROM USUARIO WHERE matricula = ?");
    $stmt->bind_param("s", $matricula);
    $stmt->execute();
    $result = $stmt->get_result();
    $aluno = $result->fetch_assoc();
    $stmt->close();

    if (!$aluno) {
        die("Aluno não encontrado.");
    }
    $idAluno = $aluno['idUSUARIO'];
    $idServidor = 1; // exemplo: servidor logado, ajustar conforme seu sistema

    if ($id > 0) {
        // Atualiza registro
        $stmt = $conn->prepare("UPDATE ATRASO SET idAluno=?, data=?, notificado='N', motivo=? WHERE idATRASO=?");
        $stmt->bind_param("issi", $idAluno, $data, $motivo, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insere novo registro
        $stmt = $conn->prepare("INSERT INTO ATRASO (idAluno, idServidor, data, notificado, motivo) VALUES (?, ?, ?, 'N', ?)");
        $stmt->bind_param("iiss", $idAluno, $idServidor, $data, $motivo);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === EXCLUIR REGISTRO ===
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM ATRASO WHERE idATRASO=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// === EDITAR REGISTRO ===
$edit = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT a.idATRASO, a.data, u.matricula, u.nome, a.motivo
                            FROM ATRASO a
                            INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO
                            WHERE a.idATRASO = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit = $result->fetch_assoc();
    $stmt->close();
}

// === LISTAR ATRASOS ===
$atrasos = [];
$sql = "SELECT a.idATRASO, a.data, u.matricula, u.nome, a.motivo
        FROM ATRASO a
        INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO
        ORDER BY a.data DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $atrasos[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Registro de Atrasos</title>
<style>
body { font-family: Arial; margin: 40px; }
table { border-collapse: collapse; width: 100%; margin-top: 20px; }
th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background: #f0f0f0; }
form { margin-bottom: 20px; }
input, select, textarea { width: 100%; padding: 6px; margin: 4px 0; }
textarea { height: 60px; resize: vertical; }
.btn { padding: 6px 12px; margin-right: 6px; background: #007BFF; color: white; border: none; cursor: pointer; }
.btn:hover { background: #0056b3; }
a { text-decoration: none; color: #007BFF; }
a:hover { text-decoration: underline; }
.cancel { background:#6c757d; color:white; padding:6px 12px; margin-left:5px; }
</style>
</head>
<body>
<h1>Registro de Atrasos</h1>

<!-- FORMULÁRIO CADASTRO / EDIÇÃO -->
<form method="post">
    <input type="hidden" name="id" value="<?= $edit['id'] ?? '' ?>">
    <label>Matrícula:<br>
        <input type="text" name="matricula" required pattern="\d{4}\d*" 
               title="A matrícula deve começar com o ano (4 dígitos)" 
               value="<?= htmlspecialchars($edit['matricula'] ?? '') ?>">
    </label><br>
    <label>Data:<br>
        <input type="date" name="data" required value="<?= htmlspecialchars($edit['data'] ?? date('Y-m-d')) ?>">
    </label><br>
    <label>Motivo:<br>
        <select name="motivo" required>
            <?php 
            $motivos = ["Sem justifica", "Atraso do Ônibus", "Consulta de Exames", "Outros"];
            $motivoAtual = $edit['motivo'] ?? '';
            $motivoBase = explode(" - ", $motivoAtual)[0] ?? ''; 
            foreach ($motivos as $m) {
                $selected = ($motivoBase === $m) ? 'selected' : '';
                echo "<option value=\"$m\" $selected>$m</option>";
            }
            ?>
        </select>
    </label><br>
    <label>OBS:<br>
        <textarea name="motivo_extra"><?= htmlspecialchars($edit ? (explode(" - ", $edit['motivo'])[1] ?? '') : '') ?></textarea>
    </label><br>
    <button class="btn" type="submit"><?= $edit ? 'Salvar Alterações' : 'Registrar Atraso' ?></button>
    <?php if ($edit): ?>
        <a href="atrasos.php" class="cancel">Cancelar</a>
    <?php endif; ?>
</form>

<!-- TABELA DE REGISTROS -->
<table>
    <tr>
        <th>Matrícula</th>
        <th>Data</th>
        <th>Motivo</th>
        <th>Ações</th>
    </tr>
    <?php foreach ($atrasos as $a): ?>
    <tr>
        <td><?= htmlspecialchars($a['matricula']) ?></td>
        <td><?= data_br($a['data']) ?></td>
        <td><?= nl2br(htmlspecialchars($a['motivo'])) ?></td>
        <td>
            <a href="?edit=<?= $a['idATRASO'] ?>">Editar</a> |
            <a href="?delete=<?= $a['idATRASO'] ?>" onclick="return confirm('Deseja excluir este registro?')">Excluir</a>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($atrasos)): ?>
        <tr><td colspan="4">Nenhum registro encontrado.</td></tr>
    <?php endif; ?>
</table>
</table>
</body>
</html>
