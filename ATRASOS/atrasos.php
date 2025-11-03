<?php
include '../conexao.php';

function data_br($data)
{
    return date('d/m/Y H:i:s', strtotime($data));
}

// === LISTAR PROFESSORES ===
$professores = [];
$res = $conexao->query("SELECT idUSUARIO, nome FROM USUARIO WHERE tipo = 2 ORDER BY nome ASC");
if ($res)
    while ($r = $res->fetch_assoc())
        $professores[] = $r;

// === BUSCAR ALUNO POR MATRÍCULA ===
if (isset($_GET['buscar_aluno'])) {
    $matricula = $_GET['buscar_aluno'];
    $stmt = $conexao->prepare("SELECT nome FROM USUARIO WHERE matricula = ?");
    $stmt->bind_param("s", $matricula);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    echo json_encode($res ?: []);
    exit;
}

// === LISTAR ATRASOS ===
$atrasos = [];
$sql = "SELECT a.idATRASO, a.data, u.matricula, u.nome AS aluno_nome, p.nome AS professor_nome, a.motivo
        FROM ATRASO a
        INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO
        LEFT JOIN USUARIO p ON a.idProfessor = p.idUSUARIO
        ORDER BY a.data DESC";
$res = $conexao->query($sql);
if ($res)
    while ($r = $res->fetch_assoc())
        $atrasos[] = $r;

// === FORMULÁRIO - SUBMISSÃO ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $matricula = $_POST['matricula'];
    $data = $_POST['data'];
    $motivo = $_POST['motivo'];
    $motivo_extra = trim($_POST['motivo_extra']);
    $idProfessor = $_POST['idProfessor'];

    if ($motivo_extra)
        $motivo .= " - " . $motivo_extra;

    // Buscar aluno pelo número de matrícula
    $stmt = $conexao->prepare("SELECT idUSUARIO FROM USUARIO WHERE matricula = ? AND tipo = 3");
    $stmt->bind_param("s", $matricula);
    $stmt->execute();
    $aluno = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$aluno)
        die("<div class='alert alert-danger text-center'>Aluno não encontrado!</div>");

    if ($id > 0) {
        $stmt = $conexao->prepare("UPDATE ATRASO SET idAluno = ?, idServidor = ?, idProfessor = ?, data = ?, motivo = ?, notificado = 'N' WHERE idATRASO = ?");
        $stmt->bind_param("iiissi", $idAluno, $idServidor, $idProfessor, $data, $motivo, $id);
    } else {
        $stmt = $conexao->prepare("INSERT INTO ATRASO (idAluno, idServidor, idProfessor, data, motivo, notificado) VALUES (?, ?, ?, ?, ?, 'N')");
        $stmt->bind_param("iiiss", $idAluno, $idServidor, $idProfessor, $data, $motivo);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: atrasos.php");
    exit;
}

// === EXCLUSÃO ===
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conexao->prepare("DELETE FROM ATRASO WHERE idATRASO = ?");
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
    $stmt = $conexao->prepare("SELECT a.idATRASO, a.data, a.idProfessor, u.matricula, u.nome AS aluno_nome, a.motivo
        FROM ATRASO a
        INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO
        WHERE a.idATRASO = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
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

        .btn-outline-light:hover {
            background: white;
            color: #198754;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.08);
        }

        th {
            color: #198754;
        }

        .btn-outline-primary {
            border-color: #198754;
            color: #198754;
        }

        .btn-outline-primary:hover {
            background-color: #198754;
            color: #fff;
        }

        .btn-outline-danger {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-outline-danger:hover {
            background-color: #dc3545;
            color: #fff;
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
            <a href="../DASHBOARD/dados.php" class="btn btn-outline-secondary btn-sm">Meus Dados</a>
            <a href="../LOGIN/logout.php" class="btn btn-danger btn-sm">Sair</a>
        </nav>
    </header>

    <div class="container mt-5">
        <div class="card p-4">
            <h3 class="fw-bold text-success mb-3"><?= $edit ? 'Editar Atraso' : 'Registrar Atraso' ?></h3>

            <form method="post">
                <input type="hidden" name="id" value="<?= $edit['idATRASO'] ?? '' ?>">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Matrícula</label>
                        <input type="text" name="matricula" id="matricula" class="form-control" required
                            value="<?= $edit['matricula'] ?? '' ?>">
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Nome do Aluno</label>
                        <input type="text" class="form-control" id="nomeAluno" readonly
                            value="<?= $edit['aluno_nome'] ?? '' ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Data</label>
                        <input type="datetime-local" name="data" class="form-control"
                            value="<?= isset($edit['data']) ? date('Y-m-d\TH:i', strtotime($edit['data'])) : date('Y-m-d\TH:i') ?>"
                            required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-semibold">Professor</label>
                        <select name="idProfessor" class="form-select" required>
                            <option value="">Selecione um professor</option>
                            <?php foreach ($professores as $p):
                                $selected = ($edit && $edit['idProfessor'] == $p['idUSUARIO']) ? 'selected' : '';
                                echo "<option value='{$p['idUSUARIO']}' $selected>{$p['nome']}</option>";
                            endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Motivo</label>
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

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Observação (opcional)</label>
                        <textarea name="motivo_extra"
                            class="form-control"><?= htmlspecialchars($edit ? (explode(" - ", $edit['motivo'])[1] ?? '') : '') ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-success px-4"><?= $edit ? 'Salvar' : 'Registrar' ?></button>
                    <?php if ($edit): ?>
                        <a href="atrasos.php" class="btn btn-secondary px-4">Cancelar</a>
                    <?php endif; ?>
                    <a href="../ATRASOS_SCAN/scanner.php" class="btn btn-warning px-4 text-white fw-semibold">Registrar
                        via QRCode</a>
                    <a href="../DASHBOARD/dashboard.php" class="btn btn-secondary px-4">Voltar ao Dashboard</a>
                </div>
            </form>
        </div>

        <div class="card mt-5 p-4">
            <h4 class="fw-bold text-success mb-3">Atrasos Registrados</h4>
            <table class="table table-hover table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Data</th>
                        <th>Matrícula</th>
                        <th>Aluno</th>
                        <th>Professor</th>
                        <th>Motivo</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($atrasos):
                        foreach ($atrasos as $a): ?>
                            <tr>
                                <td><?= data_br($a['data']) ?></td>
                                <td><?= htmlspecialchars($a['matricula']) ?></td>
                                <td><?= htmlspecialchars($a['aluno_nome']) ?></td>
                                <td><?= htmlspecialchars($a['professor_nome'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($a['motivo']) ?></td>
                                <td class="text-center">
                                    <a href="?edit=<?= $a['idATRASO'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                    <a href="?delete=<?= $a['idATRASO'] ?>" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Excluir este atraso?')">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nenhum atraso registrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <footer>
        <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
    </footer>

    <script>
        document.getElementById('matricula').addEventListener('blur', function () {
            const matricula = this.value.trim();
            const nomeAluno = document.getElementById('nomeAluno');

            if (matricula.length >= 5) {
                fetch(`?buscar_aluno=${encodeURIComponent(matricula)}`)
                    .then(res => res.json())
                    .then(data => {
                        nomeAluno.value = data.nome || 'Aluno não encontrado';
                    })
                    .catch(() => nomeAluno.value = 'Erro na requisição');
            } else {
                nomeAluno.value = '';
            }
        });
    </script>

</body>

</html>