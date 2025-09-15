<?php
session_start();
require_once '../conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../LOGIN/login.php');
    exit;
}

// Captura os dados da sessão
$usuario = $_SESSION['usuario'];

// Verifica se a sessão tem os campos essenciais
if (!isset($usuario['idUSUARIO'], $usuario['tipo'])) {
    die('Sessão inválida. Faça login novamente.');
}

$tipo = $usuario['tipo']; // 1 = Servidor AE, 2 = Professor, 3 = Aluno
$idUsuario = $usuario['idUSUARIO'];

// Calcula o total de atrasos conforme o tipo de usuário
$qtdAtrasos = 0;
if ($tipo == 3) {
    // Aluno: conta apenas seus próprios atrasos
    $sqlAtrasos = "SELECT COUNT(*) AS total FROM ATRASO WHERE idAluno = ?";
    $stmtAtrasos = $conexao->prepare($sqlAtrasos);
    $stmtAtrasos->bind_param("i", $idUsuario);
    $stmtAtrasos->execute();
    $resAtrasos = $stmtAtrasos->get_result();
    if ($resAtrasos) {
        $rowAtrasos = $resAtrasos->fetch_assoc();
        $qtdAtrasos = (int)$rowAtrasos['total'];
    }
} elseif ($tipo == 2) {
    // Professor: conta atrasos dos alunos da(s) turma(s) do professor
    // Supondo que professor vê atrasos dos alunos da mesma turma
    $turma = $usuario['turma'] ?? null;
    if ($turma) {
        $sqlAtrasos = "
            SELECT COUNT(*) AS total 
            FROM ATRASO a
            INNER JOIN USUARIO u ON a.idAluno = u.idUSUARIO
            WHERE u.turma = ?
        ";
        $stmtAtrasos = $conexao->prepare($sqlAtrasos);
        $stmtAtrasos->bind_param("s", $turma);
        $stmtAtrasos->execute();
        $resAtrasos = $stmtAtrasos->get_result();
        if ($resAtrasos) {
            $rowAtrasos = $resAtrasos->fetch_assoc();
            $qtdAtrasos = (int)$rowAtrasos['total'];
        }
    }
} else {
    // Servidor AE: conta todos os atrasos
    $sqlAtrasos = "SELECT COUNT(*) AS total FROM ATRASO";
    $resAtrasos = $conexao->query($sqlAtrasos);
    if ($resAtrasos) {
        $rowAtrasos = $resAtrasos->fetch_assoc();
        $qtdAtrasos = (int)$rowAtrasos['total'];
    }
}

// Dashboard conforme o tipo de usuário
if ($tipo == 3 || $tipo == 2) {
    // Atas redigidas pelo aluno ou professor (idRedator)
    $sqlAtas = "
        SELECT 
            a.idATA,
            a.assunto,
            a.`data`,
            a.anotacoes,
            GROUP_CONCAT(u.nome ORDER BY u.nome SEPARATOR ', ') AS participantes,
            COUNT(u.idUSUARIO) AS qtd_participantes
        FROM ATA a
        LEFT JOIN PARTICIPANTES p ON a.idATA = p.idAta
        LEFT JOIN USUARIO u ON p.idUSUARIO = u.idUSUARIO
        WHERE a.idRedator = ?
        GROUP BY a.idATA, a.assunto, a.`data`, a.anotacoes
        ORDER BY a.`data` DESC
    ";

    $sqlQtdAtas = "SELECT COUNT(*) AS total FROM ATA WHERE idRedator = ?";

    $stmtAtas = $conexao->prepare($sqlAtas);
    $stmtAtas->bind_param("i", $idUsuario);
    $stmtAtas->execute();
    $resAtas = $stmtAtas->get_result();

    $stmtQtd = $conexao->prepare($sqlQtdAtas);
    $stmtQtd->bind_param("i", $idUsuario);
    $stmtQtd->execute();
    $resQtd = $stmtQtd->get_result();
} else {
    // Servidor AE  todas as atas
    $sqlAtas = "
        SELECT 
            a.idATA,
            a.assunto,
            a.`data`,
            a.anotacoes,
            GROUP_CONCAT(u.nome SEPARATOR ', ') AS participantes,
            COUNT(u.idUSUARIO) AS qtd_participantes
        FROM ATA a
        LEFT JOIN PARTICIPANTES p ON a.idATA = p.idAta
        LEFT JOIN USUARIO u ON p.idUSUARIO = u.idUSUARIO
        GROUP BY a.idATA, a.assunto, a.`data`, a.anotacoes
        ORDER BY a.`data` DESC
    ";
    // Contagem de todas as atas
    $sqlQtdAtas = "SELECT COUNT(*) AS total FROM ATA";

    $resAtas = $conexao->query($sqlAtas);
    $resQtd = $conexao->query($sqlQtdAtas);
}

