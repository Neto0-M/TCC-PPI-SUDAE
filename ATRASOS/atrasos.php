<?php
session_start();
include '../conexao.php';
date_default_timezone_set('America/Sao_Paulo');
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
    // Garante que a sessão está ativa
    if (!isset($_SESSION['usuario']['idUSUARIO'])) {
        die("<div class='alert alert-danger text-center'>Você precisa estar logado!</div>");
    }

    $idServidor = $_SESSION['usuario']['idUSUARIO'];
    $id = $_POST['id'] ?? 0;
    $matricula = trim($_POST['matricula']);
    $data = $_POST['data'];
    $motivo = $_POST['motivo'];
    $motivo_extra = trim($_POST['motivo_extra']);
    $idProfessor = $_POST['idProfessor'] ?? null;

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

    $idAluno = $aluno['idUSUARIO'];

    // Inserir ou atualizar
    if ($id > 0) {
        $stmt = $conexao->prepare("
            UPDATE ATRASO 
            SET idAluno = ?, idServidor = ?, idProfessor = ?, data = ?, motivo = ?, notificado = 'N' 
            WHERE idATRASO = ?
        ");
        $stmt->bind_param("iiissi", $idAluno, $idServidor, $idProfessor, $data, $motivo, $id);
    } else {
        $stmt = $conexao->prepare("
            INSERT INTO ATRASO (idAluno, idServidor, idProfessor, data, motivo, notificado) 
            VALUES (?, ?, ?, ?, ?, 'N')
        ");
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
    :root {
        --verde-sudae: #198754;
        --verde-claro: #e8f5ee;
        --cinza-claro: #f9f9f9;
    }

    body {
        background-color: var(--verde-claro);
        font-family: "Segoe UI", sans-serif;
        padding-bottom: 100px;
    }

    header {
        background-color: #fff;
        border-bottom: 2px solid #dceee2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 40px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 0;
        z-index: 10;
    }

    header .logo {
        width: 50px;
        height: auto;
    }

    header h1 {
        flex-grow: 1;
        font-size: 1.3rem;
        font-weight: bold;
        color: var(--verde-sudae);
        padding-left: 5px;
        margin: 0;
    }

    header nav a {
        margin-left: 10px;
    }

    .container {
        max-width: 1100px;
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
        color: #555;
        font-size: 0.9rem;
        padding: 10px 0;
        border-top: 2px solid #dceee2;
        box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.05);
        z-index: 99;
    }

    @media (max-width: 768px) {
        header {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }

        header h1 {
            font-size: 1.1rem;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .col-md-4,
        .col-md-6,
        .col-md-8 {
            flex: 100%;
            max-width: 100%;
        }

        table {
            font-size: 0.8rem;
        }

        .btn {
            margin-top: 5px;
        }
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
            <div class="mb-3 row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="text" id="buscarTextoAtrasos" class="form-control"
                        placeholder="Buscar por matrícula, aluno, professor ou motivo...">
                </div>
                <div class="col-md-3">
                    <input type="date" id="buscarDataInicioAtrasos" class="form-control" placeholder="Data inicial">
                </div>
                <div class="col-md-3">
                    <input type="date" id="buscarDataFimAtrasos" class="form-control" placeholder="Data final">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-success w-100" id="limparFiltrosAtrasos">Limpar
                        filtros</button>
                </div>
            </div>

            <table class="table table-hover table-bordered align-middle" id="tabelaAtrasos">
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
                    <?php if ($atrasos): ?>
                        <?php foreach ($atrasos as $a): ?>
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
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nenhum atraso registrado.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Paginação -->
            <nav id="paginacaoAtrasos" class="d-flex justify-content-center mt-3"></nav>
        </div>
    </div>

    <footer>
        <p>© <?= date('Y') ?> SUDAE - Sistema Unificado da Assistência Estudantil</p>
    </footer>

    <script>
        const inputTextoAtrasos = document.getElementById('buscarTextoAtrasos');
        const inputDataInicioAtrasos = document.getElementById('buscarDataInicioAtrasos');
        const inputDataFimAtrasos = document.getElementById('buscarDataFimAtrasos');
        const btnLimparAtrasos = document.getElementById('limparFiltrosAtrasos');
        const tabela = document.querySelector("#tabelaAtrasos tbody");
        const todasLinhas = Array.from(tabela.querySelectorAll("tr"));
        const paginacao = document.getElementById("paginacaoAtrasos");
        const porPagina = 5;
        let paginaAtual = 1;
        let linhasFiltradas = [...todasLinhas];

        function renderizarTabela() {
            const inicio = (paginaAtual - 1) * porPagina;
            const fim = inicio + porPagina;

            linhasFiltradas.forEach((linha, i) => {
                linha.style.display = (i >= inicio && i < fim) ? "" : "none";
            });

            atualizarPaginacao();
        }

        function atualizarPaginacao() {
            const totalPaginas = Math.ceil(linhasFiltradas.length / porPagina);
            paginacao.innerHTML = "";

            if (totalPaginas <= 1) return;

            const ul = document.createElement("ul");
            ul.className = "pagination";

            const liAnterior = document.createElement("li");
            liAnterior.className = `page-item ${paginaAtual === 1 ? "disabled" : ""}`;
            liAnterior.innerHTML = `<a class="page-link text-success" href="#">Anterior</a>`;
            liAnterior.onclick = (e) => {
                e.preventDefault();
                if (paginaAtual > 1) {
                    paginaAtual--;
                    renderizarTabela();
                }
            };
            ul.appendChild(liAnterior);

            for (let i = 1; i <= totalPaginas; i++) {
                const li = document.createElement("li");
                li.className = `page-item ${i === paginaAtual ? "active" : ""}`;
                li.innerHTML = `<a class="page-link ${i === paginaAtual ? 'bg-success border-success text-white' : 'text-success'}" href="#">${i}</a>`;
                li.onclick = (e) => {
                    e.preventDefault();
                    paginaAtual = i;
                    renderizarTabela();
                };
                ul.appendChild(li);
            }

            const liProximo = document.createElement("li");
            liProximo.className = `page-item ${paginaAtual === totalPaginas ? "disabled" : ""}`;
            liProximo.innerHTML = `<a class="page-link text-success" href="#">Próximo</a>`;
            liProximo.onclick = (e) => {
                e.preventDefault();
                if (paginaAtual < totalPaginas) {
                    paginaAtual++;
                    renderizarTabela();
                }
            };
            ul.appendChild(liProximo);

            paginacao.appendChild(ul);
        }

        function filtrarAtrasos() {
            const textoFiltro = inputTextoAtrasos.value.toLowerCase();
            const dataInicio = inputDataInicioAtrasos.value ? new Date(inputDataInicioAtrasos.value) : null;
            const dataFim = inputDataFimAtrasos.value ? new Date(inputDataFimAtrasos.value) : null;

            linhasFiltradas = todasLinhas.filter(row => {
                const dataRow = new Date(row.cells[0].textContent.split(' ')[0].split('/').reverse().join('-'));
                const matricula = row.cells[1].textContent.toLowerCase();
                const aluno = row.cells[2].textContent.toLowerCase();
                const professor = row.cells[3].textContent.toLowerCase();
                const motivo = row.cells[4].textContent.toLowerCase();

                const textoOk = matricula.includes(textoFiltro) || aluno.includes(textoFiltro) ||
                    professor.includes(textoFiltro) || motivo.includes(textoFiltro);

                let dataOk = true;
                if (dataInicio && dataRow < dataInicio) dataOk = false;
                if (dataFim && dataRow > dataFim) dataOk = false;

                row.style.display = (textoOk && dataOk) ? "" : "none";
                return (textoOk && dataOk);
            });

            paginaAtual = 1;
            renderizarTabela();
        }

        inputTextoAtrasos.addEventListener('keyup', filtrarAtrasos);
        inputDataInicioAtrasos.addEventListener('change', filtrarAtrasos);
        inputDataFimAtrasos.addEventListener('change', filtrarAtrasos);
        btnLimparAtrasos.addEventListener('click', () => {
            inputTextoAtrasos.value = '';
            inputDataInicioAtrasos.value = '';
            inputDataFimAtrasos.value = '';
            linhasFiltradas = [...todasLinhas];
            paginaAtual = 1;
            renderizarTabela();
        });

        renderizarTabela();

        // Buscar aluno por matrícula
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