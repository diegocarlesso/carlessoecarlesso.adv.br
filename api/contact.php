<?php
/**
 * api/contact.php
 * Processa formulário de contato
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Método não permitido.'], 405);
}

// CSRF
$token = $_POST['_csrf'] ?? null;
if (!CSRF::validate($token)) {
    jsonResponse(['success' => false, 'message' => 'Sessão expirada. Recarregue a página.'], 403);
}

// Rate limit simples via sessão
session_start();
$now = time();
$rl  = $_SESSION['contact_rl'] ?? ['count' => 0, 'time' => 0];
if ($now - $rl['time'] < 300 && $rl['count'] >= 3) {
    jsonResponse(['success' => false, 'message' => 'Muitas mensagens enviadas. Aguarde 5 minutos.'], 429);
}

// Validação
$nome     = trim($_POST['nome'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$assunto  = trim($_POST['assunto'] ?? '');
$mensagem = trim($_POST['mensagem'] ?? '');

$errors = [];
if (!$nome || strlen($nome) < 2)        $errors[] = 'Nome é obrigatório.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
if (!$mensagem || strlen($mensagem) < 10) $errors[] = 'Mensagem muito curta.';

if ($errors) {
    jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
}

// Salva no banco (tabela de contatos, criada abaixo)
try {
    Database::query(
        'CREATE TABLE IF NOT EXISTS `contatos` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nome` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `telefone` varchar(20) DEFAULT NULL,
            `assunto` varchar(100) DEFAULT NULL,
            `mensagem` text NOT NULL,
            `lido` tinyint(1) DEFAULT 0,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    Database::insert('contatos', [
        'nome'     => $nome,
        'email'    => $email,
        'telefone' => $telefone,
        'assunto'  => $assunto,
        'mensagem' => $mensagem,
    ]);
} catch (Exception $e) {
    error_log('Contact save error: ' . $e->getMessage());
}

// Enviar e-mail
$emailDest = getConfig('email_contato', 'contato@carlessoadvogados.com.br');
$subject   = '[Carlesso & Carlesso] Nova mensagem de ' . $nome;
if ($assunto) $subject .= " — $assunto";

$body = "Nova mensagem recebida pelo site:\n\n"
    . "Nome: $nome\n"
    . "E-mail: $email\n"
    . ($telefone ? "Telefone: $telefone\n" : '')
    . ($assunto  ? "Assunto: $assunto\n" : '')
    . "\nMensagem:\n$mensagem\n\n"
    . "---\nEnviado em: " . date('d/m/Y H:i') . "\n";

$headers  = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'carlessoecarlesso.adv.br') . "\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: CarlessoCMS/1.0\r\n";

mail($emailDest, $subject, $body, $headers);

// Rate limit update
$_SESSION['contact_rl'] = [
    'count' => ($now - $rl['time'] < 300 ? $rl['count'] : 0) + 1,
    'time'  => $now,
];

jsonResponse(['success' => true, 'message' => 'Mensagem enviada com sucesso! Entraremos em contato em breve.']);