if (!$resAtas) {
    die("Erro na consulta de atas: " . $conexao->error);
}

// Executa a contagem de atas
$qtdAtas = 0;
if ($resQtd) {
    $row = $resQtd->fetch_assoc();
    $qtdAtas = (int)$row['total'];
} else {
    die("Erro na contagem de atas: " . $conexao->error);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #fafafa; margin:0; padding:0; color:#333; }
        header { background:#fff; border-bottom:1px solid #ddd; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; }
        header h1 { font-size:16px; margin:0; }
        nav a { margin-left:20px; text-decoration:none; color:#333; font-weight:bold; }
        .container { padding:30px; }
        .beneficios { background:#f5f9ff; padding:20px; border-radius:10px; margin-bottom:30px; display:grid; grid-template-columns:1fr 1fr; gap:20px; }
        .beneficios h2 { grid-column:1 / span 2; margin-bottom:10px; }
        .stats { display: flex; gap: 20px; margin-bottom: 30px; }
        .card { flex: 1; background: #fff; border: 1px solid #eee; border-radius: 10px; padding: 20px; text-align: center; }
        .beneficios div h3 { margin:0 0 5px; }
        .stats { display:flex; gap:20px; margin-bottom:30px; }
        .card { flex:1; background:#fff; border:1px solid #eee; border-radius:10px; padding:20px; text-align:center; }
        .card h3 { margin:0 0 10px; font-size:18px; }
        .card p { margin:0; font-size:14px; color:#555; }
        .atas { margin-top:20px; }
        .ata { background:#fff; border:1px solid #eee; border-radius:10px; padding:20px; margin-bottom:15px; }
        .ata h3 { margin:0 0 5px; font-size:16px; }
        .ata p { margin:5px 0; font-size:14px; }
        .ata small { display:block; margin-top:10px; font-size:12px; color:#555; }
    </style>
</head>
<body>
<header>
            <h1>📄 Sistema Unificado da Assistência Estudantil</h1>
            <nav>
                <a href="#">Ferramentas</a>
                <a href="../LOGIN/logout.php">Sair</a>
            </nav>
        </header>
<div class="container">
    <h2>Bem-vindo, <?= htmlspecialchars($usuario['nome']); ?>!</h2>
    <?php if ($tipo == 1): // - Servidor AE ?>
        <h3>Painel do Servidor AE</h3>
        <p>Aqui você pode gerenciar atrasos, atas e relatórios.</p>

    <?php elseif ($tipo == 2): // - Professor?>
        <h3>Painel do Professor</h3>
        <p>Aqui você pode registrar presença e consultar atrasos dos alunos.</p>

    <?php elseif ($tipo == 3): // - Aluno?>
        <h3>Painel do Aluno</h3>
        <p>Aqui você pode ver seus atrasos e atas.</p>
    <?php endif; ?>

    <section class="stats">
        <div class="card">
            <h3>Total de ATAs</h3>
            <p><?= $qtdAtas ?></p>
        </div>
        <div class="card">
            <h3>Total de atrasos</h3>
            <p><?= $qtdAtrasos ?></p>
        </div>
    </section>

    <?php if ($tipo == 1): // - Servidor AE ?>
        <section class="funções">
        <a href="../ATAS/cadastrar_Ata.php">
            <button type="button">
            <h3>Registrar Ata</h3>
            </button>
        </a>

        <a href="../ATRASOS/atrasos.php">
            <button type="button">
            <h3>Registrar Atraso</h3>
            </button>
        </a>
        </section>
        <?php endif; ?>

    <section class="atas">
        <h2>ATAs Recentes</h2>
        <?php if ($resAtas && $resAtas->num_rows > 0): ?>
            <?php while ($ata = $resAtas->fetch_assoc()): ?>
                <?php $data = date('d/m/Y H:i', strtotime($ata['data'])); ?>
                <div class="ata">
                    <h3><?= htmlspecialchars($ata['assunto']) ?></h3>
                    <p><?= nl2br(htmlspecialchars($ata['anotacoes'])) ?></p>
                    <small>
                        📅 <?= $data ?> &nbsp;
                        👥 <?= $ata['qtd_participantes'] ?> participantes<br>
                        Participantes: <?= htmlspecialchars($ata['participantes']) ?>
                    </small>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>Nenhuma ATA registrada ainda.</p>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
