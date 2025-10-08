<?php
session_start();
include '../conexao.php';

// pega o servidor logado (quem está registrando o atraso)
$idServidor = $_SESSION['usuario']['idUSUARIO'] ?? 0;

if (!isset($_SESSION['usuario'])) {
    header('Location: ../LOGIN/login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Scanner QR Code - SUDAE</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-light">

<div class="container py-5 text-center">
  <h2 class="mb-4">Escanear QR Code</h2>
  <div id="reader" style="width:300px; margin:auto;"></div>
  <div id="result" class="mt-3"></div>
</div>

<script>
function onScanSuccess(decodedText, decodedResult) {
    // decodedText = matrícula do QRCode
    fetch("registrar_atraso.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "matricula=" + encodeURIComponent(decodedText)
    })
    .then(res => res.text())
    .then(msg => {
        document.getElementById("result").innerHTML = 
          `<div class="alert alert-info">${msg}</div>`;
    });

    // Para parar o scanner depois de 1 leitura:
    html5QrcodeScanner.clear();
}

var html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", { fps: 10, qrbox: 250 }
);
html5QrcodeScanner.render(onScanSuccess);
</script>
<a href="../ATRASOS/atrasos.php" class="btn btn-primary mx-auto d-block" style="max-width:200px;">
  Voltar
</a>
</body>
</html>
