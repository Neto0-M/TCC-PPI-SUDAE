<?php

// Função para sanitizar dados de entrada
if (!function_exists('sanitize_input')) {
	function sanitize_input($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
		return $data;
	}
}

// Configurações do banco de dados
$host = "localhost"; // ou o endereço do seu servidor de banco de dados
$user = "root"; // seu usuário do MySQL
$pass = ""; // sua senha do MySQL
$db = "sudae"; // nome do banco de dados

// Tenta estabelecer a conexão
$conn = new mysqli($host, $user, $pass, $db);

// Verifica se houve erro na conexão
if ($conn->connect_error) {
    // Em ambiente de produção, você pode logar o erro em vez de exibi-lo
    error_log("Erro de conexão com o banco de dados: " . $conn->connect_error);
    die("Desculpe, ocorreu um erro ao conectar ao banco de dados. Por favor, tente novamente mais tarde.");
}

// Define o charset para UTF-8 para evitar problemas com caracteres especiais
$conn->set_charset("utf8");

