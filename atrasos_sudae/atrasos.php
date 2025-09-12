<?php  
// Conexão com SQLite
$dbFile = __DIR__ . '/atrasos.db';
$db = new PDO("sqlite:$dbFile");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Criar tabela se não existir
$db->exec("CREATE TABLE IF NOT EXISTS atrasos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    matricula TEXT NOT NULL,
    data TEXT NOT NULL,
    motivo TEXT NOT NULL
)");

// Função para formatar data
function data_br($data) {
    return date('d/m/Y', strtotime($data));
}

// === PROCESSAMENTO DO FORMULÁRIO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $matricula    = trim($_POST['matricula']);
    $data         = $_POST['data'];
    $motivo       = trim($_POST['motivo']);
    $motivo_extra = trim($_POST['motivo_extra']);

    // Junta motivo fixo + extra
    if (!empty($motivo_extra)) {
        $motivo = $motivo . " - " . $motivo_extra;
    }

    if ($id > 0) {
        // Atualizar registro
        $stmt = $db->prepare("UPDATE atrasos SET matricula=?, data=?, motivo=? WHERE id=?");
        $stmt->execute([$matricula, $data, $motivo, $id]);
    } else {
        // Inserir novo registro
        $stmt = $db->prepare("INSERT INTO atrasos (matricula, data, motivo) VALUES (?, ?, ?)");
        $stmt->execute([$matricula, $data, $motivo]);
    }

    header("Location: atrasos.php");
    exit;
}

// === EXCLUIR REGISTRO ===
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM atrasos WHERE id=?");
    $stmt->execute([intval($_GET['delete'])]);
    header("Location: atrasos.php");
    exit;
}

// === EDITAR REGISTRO ===
$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM atrasos WHERE id=?");
    $stmt->execute([intval($_GET['edit'])]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

// === LISTAR TODOS OS REGISTROS ===
$atrasos = $db->query("
    SELECT * FROM atrasos
    ORDER BY SUBSTR(matricula, 1, 4) DESC, data DESC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);
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
            <a href="?edit=<?= $a['id'] ?>">Editar</a> |
            <a href="?delete=<?= $a['id'] ?>" onclick="return confirm('Deseja excluir este registro?')">Excluir</a>
        </td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($atrasos)): ?>
        <tr><td colspan="4">Nenhum registro encontrado.</td></tr>
    <?php endif; ?>
</table>
</body>
</html>


