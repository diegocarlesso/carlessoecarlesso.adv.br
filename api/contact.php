<?php
/**
 * api/contact.php — Processa o formulário de contato.
 *
 * Fluxo:
 *   1. Valida CSRF + campos obrigatórios (nome, email, telefone, mensagem)
 *   2. Salva sempre na tabela `contatos` (visível em /admin/contatos.php)
 *   3. Tenta enviar e-mail via mail() ao destinatário configurado (best effort —
 *      se falhar, a mensagem JÁ está salva e visível no admin)
 *   4. Retorna sempre JSON (mesmo em fatal error — handler global no início)
 */
define('CARLESSO_CMS', true);
define('BASE_PATH', dirname(dirname(__DIR__)));

// ── Sempre devolve JSON, mesmo em fatal error ──────────────────────────
header('Content-Type: application/json; charset=utf-8');

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_COMPILE_ERROR, E_CORE_ERROR])) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Erro interno no servidor. A equipe foi notificada.',
            'debug'   => (getenv('APP_ENV') === 'development') ? $err : null,
        ]);
    }
});

try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/csrf.php';
    require_once __DIR__ . '/../includes/functions.php';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(['success' => false, 'message' => 'Método não permitido.'], 405);
    }

    // ── CSRF — aceita stateless HMAC (preferido) OU session-based (fallback).
    //    Usa method_exists pra não quebrar se Csrf.php antigo ainda no servidor.
    $token = $_POST['_csrf'] ?? null;
    $csrfOk = false;
    if (method_exists('CSRF', 'validateStateless') && CSRF::validateStateless($token)) {
        $csrfOk = true;
    } elseif (CSRF::validate($token)) {
        $csrfOk = true;
    }
    if (!$csrfOk) {
        jsonResponse(['success' => false, 'message' => 'Token inválido. Recarregue a página e tente de novo.'], 403);
    }

    // ── Rate limit por IP (5 mensagens / 5 min) ─────────────────────────
    if (session_status() === PHP_SESSION_NONE) session_start();
    $now = time();
    $rl  = $_SESSION['contact_rl'] ?? ['count' => 0, 'time' => 0];
    if ($now - $rl['time'] < 300 && $rl['count'] >= 5) {
        jsonResponse(['success' => false, 'message' => 'Muitas mensagens enviadas. Aguarde 5 minutos.'], 429);
    }

    // ── Validação ───────────────────────────────────────────────────────
    $nome     = trim($_POST['nome']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $assunto  = trim($_POST['assunto']  ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');

    $errors = [];
    if (!$nome || strlen($nome) < 2) $errors[] = 'Nome é obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
    if (!$telefone || strlen(preg_replace('/\D/', '', $telefone)) < 8) {
        $errors[] = 'Telefone é obrigatório (mínimo 8 dígitos).';
    }
    if (!$mensagem || strlen($mensagem) < 10) $errors[] = 'Mensagem muito curta (mínimo 10 caracteres).';

    if ($errors) {
        jsonResponse(['success' => false, 'message' => implode(' ', $errors)], 422);
    }

    // ── Salva no banco (sempre — independente do e-mail) ────────────────
    $savedId = null;
    $dbError = null;
    try {
        // Garantia que a tabela existe (compat com instalações antigas)
        Database::query(
            'CREATE TABLE IF NOT EXISTS `contatos` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `nome` varchar(100) NOT NULL,
                `email` varchar(100) NOT NULL,
                `telefone` varchar(30) DEFAULT NULL,
                `assunto` varchar(120) DEFAULT NULL,
                `mensagem` text NOT NULL,
                `lido` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_lido_data` (`lido`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $savedId = Database::insert('contatos', [
            'nome'     => $nome,
            'email'    => $email,
            'telefone' => $telefone,
            'assunto'  => $assunto,
            'mensagem' => $mensagem,
        ]);
    } catch (\Throwable $e) {
        $dbError = $e->getMessage();
        error_log('[contact] DB save error: ' . $dbError);
    }

    // Se nem no banco salvou, devolve erro real
    if (!$savedId) {
        jsonResponse([
            'success' => false,
            'message' => 'Não foi possível registrar sua mensagem. Tente o WhatsApp ou ligue diretamente.',
            'debug'   => (getenv('APP_ENV') === 'development') ? $dbError : null,
        ], 500);
    }

    // ── Tenta enviar e-mail via Mailer (SMTP preferido, mail() fallback) ─
    // Destinatário: usa CONTACT_FORM_TO do .env se setado (e-mail interno
    // que recebe leads, ex: contatosite@...), senão cai pro email_contato
    // exibido publicamente no site (compatibilidade).
    $emailDest = env('CONTACT_FORM_TO') ?: getConfig('email_contato', 'contato@carlessoecarlesso.adv.br');
    $hostDomain = $_SERVER['HTTP_HOST'] ?? 'carlessoecarlesso.adv.br';
    $hostDomain = preg_replace('/^www\./', '', $hostDomain);

    $subject = '[Site] Nova mensagem de ' . $nome;
    if ($assunto) $subject .= ' — ' . $assunto;

    $body = "<p><strong>Nova mensagem recebida pelo site:</strong></p>"
          . "<table style='border-collapse:collapse;font-family:sans-serif'>"
          . "<tr><td style='padding:4px 12px 4px 0'><strong>Nome:</strong></td><td>" . htmlspecialchars($nome, ENT_QUOTES) . "</td></tr>"
          . "<tr><td style='padding:4px 12px 4px 0'><strong>E-mail:</strong></td><td><a href='mailto:" . htmlspecialchars($email, ENT_QUOTES) . "'>" . htmlspecialchars($email, ENT_QUOTES) . "</a></td></tr>"
          . "<tr><td style='padding:4px 12px 4px 0'><strong>Telefone:</strong></td><td>" . htmlspecialchars($telefone, ENT_QUOTES) . "</td></tr>"
          . ($assunto ? "<tr><td style='padding:4px 12px 4px 0'><strong>Assunto:</strong></td><td>" . htmlspecialchars($assunto, ENT_QUOTES) . "</td></tr>" : '')
          . "</table>"
          . "<h3>Mensagem:</h3>"
          . "<div style='background:#f9fafb;padding:12px;border-left:3px solid #527095'>" . nl2br(htmlspecialchars($mensagem, ENT_QUOTES)) . "</div>"
          . "<hr><p style='font-size:12px;color:#6b7280'>"
          . "ID #$savedId · Recebido em " . date('d/m/Y H:i') . " · IP " . ($_SERVER['REMOTE_ADDR'] ?? '?') . "<br>"
          . "Ver no painel: <a href='https://" . $hostDomain . "/admin/contatos.php'>https://$hostDomain/admin/contatos.php</a>"
          . "</p>";

    $mailResult = ['success' => false, 'method' => 'none', 'error' => 'Mailer não inicializado'];
    try {
        if (class_exists(\Carlesso\Services\Mail\Mailer::class)) {
            $mailResult = \Carlesso\Services\Mail\Mailer::send($emailDest, $subject, $body, [
                'reply_to'   => $email,
                'reply_name' => $nome,
                'is_html'    => true,
            ]);
        }
    } catch (\Throwable $e) {
        error_log('[contact] Mailer exception: ' . $e->getMessage());
        $mailResult = ['success' => false, 'method' => 'exception', 'error' => $e->getMessage()];
    }
    $mailSent = $mailResult['success'];

    if (!$mailSent) {
        error_log('[contact] E-mail NÃO enviado para ' . $emailDest . ' — método: ' . $mailResult['method'] . ', erro: ' . $mailResult['error'] . '. Mensagem #' . $savedId . ' salva no DB.');
    }

    // ── Rate limit update ───────────────────────────────────────────────
    $_SESSION['contact_rl'] = [
        'count' => ($now - $rl['time'] < 300 ? $rl['count'] : 0) + 1,
        'time'  => $now,
    ];

    // ── Resposta final ──────────────────────────────────────────────────
    jsonResponse([
        'success'    => true,
        'id'         => $savedId,
        'mail_sent'  => (bool) $mailSent,
        'message'    => $mailSent
            ? 'Mensagem enviada com sucesso! Entraremos em contato em breve.'
            : 'Mensagem registrada com sucesso! Entraremos em contato em breve.',
    ]);

} catch (\Throwable $e) {
    error_log('[contact] FATAL: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno. Tente novamente em instantes ou use o WhatsApp.',
        'debug'   => (getenv('APP_ENV') === 'development') ? [
            'msg'  => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine(),
        ] : null,
    ]);
}
