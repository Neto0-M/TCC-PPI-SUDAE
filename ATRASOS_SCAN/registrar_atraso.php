<?php
session_start();
include '../conexao.php';

date_default_timezone_set('America/Sao_Paulo');
$idServidor = $_SESSION['usuario']['idUSUARIO'] ?? 0;

// BUSCAR ALUNO (GET)
if (isset($_GET['acao']) && $_GET['acao'] === 'buscar_aluno') {
    $matricula = $_GET['matricula'] ?? '';
    $response = ['nome' => null];

    if ($matricula) {
        $stmt = $conexao->prepare("SELECT nome FROM USUARIO WHERE matricula = ?");
        $stmt->bind_param("s", $matricula);
        $stmt->execute();
        $stmt->bind_result($nome);
        if ($stmt->fetch())
            $response['nome'] = $nome;
        $stmt->close();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// REGISTRAR ATRASO (POST)
$matricula = $_POST['matricula'] ?? '';
$idProfessor = intval($_POST['idProfessor'] ?? 0);
$motivo = trim($_POST['motivo'] ?? '');
$obs = trim($_POST['observacao'] ?? '');

if (!$matricula || !$idProfessor || !$motivo)
    die("Preencha todos os campos obrigatórios.");

// Busca aluno
$stmt = $conexao->prepare("SELECT idUSUARIO, nome FROM USUARIO WHERE matricula = ?");
$stmt->bind_param("s", $matricula);
$stmt->execute();
$aluno = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$aluno)
    die("Aluno não encontrado.");

$idAluno = $aluno['idUSUARIO'];
$data = date('Y-m-d H:i:s');
$motivoCompleto = $motivo . ($obs ? " - " . $obs : '');

// Inserir no banco
$stmt = $conexao->prepare("INSERT INTO ATRASO (idAluno, idServidor, data, notificado, motivo) VALUES (?, ?, ?, 'N', ?)");
$stmt->bind_param("iiss", $idAluno, $idServidor, $data, $motivoCompleto);
$stmt->execute();
$stmt->close();

// Simula notificação ao professor (TCC, apenas mensagem)
$stmt = $conexao->prepare("SELECT nome, email FROM USUARIO WHERE idUSUARIO = ?");
$stmt->bind_param("i", $idProfessor);
$stmt->execute();
$prof = $stmt->get_result()->fetch_assoc();
$stmt->close();

$msgProf = $prof ? "Notificação enviada ao professor {$prof['nome']} ({$prof['email']})" : "Professor não encontrado.";

echo "Registro de atraso salvo com sucesso para <strong>{$aluno['nome']}</strong>.<br>$msgProf";
?>