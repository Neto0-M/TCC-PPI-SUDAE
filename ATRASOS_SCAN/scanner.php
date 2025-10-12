<?php
session_start();
include '../conexao.php';

// Garante login ativo
if (!isset($_SESSION['usuario'])) {
    header('Location: ../LOGIN/login.php');
    exit;
}

$idServidor = $_SESSION['usuario']['idUSUARIO'] ?? 0;

// LISTAR PROFESSORES
$professores = [];
$result = $conexao->query("SELECT idUSUARIO, nome, email FROM USUARIO WHERE tipo = 2 ORDER BY nome ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $professores[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Scanner QR Code - SUDAE</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<style>
body { background-color: #e6f4ec; font-family: 'Segoe UI', sans-serif; }
.fade-in { opacity: 0; transform: translateY(10px); animation: fadeIn 0.6s ease forwards; }
@keyframes fadeIn { to { opacity: 1; transform: translateY(0); } }
</style>
</head>
<body>
<div class="container py-5 text-center">
  <h2 class="text-success mb-4">Escanear QR Code</h2>
  <div id="reader" style="width:300px; margin:auto;"></div>
  <div id="result" class="mt-4"></div>

  <!-- Formulário exibido após leitura -->
  <div id="form-section" class="mt-4" style="display:none;">
    <div class="card p-4 fade-in shadow-sm">
      <h4 class="text-success mb-3">Registrar Atraso</h4>
      <form id="form-registro">
        <input type="hidden" name="matricula" id="matricula">

        <!-- Professor -->
        <div class="mb-3 text-start">
          <label class="form-label fw-semibold">Professor</label>
          <select name="idProfessor" id="idProfessor" class="form-select" required>
            <option value="">Selecione o professor</option>
            <?php foreach ($professores as $prof): ?>
              <option value="<?= $prof['idUSUARIO'] ?>">
                <?= htmlspecialchars($prof['nome']) ?> (<?= htmlspecialchars($prof['email']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Motivo -->
        <div class="mb-3 text-start">
          <label class="form-label fw-semibold">Motivo</label>
          <select name="motivo" id="motivo" class="form-select" required>
            <option value="">Selecione o motivo</option>
            <option>Sem justificativa</option>
            <option>Motivos de Saúde</option>
            <option>Chuva Forte</option>
            <option>Atraso do Ônibus</option>
            <option>Consulta de Exames</option>
            <option>Outros</option>
          </select>
        </div>

        <!-- Observação -->
        <div class="mb-3 text-start">
          <label class="form-label fw-semibold">Observação (Opcional)</label>
          <textarea name="observacao" id="observacao" class="form-control" rows="3"></textarea>
        </div>

        <div class="d-flex justify-content-center gap-3 mt-4">
          <button type="submit" class="btn btn-success px-4">Registrar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="d-flex justify-content-center gap-3 mt-4">
          <a href="../ATRASOS/atrasos.php" class="btn btn-secondary px-4">
            <i class="bi bi-arrow-left"></i> Voltar
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Função chamada quando QR é lido
function onScanSuccess(decodedText) {
    const matricula = decodedText.trim();
    document.getElementById("matricula").value = matricula;

    fetch("registrar_atraso.php?acao=buscar_aluno&matricula=" + encodeURIComponent(matricula))
      .then(res => res.json())
      .then(data => {
          if (data && data.nome) {
              document.getElementById("reader").style.display = "none";
              document.getElementById("result").innerHTML = 
                `<div class="alert alert-success fade-in">
                   Aluno identificado: <strong>${data.nome}</strong> (${matricula})
                 </div>`;
              document.getElementById("form-section").style.display = "block";
              html5QrcodeScanner.clear();
          } else {
              document.getElementById("result").innerHTML = 
                `<div class="alert alert-danger fade-in">Aluno não encontrado.</div>`;
          }
      })
      .catch(() => {
          document.getElementById("result").innerHTML = 
            `<div class="alert alert-danger fade-in">Erro ao buscar aluno.</div>`;
      });
}

// Inicializa o scanner (fora da função)
var html5QrcodeScanner = new Html5QrcodeScanner("reader", { fps: 10, qrbox: 250 });
html5QrcodeScanner.render(onScanSuccess);

// Envio do formulário via fetch
document.getElementById("form-registro").addEventListener("submit", function(e) {
    e.preventDefault();
    const formData = new URLSearchParams(new FormData(this)).toString();

    fetch("registrar_atraso.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData
    })
    .then(res => res.text())
    .then(msg => {
        document.getElementById("form-section").innerHTML = msg;
    });
});
</script>

</body>
</html>
