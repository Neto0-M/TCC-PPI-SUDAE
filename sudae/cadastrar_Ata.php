<?php
require_once 'conexao.php';

// ====== Cadastrar nova ATA ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'cadastrar') {
    $data = $_POST['data'];
    $assunto = $_POST['assunto'];
    $anotacoes = $_POST['anotacoes'];
    $encaminhamentos = $_POST['encaminhamentos'];
    $idRedator = $_POST['idRedator'];

    $sql = "INSERT INTO ATA (idATA, data, assunto, anotacoes, encaminhamentos, idRedator) 
            VALUES (NULL, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $data, $assunto, $anotacoes, $encaminhamentos, $idRedator);
    $stmt->execute();
}

// ====== Atualizar (Edit) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $idATA = $_POST['idATA'];
    $data = $_POST['data'];
    $assunto = $_POST['assunto'];
    $anotacoes = $_POST['anotacoes'];
    $encaminhamentos = $_POST['encaminhamentos'];
    $idRedator = $_POST['idRedator'];

    $sql = "UPDATE ATA SET data=?, assunto=?, anotacoes=?, encaminhamentos=?, idRedator=? WHERE idATA=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $data, $assunto, $anotacoes, $encaminhamentos, $idRedator, $idATA);
    $stmt->execute();
}

// ====== Excluir ======
if (isset($_GET['delete'])) {
    $idATA = $_GET['delete'];
    $sql = "DELETE FROM ATA WHERE idATA=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idATA);
    $stmt->execute();
}

// ====== Carregar dados para edição ======
$ata_edit = null;
if (isset($_GET['edit'])) {
    $idATA = $_GET['edit'];
    $sql = "SELECT * FROM ATA WHERE idATA=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idATA);
    $stmt->execute();
    $ata_edit = $stmt->get_result()->fetch_assoc();
}

// ====== Listar todas ======
$sql_listar = "SELECT ATA.idATA, ATA.data, ATA.assunto, USUARIO.nome AS redator 
               FROM ATA 
               INNER JOIN USUARIO ON ATA.idRedator = USUARIO.idUSUARIO
               ORDER BY ATA.data DESC";
$result == $conn->query($sql_listar);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cadastro de ATAs</title>
</head>
<body>
    <h2><?php echo $ata_edit ? "Editar ATA" : "Cadastrar nova ATA"; ?></h2>
    <form method="POST">
        <input type="hidden" name="acao" value="<?php echo $ata_edit ? "editar" : "cadastrar"; ?>">
        <?php if ($ata_edit): ?>
            <input type="hidden" name="idATA" value="<?php echo $ata_edit['idATA']; ?>">
        <?php endif; ?>

        Data: <input type="datetime-local" name="data" value="<?php echo $ata_edit['data'] ?? ''; ?>" required><br><br>
        Assunto: <input type="text" name="assunto" value="<?php echo $ata_edit['assunto'] ?? ''; ?>" required><br><br>
        Conteúdo ATA: <textarea name="anotacoes" required><?php echo $ata_edit['anotacoes'] ?? ''; ?></textarea><br><br>
        Encaminhamentos: <textarea name="encaminhamentos"><?php echo $ata_edit['encaminhamentos'] ?? ''; ?></textarea><br><br>

        Aluno: 
        <select name="idRedator" required>
            <option value="">-- Selecione --</option>
            <?php
            $usuarios = $conn->query("SELECT idUSUARIO, nome FROM USUARIO");
            while ($u = $usuarios->fetch_assoc()) {
                $selected = ($ata_edit && $ata_edit['idRedator'] == $u['idUSUARIO']) ? "selected" : "";
                echo "<option value='{$u['idUSUARIO']}' $selected>{$u['nome']}</option>";
            }
            ?>
        </select><br><br>

        <button type="submit"><?php echo $ata_edit ? "Atualizar" : "Salvar"; ?></button>
    </form>

    <a href="dashboard.php">Voltar</a>

    <h2>ATAs Registradas</h2>
    <table border="2" cellpadding="3">
        <tr>
            <th>ID</th>
            <th>Data</th>
            <th>Assunto</th>
            <th>Aluno</th>
            <th>Ações</th>
        </tr>
        <?php while ($ata = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $ata['idATA']; ?></td>
                <td><?php echo $ata['data']; ?></td>
                <td><?php echo $ata['assunto']; ?></td>
                <td><?php echo $ata['redator']; ?></td>
                <td>
                    <a href="?edit=<?php echo $ata['idATA']; ?>">Editar</a> | 
                    <a href="?delete=<?php echo $ata['idATA']; ?>" onclick="return confirm('Deseja excluir esta ATA?')">Excluir</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

