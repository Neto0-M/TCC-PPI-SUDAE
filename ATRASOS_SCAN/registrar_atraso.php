<?php
session_start();
include '../conexao.php';

$idServidor = $_SESSION['usuario']['idUSUARIO'] ?? 0;
$matricula = $_POST['matricula'] ?? '';

if (!$idServidor || !$matricula) {
    echo "Erro: dados inválidos.";
    exit;
}

// busca id do aluno pela matrícula
$sql = $conexao->prepare("SELECT idUSUARIO FROM USUARIO WHERE matricula=? AND tipo=3");
$sql->bind_param("s", $matricula);
$sql->execute();
$res = $sql->get_result();

if ($res->num_rows === 1) {
    $aluno = $res->fetch_assoc();
    $idAluno = $aluno['idUSUARIO'];

    // insere registro na tabela ATRASO
    $stmt = $conexao->prepare("INSERT INTO ATRASO (data, idAluno, idServidor, motivo) VALUES (NOW(), ?, ?, ?)");
    $motivo = "Registrado via QR Code";
    $stmt->bind_param("iis", $idAluno, $idServidor, $motivo);

    if ($stmt->execute()) {
        echo "Atraso registrado para matrícula $matricula.";
    } else {
        echo "Erro ao registrar atraso.";
    }
} else {
    echo "Aluno não encontrado ou não é do tipo 'aluno'.";
}
