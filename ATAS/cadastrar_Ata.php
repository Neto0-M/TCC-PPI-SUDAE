<?php
require_once '../conexao.php';
session_start();

// 🔒 Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header("Location: ../LOGIN/login.php");
    exit;
}

$idRedator = $_SESSION['usuario']['idUSUARIO']; // redator = usuário logado

// ====== Cadastrar nova ATA ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $data = date("Y-m-d H:i:s", strtotime($_POST['data']));
    $assunto = $_POST['assunto'];
    $anotacoes = $_POST['anotacoes'];
    $encaminhamentos = $_POST['encaminhamentos'];
    $participantes = $_POST['participantes'] ?? []; // array de IDs de usuários

    // 1) Inserir na tabela ATA
    $sql = "INSERT INTO ATA (data, assunto, anotacoes, encaminhamentos, idRedator) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $data, $assunto, $anotacoes, $encaminhamentos, $idRedator);

    if ($stmt->execute()) {
        $idAta = $stmt->insert_id; // pega o ID da ATA inserida

        // 2) Inserir os participantes selecionados
        $sql_part = "INSERT INTO PARTICIPANTES (idAta, idUSUARIO, dataRegistro, assinatura) 
                     VALUES (?, ?, NOW(), 'N')";
        $stmt_part = $conn->prepare($sql_part);

        foreach ($participantes as $idUsuario) {
            $stmt_part->bind_param("ii", $idAta, $idUsuario);
            $stmt_part->execute();
        }

        echo "<p>ATA cadastrada com sucesso!</p>";
    } else {
        echo "<p>Erro ao cadastrar: " . $stmt->error . "</p>";
    }
}

// ====== Excluir ======
if (isset($_GET['delete'])) {
    $idATA = $_GET['delete'];

    // exclui automaticamente da tabela PARTICIPANTES (por causa do ON DELETE CASCADE)
    $sql = "DELETE FROM ATA WHERE idATA=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idATA);
    $stmt->execute();
}

// ====== Listar todas ======
$sql_listar = "SELECT A.idATA, A.data, A.assunto, U.nome AS redator,
                      GROUP_CONCAT(PU.nome SEPARATOR ', ') AS participantes
               FROM ATA A
               INNER JOIN USUARIO U ON A.idRedator = U.idUSUARIO
               LEFT JOIN PARTICIPANTES P ON A.idATA = P.idAta
               LEFT JOIN USUARIO PU ON P.idUSUARIO = PU.idUSUARIO
               GROUP BY A.idATA
               ORDER BY A.data DESC";
$result = $conn->query($sql_listar);
if (!$result) {
    die("Erro ao listar ATAs: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cadastro de ATAs</title>
</head>
<body>

<h2>Cadastrar nova ATA</h2>

<form method="POST">
    <input type="hidden" name="acao" value="cadastrar">

    <label>Data:</label>
    <input type="datetime-local" name="data" required><br><br>

    <label>Assunto:</label>
    <input type="text" name="assunto" required><br><br>

    <label>Conteúdo ATA:</label>
    <textarea name="anotacoes" required></textarea><br><br>

    <label>Encaminhamentos:</label>
    <textarea name="encaminhamentos"></textarea><br><br>
<label>Participantes:</label><br>
<select name="participantes[]" multiple required>
    <?php
    $usuarios = $conn->query("SELECT idUSUARIO, nome, tipo FROM USUARIO WHERE tipo IN (2,3)"); // só professores e alunos
    while ($u = $usuarios->fetch_assoc()) {
        $tipo = ($u['tipo'] == 2) ? "Professor" : "Aluno";
        echo "<option value='{$u['idUSUARIO']}'>{$u['nome']} ({$tipo})</option>";
    }
    ?>
</select>
<br>

    <button type="submit">Salvar</button>
</form>

<a href="../DASHBOARD/dashboard.php">Voltar</a>

<h2>ATAs Registradas</h2>

<table border="2" cellpadding="3">
    <tr>
        <th>ID</th>
        <th>Data</th>
        <th>Assunto</th>
        <th>Redator</th>
        <th>Participantes</th>
        <th>Ações</th>
    </tr>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($ata = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $ata['idATA']; ?></td>
                <td><?php echo $ata['data']; ?></td>
                <td><?php echo $ata['assunto']; ?></td>
                <td><?php echo $ata['redator']; ?></td>
                <td><?php echo $ata['participantes'] ?: 'Nenhum'; ?></td>
                <td>
                    <a href="?delete=<?php echo $ata['idATA']; ?>" onclick="return confirm('Deseja excluir esta ATA?')">Excluir</a>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="6">Nenhuma ATA encontrada.</td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>
